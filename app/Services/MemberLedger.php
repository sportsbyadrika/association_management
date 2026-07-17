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
            $remaining = max(0.0, round($amount - $paid, 2));
            $status = $remaining <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'pending');

            $entries[] = [
                'date'        => $d['due_date'] ?: substr((string) $d['created_at'], 0, 10),
                'type'        => 'Demand',
                'kind'        => 'demand',
                'demand_id'   => (int) $d['id'],
                'status'      => $status,
                'remaining'   => $remaining,
                'description' => ucfirst((string) $d['purpose']) . ($d['remarks'] ? ' — ' . $d['remarks'] : ''),
                'debit'       => $amount,
                'credit'      => 0.0,
                'sort'        => ($d['due_date'] ?: substr((string) $d['created_at'], 0, 10)) . '-0',
            ];
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
        foreach ($entries as &$e) {
            $balance += $e['debit'] - $e['credit'];
            $totalDemand += $e['debit'];
            $totalPaid += $e['credit'];
            $e['balance'] = $balance;
        }
        unset($e);

        return [
            'rows'         => $entries,
            'total_demand' => $totalDemand,
            'total_paid'   => $totalPaid,
            'balance'      => $balance,
        ];
    }
}
