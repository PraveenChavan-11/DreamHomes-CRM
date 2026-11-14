<?php

namespace Crm\Services;

/**
 * Pure, DB-free quotation maths (bcmath). Independently re-run on the server for every save; client values are never trusted.
 * line: subtotal = qty × price; discount = pct ? subtotal×pct/100 : amount (capped at subtotal); taxable = subtotal − discount; tax = taxable × rate.
 * charges: amount + optional tax. Grand total = taxable + charges + tax, optionally rounded to the nearest rupee (round_off shown separately).
 */
final class QuotationCalculator
{
    /**
     * @param list<array{quantity:string|int|float,unit_price:string|int|float,discount_pct?:string|int|float,discount_amount?:string|int|float,tax_rate:string|int|float}> $lines
     * @param list<array{name:string,amount:string|int|float,tax_rate?:string|int|float}> $charges
     * @return array{lines:list<array<string,string>>,charges:list<array<string,string>>,subtotal:string,discount_total:string,taxable_total:string,tax_total:string,charges_total:string,round_off:string,grand_total:string}
     */
    public static function compute(array $lines, array $charges = [], bool $roundOff = true): array
    {
        $out = ['lines' => [], 'charges' => []];
        $sub = $disc = $taxable = $tax = $chg = '0.00';

        foreach ($lines as $i => $l) {
            $qty = Money::norm($l['quantity']);
            $pr  = Money::norm($l['unit_price']);
            if (Money::cmp($qty, '0') <= 0) {
                throw new CrmException('Line ' . ($i + 1) . ': quantity must be greater than zero.');
            }
            if (Money::cmp($pr, '0') < 0) {
                throw new CrmException('Line ' . ($i + 1) . ': unit price cannot be negative.');
            }
            $lineSub = Money::round(Money::mul($qty, $pr));
            $pct     = Money::norm($l['discount_pct'] ?? 0);
            if (Money::cmp($pct, '0') < 0 || Money::cmp($pct, '100') > 0) {
                throw new CrmException('Line ' . ($i + 1) . ': discount % must be between 0 and 100.');
            }
            $d = Money::cmp($pct, '0') > 0 ? Money::pct($lineSub, $pct) : Money::round(Money::norm($l['discount_amount'] ?? 0));
            if (Money::cmp($d, '0') < 0) {
                throw new CrmException('Line ' . ($i + 1) . ': discount cannot be negative.');
            }
            if (Money::cmp($d, $lineSub) > 0) {
                $d = $lineSub;
            }
            $lineTaxable = Money::round(Money::sub($lineSub, $d));
            $rate        = Money::norm($l['tax_rate']);
            $lineTax     = Money::pct($lineTaxable, $rate);
            $out['lines'][] = [
                'quantity' => Money::round($qty, 3), 'unit_price' => Money::round($pr), 'discount_pct' => Money::round($pct, 3), 'discount_amount' => $d,
                'line_subtotal' => $lineSub, 'taxable_amount' => $lineTaxable, 'tax_rate' => Money::round($rate, 3), 'tax_amount' => $lineTax,
                'line_total' => Money::round(Money::add($lineTaxable, $lineTax)),
            ];
            $sub     = Money::round(Money::add($sub, $lineSub));
            $disc    = Money::round(Money::add($disc, $d));
            $taxable = Money::round(Money::add($taxable, $lineTaxable));
            $tax     = Money::round(Money::add($tax, $lineTax));
        }
        foreach ($charges as $c) {
            $amt = Money::round(Money::norm($c['amount']));
            if (Money::cmp($amt, '0') < 0) {
                throw new CrmException('Additional charge amounts cannot be negative.');
            }
            $r   = Money::norm($c['tax_rate'] ?? 0);
            $ct  = Money::pct($amt, $r);
            $out['charges'][] = ['name' => $c['name'], 'amount' => $amt, 'tax_rate' => Money::round($r, 3), 'tax_amount' => $ct];
            $chg = Money::round(Money::add($chg, $amt));
            $tax = Money::round(Money::add($tax, $ct));
        }
        $gross = Money::round(Money::add(Money::add($taxable, $chg), $tax));
        $grand = $gross;
        $ro    = '0.00';
        if ($roundOff) {
            // nearest rupee, half up, exact bcmath: floor(gross + 0.5)
            $whole = bcadd($gross, '0.5', 2);
            $grand = bcadd(explode('.', $whole)[0], '0', 2);
            $ro    = Money::round(Money::sub($grand, $gross));
        }

        return $out + [
            'subtotal' => $sub, 'discount_total' => $disc, 'taxable_total' => $taxable, 'tax_total' => $tax, 'charges_total' => $chg, 'round_off' => $ro, 'grand_total' => $grand,
        ];
    }

    /**
     * Manual LINE-TOTAL override: find the unit price (and, when the line has no % discount, a tiny explicit rounding discount) so the line's total
     * (taxable + tax, i.e. what the customer sees) equals $target to the paisa. A unit price only has 2 decimals, so the exact quotient is rounded,
     * the neighbouring paise are searched, and any remainder goes into `discount_amount` (shown on the line). With a % discount the line cannot absorb
     * a rounding discount, so the closest achievable total is returned and `variance` says by how much it differs. Deterministic.
     * @return array{unit_price:string,discount_amount:string,line_total:string,variance:string}
     */
    public static function solveUnitPrice(string $qty, string $discountPct, string $discountAmount, string $taxRate, string $target): array
    {
        $qty = Money::norm($qty);
        $t   = Money::norm($target);
        if (Money::cmp($qty, '0') <= 0) {
            throw new CrmException('Quantity must be greater than zero to fix a line total.');
        }
        if (Money::cmp($t, '0') < 0) {
            throw new CrmException('Line total cannot be negative.');
        }
        $factor    = bcadd('1', bcdiv(Money::norm($taxRate), '100', 8), 8);
        $netTarget = bcdiv($t, $factor, 8);                       // taxable amount needed
        $pct       = Money::norm($discountPct);
        $amtIn     = Money::round(Money::norm($discountAmount));
        $usePct    = Money::cmp($pct, '0') > 0;
        if ($usePct) {
            $keep = bcsub('1', bcdiv($pct, '100', 8), 8);
            $sub  = Money::cmp($keep, '0') > 0 ? bcdiv($netTarget, $keep, 8) : '0';
        } else {
            $sub = bcadd($netTarget, $amtIn, 8);
        }
        $base = Money::round(bcdiv($sub, $qty, 8));
        $best = null;
        $eval = static function (string $p, string $d) use ($qty, $pct, $taxRate, $t, &$best): void {
            $r  = self::compute([['quantity' => $qty, 'unit_price' => $p, 'discount_pct' => $pct, 'discount_amount' => $d, 'tax_rate' => $taxRate]], [], false)['lines'][0]['line_total'];
            $df = ltrim(bcsub($r, $t, 2), '-');
            $k  = [$df, $d, $p];
            if ($best === null || bccomp($df, $best['d'], 2) < 0 || (bccomp($df, $best['d'], 2) === 0 && (bccomp($d, $best['a'], 2) < 0 || (bccomp($d, $best['a'], 2) === 0 && bccomp($p, $best['p'], 2) < 0)))) {
                $best = ['p' => $p, 'a' => $d, 'r' => $r, 'd' => $df];
            }
        };
        for ($k = -5; $k <= 5; $k++) {
            $p = bcadd($base, bcdiv((string) $k, '100', 2), 2);
            if (Money::cmp($p, '0') < 0) {
                continue;
            }
            if ($usePct) {
                $eval($p, $amtIn);
                continue;
            }
            $lineSub = Money::round(Money::mul($qty, $p));
            $d0      = Money::round(Money::sub($lineSub, $netTarget));
            for ($j = -2; $j <= 2; $j++) {
                $d = bcadd($d0, bcdiv((string) $j, '100', 2), 2);
                if (Money::cmp($d, $amtIn) < 0 || Money::cmp($d, $lineSub) > 0) {
                    continue;
                }
                $eval($p, $d);
            }
        }
        if ($best === null) {
            throw new CrmException('Could not fix that line total for this quantity and tax rate.');
        }

        return ['unit_price' => $best['p'], 'discount_amount' => $best['a'], 'line_total' => $best['r'], 'variance' => $best['d']];
    }

    /**
     * Negotiated GRAND-TOTAL target: apply the smallest uniform extra discount (on top of existing line discounts) so the grand total lands on
     * $target, then fine-tune the largest line paise-by-paise. Reduction only. Each line becomes amount-based so the stored figures reproduce the total.
     * @param list<array<string,mixed>> $lines same shape as compute()
     * @return array{lines:list<array<string,mixed>>,adjustment:string,grand_total:string,variance:string}
     */
    public static function fitGrandTotal(array $lines, array $charges, bool $roundOff, string $target): array
    {
        $target = Money::round(Money::norm($target));
        $base   = self::compute($lines, $charges, $roundOff);
        if (Money::cmp($target, '0') <= 0) {
            throw new CrmException('Negotiated total must be greater than zero.');
        }
        if (Money::cmp($target, $base['grand_total']) > 0) {
            throw new CrmException('Negotiated total (' . Money::fmt($target) . ') cannot exceed the computed total (' . Money::fmt($base['grand_total']) . '). Raise prices on the lines instead.');
        }
        $amountLines = [];
        foreach ($lines as $i => $l) {
            $amountLines[$i] = ['quantity' => $l['quantity'], 'unit_price' => $l['unit_price'], 'discount_pct' => '0', 'discount_amount' => $base['lines'][$i]['discount_amount'], 'tax_rate' => $l['tax_rate']];
        }
        $apply = static function (string $x, array $extraTweak = []) use ($amountLines, $base, $charges, $roundOff) {
            $ls = $amountLines;
            foreach ($ls as $i => $l) {
                $extra = Money::pct($base['lines'][$i]['taxable_amount'], $x);
                $ls[$i]['discount_amount'] = Money::round(Money::add($l['discount_amount'], $extra));
                if (isset($extraTweak[$i])) {
                    $ls[$i]['discount_amount'] = Money::round(Money::add($ls[$i]['discount_amount'], $extraTweak[$i]));
                }
            }

            return [$ls, self::compute($ls, $charges, $roundOff)];
        };
        $lo = '0';
        $hi = '100';
        for ($n = 0; $n < 40; $n++) {
            $mid = bcdiv(bcadd($lo, $hi, 8), '2', 8);
            [, $c] = $apply($mid);
            if (bccomp($c['grand_total'], $target, 2) >= 0) {
                $lo = $mid;
            } else {
                $hi = $mid;
            }
        }
        [$ls, $c] = $apply($lo);
        $best = ['ls' => $ls, 'c' => $c, 'd' => ltrim(bcsub($c['grand_total'], $target, 2), '-')];
        if (Money::cmp($best['d'], '0') !== 0 && $lines) {
            $big = 0;
            foreach ($base['lines'] as $i => $bl) {
                if (bccomp($bl['taxable_amount'], $base['lines'][$big]['taxable_amount'], 2) > 0) {
                    $big = $i;
                }
            }
            $need = bcsub($c['grand_total'], $target, 2);        // positive: still above target
            $approx = bcdiv($need, bcadd('1', bcdiv(Money::norm($lines[$big]['tax_rate']), '100', 8), 8), 2);
            for ($k = -150; $k <= 150; $k++) {
                $tw = bcadd($approx, bcdiv((string) $k, '100', 2), 2);
                if (bccomp($tw, '0', 2) < 0) {
                    continue;
                }
                [$ls2, $c2] = $apply($lo, [$big => $tw]);
                if (bccomp($ls2[$big]['discount_amount'], $base['lines'][$big]['line_subtotal'], 2) > 0) {
                    continue;
                }
                $d2 = ltrim(bcsub($c2['grand_total'], $target, 2), '-');
                if (bccomp($d2, $best['d'], 2) < 0) {
                    $best = ['ls' => $ls2, 'c' => $c2, 'd' => $d2];
                }
                if (Money::cmp($d2, '0') === 0) {
                    break;
                }
            }
        }

        return ['lines' => $best['ls'], 'adjustment' => Money::round(Money::sub($best['c']['discount_total'], $base['discount_total'])), 'grand_total' => $best['c']['grand_total'], 'variance' => $best['d'], 'calc' => $best['c'], 'discount_added' => Money::round(Money::sub($best['c']['discount_total'], $base['discount_total']))];
    }
}
