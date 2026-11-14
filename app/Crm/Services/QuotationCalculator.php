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
}
