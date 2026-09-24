<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Demand;
use App\Models\Receipt;

/**
 * Builds a per-member ledger: demands (charges) and receipts (payments)
 * merged chronologically with a running outstanding balance.
 */
final class MemberLedger
{
    /**
     * @return array{rows:list<array<string,mixed>>,total_demand:float,total_paid:float,balance:float}
     */
    public function build(int $memberId): array
    {
        $demands = (new Demand())->forMember($memberId);
        $receipts = (new Receipt())->forMember($memberId);

        // Sum receipts allocated to each demand, to derive an accurate status
        // and the outstanding amount for a "record receipt" action.
        $paidByDemand = [];
        foreach ($receipts as $r) {
            if (!empty($r['demand_id'])) {
                $key = (int) $r['demand_id'];
                $paidByDemand[$key] = ($paidByDemand[$key] ?? 0.0) + (float) $r['amount'];
            }
        }

        $entries = [];
        $subscriptionDue = 0.0;
        foreach ($demands as $d) {
            if ($d['status'] === 'cancelled') {
                continue;
            }
            $amount = (float) $d['amount'];
            $paid = (float) ($paidByDemand[(int) $d['id']] ?? 0.0);

            // Which "bucket" the due belongs to: an activity link wins, else it
            // is a subscription/general due.
            $bucket = !empty($d['project_id']) ? 'project'
                : (!empty($d['gift_id']) ? 'gift'
                : (!empty($d['event_id']) ? 'event' : 'subscription'));
            if ($bucket === 'subscription') {
                $subscriptionDue += $amount;
            }

            // A demand can be marked paid manually (no receipt) as well as by
            // receipts covering it.
            if ($d['status'] === 'paid' || round($amount - $paid, 2) <= 0) {
                $status = 'paid';
                $remaining = 0.0;
            } elseif ($paid > 0) {
                $status = 'partial';
                $remaining = round($amount - $paid, 2);
            } else {
                $status = 'pending';
                $remaining = $amount;
            }

            // A demand shown as paid but not covered by receipts was marked
            // paid manually — it can be reopened.
            $settle = round($amount - $paid, 2);
            $manualPaid = $status === 'paid' && $settle > 0;

            $demandDate = $d['due_date'] ?: substr((string) $d['created_at'], 0, 10);
            // Label: the purpose (e.g. Subscription) or the linked activity name.
            $forLabel = $d['purpose_name']
                ?? $d['project_name']
                ?? $d['gift_name']
                ?? $d['event_name']
                ?? 'Due';
            $entries[] = [
                'date'        => $demandDate,
                'type'        => 'Due',
                'kind'        => 'demand',
                'bucket'      => $bucket,
                'demand_id'   => (int) $d['id'],
                'status'      => $status,
                'remaining'   => $remaining,
                'reopenable'  => $manualPaid,
                'description' => ((string) $forLabel) . ($d['remarks'] ? ' — ' . $d['remarks'] : ''),
                'debit'       => $amount,
                'credit'      => 0.0,
                'sort'        => $demandDate . '-0',
            ];

            // Manually marked paid without a receipt covering the balance:
            // post a transparent non-cash settlement so the ledger nets out.
            if ($d['status'] === 'paid' && $settle > 0) {
                $when = substr((string) ($d['updated_at'] ?? $demandDate), 0, 10) ?: $demandDate;
                $entries[] = [
                    'date'        => $when,
                    'type'        => 'Adjustment',
                    'kind'        => 'adjustment',
                    'bucket'      => $bucket,
                    'description' => 'Marked paid (no receipt)',
                    'debit'       => 0.0,
                    'credit'      => $settle,
                    'sort'        => $when . '-2',
                ];
            }
        }
        $received = ['subscription' => 0.0, 'project' => 0.0, 'gift' => 0.0, 'event' => 0.0];
        foreach ($receipts as $r) {
            $cat = (string) ($r['category'] ?? 'general');
            $bucket = in_array($cat, ['project', 'gift', 'event'], true) ? $cat : 'subscription';
            $received[$bucket] += (float) $r['amount'];
            $entries[] = [
                'date'        => $r['received_on'],
                'type'        => 'Receipt',
                'kind'        => 'receipt',
                'receipt_id'  => (int) $r['id'],
                'bucket'      => $bucket,
                'description' => 'Payment received' . ($r['remarks'] ? ' — ' . $r['remarks'] : '') . ' (' . str_replace('_', ' ', (string) $r['mode']) . ')',
                'debit'       => 0.0,
                'credit'      => (float) $r['amount'],
                'sort'        => $r['received_on'] . '-1',
            ];
        }

        usort($entries, static fn ($a, $b) => strcmp((string) $a['sort'], (string) $b['sort']));

        $balance = 0.0;
        $totalDemand = 0.0;
        $totalPaid = 0.0;
        $totalAdjusted = 0.0;
        foreach ($entries as &$e) {
            $balance += $e['debit'] - $e['credit'];
            $totalDemand += $e['debit'];
            if ($e['kind'] === 'receipt') {
                $totalPaid += $e['credit'];
            } elseif ($e['kind'] === 'adjustment') {
                $totalAdjusted += $e['credit'];
            }
            $e['balance'] = $balance;
        }
        unset($e);

        $activitiesReceived = $received['project'] + $received['gift'] + $received['event'];
        $summary = [
            'subscription' => [
                'due'         => $subscriptionDue,
                'received'    => $received['subscription'],
                'outstanding' => max(0.0, $subscriptionDue - $received['subscription']),
            ],
            'received' => [
                'subscription' => $received['subscription'],
                'project'      => $received['project'],
                'gift'         => $received['gift'],
                'event'        => $received['event'],
                'activities'   => $activitiesReceived,
                'total'        => $received['subscription'] + $activitiesReceived,
            ],
        ];

        return [
            'rows'          => $entries,
            'total_demand'  => $totalDemand,
            'total_paid'    => $totalPaid,
            'total_adjusted' => $totalAdjusted,
            'balance'       => $balance,
            'summary'       => $summary,
        ];
    }
}
