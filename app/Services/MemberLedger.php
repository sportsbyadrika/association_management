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
        foreach ($demands as $d) {
            if ($d['status'] === 'cancelled') {
                continue;
            }
            $amount = (float) $d['amount'];
            $paid = (float) ($paidByDemand[(int) $d['id']] ?? 0.0);

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
            $entries[] = [
                'date'        => $demandDate,
                'type'        => 'Demand',
                'kind'        => 'demand',
                'demand_id'   => (int) $d['id'],
                'status'      => $status,
                'remaining'   => $remaining,
                'reopenable'  => $manualPaid,
                'description' => ucfirst((string) $d['purpose']) . ($d['remarks'] ? ' — ' . $d['remarks'] : ''),
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
                    'description' => 'Marked paid (no receipt)',
                    'debit'       => 0.0,
                    'credit'      => $settle,
                    'sort'        => $when . '-2',
                ];
            }
        }
        foreach ($receipts as $r) {
            $entries[] = [
                'date'        => $r['received_on'],
                'type'        => 'Receipt',
                'kind'        => 'receipt',
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

        return [
            'rows'          => $entries,
            'total_demand'  => $totalDemand,
            'total_paid'    => $totalPaid,
            'total_adjusted' => $totalAdjusted,
            'balance'       => $balance,
        ];
    }
}
