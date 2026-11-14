<?php

namespace Crm\Services;

/** Decimal-safe money maths (bcmath strings only — never floats). Rounding: half away from zero, 2 dp. */
final class Money
{
    public static function norm(string|int|float|null $v): string
    {
        if ($v === null || $v === '') {
            return '0';
        }
        $s = is_float($v) ? number_format($v, 6, '.', '') : trim((string) $v);
        $s = str_replace(',', '', $s);

        return preg_match('/^-?\d+(\.\d+)?$/', $s) ? $s : '0';
    }

    public static function round(string $v, int $scale = 2): string
    {
        $v   = self::norm($v);
        $adj = '0.' . str_repeat('0', $scale) . '5';

        return str_starts_with($v, '-') ? bcsub($v, $adj, $scale) : bcadd($v, $adj, $scale);
    }

    public static function add(string $a, string $b): string { return bcadd(self::norm($a), self::norm($b), 6); }
    public static function sub(string $a, string $b): string { return bcsub(self::norm($a), self::norm($b), 6); }
    public static function mul(string $a, string $b): string { return bcmul(self::norm($a), self::norm($b), 6); }
    public static function div(string $a, string $b): string { return bccomp(self::norm($b), '0', 6) === 0 ? '0' : bcdiv(self::norm($a), self::norm($b), 6); }
    public static function cmp(string $a, string $b): int { return bccomp(self::norm($a), self::norm($b), 2); }
    public static function pct(string $amount, string $pct): string { return self::round(bcdiv(bcmul(self::norm($amount), self::norm($pct), 6), '100', 6)); }
    public static function fmt(string|float|null $v): string { return number_format((float) self::norm($v), 2, '.', ','); }
}
