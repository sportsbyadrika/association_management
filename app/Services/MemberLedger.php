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

        $entries = [];
        foreach ($demands as $d) {
            if ($d['status'] === 'cancelled') {
                continue;
            }
            $entries[] = [
                'date'        => $d['due_date'] ?: substr((string) $d['created_at'], 0, 10),
                'type'        => 'Demand',
                'description' => ucfirst((string) $d['purpose']) . ($d['remarks'] ? ' — ' . $d['remarks'] : ''),
                'debit'       => (float) $d['amount'],
                'credit'      => 0.0,
                'sort'        => ($d['due_date'] ?: substr((string) $d['created_at'], 0, 10)) . '-0',
            ];
        }
        foreach ($receipts as $r) {
            $entries[] = [
                'date'        => $r['received_on'],
                'type'        => 'Receipt',
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
