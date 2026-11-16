<?php

namespace Crm\Services;

/** Concurrency-safe business numbers (CUS-0001 …). Row-locks the sequence inside the caller's transaction. */
final class NumberSequenceService
{
    public const DEFAULTS = [
        'customer'     => ['CUS', 4],
        'enquiry'      => ['ENQ', 4],
        'lead'         => ['LEAD', 4],
        'quotation'    => ['QT', 4],
        'negotiation'  => ['NEG', 4],
        'order'        => ['SO', 4],
        'visit'        => ['SV', 4],
        'project'      => ['PRJ', 4],
        'payment'      => ['PAY', 4],
        'receipt'      => ['RCP', 4],
        'delivery'     => ['DEL', 4],
        'challan'      => ['DC', 4],
        'installation' => ['INS', 4],
        'service'      => ['SRV', 4],
    ];

    public static function next(string $key): string
    {
        return (string) Crm::tx(static function ($db) use ($key) {
            $row = $db->query('SELECT prefix, next_value, pad_length FROM crm_number_sequences WHERE seq_key = ? FOR UPDATE', [$key])->getRowArray();
            if (! $row) {
                [$prefix, $pad] = self::DEFAULTS[$key] ?? throw new \InvalidArgumentException("Unknown sequence '{$key}'");
                $db->query('INSERT IGNORE INTO crm_number_sequences (seq_key, prefix, next_value, pad_length) VALUES (?,?,1,?)', [$key, $prefix, $pad]);
                $row = $db->query('SELECT prefix, next_value, pad_length FROM crm_number_sequences WHERE seq_key = ? FOR UPDATE', [$key])->getRowArray();
            }
            $n = (int) $row['next_value'];
            $db->query('UPDATE crm_number_sequences SET next_value = next_value + 1 WHERE seq_key = ?', [$key]);

            return rtrim((string) $row['prefix'], '-') . '-' . str_pad((string) $n, (int) $row['pad_length'], '0', STR_PAD_LEFT);
        });
    }
}
