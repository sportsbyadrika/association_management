<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class BankAccount extends Model
{
    protected string $table = 'bank_accounts';

    protected array $fillable = [
        'association_id', 'account_name', 'type', 'account_number_masked',
        'opening_balance', 'description', 'is_active',
    ];

    /** For select dropdowns. @return list<array<string,mixed>> */
    public function options(int $associationId): array
    {
        return $this->db->fetchAll(
            'SELECT id, account_name, type FROM bank_accounts WHERE association_id = ? AND is_active = 1 ORDER BY account_name ASC',
            [$associationId]
        );
    }

    /**
     * Ledger for a bank account: opening balance + receipts (in) - expenditures (out).
     * @return array{opening:float,rows:list<array<string,mixed>>,total_in:float,total_out:float,balance:float}
     */
    public function ledger(int $id, int $associationId): array
    {
        $account = $this->findForAssociation($id, $associationId);
        $opening = (float) ($account['opening_balance'] ?? 0);

        $receipts = $this->db->fetchAll(
            "SELECT received_on AS date, 'receipt' AS kind, amount, remarks,
                    NULL AS category
             FROM receipts WHERE bank_account_id = ? AND association_id = ?",
            [$id, $associationId]
        );
        $expenditures = $this->db->fetchAll(
            "SELECT paid_on AS date, 'expenditure' AS kind, amount, remarks, category
             FROM expenditures WHERE bank_account_id = ? AND association_id = ?",
            [$id, $associationId]
        );

        $rows = array_merge($receipts, $expenditures);
        usort($rows, static fn ($a, $b) => strcmp((string) $a['date'], (string) $b['date']));

        $balance = $opening;
        $totalIn = 0.0;
        $totalOut = 0.0;
        foreach ($rows as &$row) {
            if ($row['kind'] === 'receipt') {
                $balance += (float) $row['amount'];
                $totalIn += (float) $row['amount'];
                $row['in'] = (float) $row['amount'];
                $row['out'] = 0.0;
            } else {
                $balance -= (float) $row['amount'];
                $totalOut += (float) $row['amount'];
                $row['in'] = 0.0;
                $row['out'] = (float) $row['amount'];
            }
            $row['running'] = $balance;
        }
        unset($row);

        return [
            'opening'  => $opening,
            'rows'     => $rows,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'balance'  => $balance,
        ];
    }
}
