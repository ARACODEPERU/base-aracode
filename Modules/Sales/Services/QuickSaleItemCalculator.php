<?php

namespace Modules\Sales\Services;

use Modules\Sales\Support\ElectronicDiscountMode;

class QuickSaleItemCalculator
{
    /**
     * Calcula montos de un ítem gravado (afe_igv 10).
     *
     * @return array<string, mixed>
     */
    public function calculateTaxedLine(
        array $produc,
        float $percentageIgv,
        float $porcentageIcbper,
        string $discountMode = ElectronicDiscountMode::ITEM_DISCOUNT
    ): array {
        $priceSale = (float) $produc['unit_price'];
        $quantity = (float) $produc['quantity'];
        $discount = min((float) ($produc['discount'] ?? 0), $priceSale);
        $nfactorIgv = round(($percentageIgv / 100) + 1, 2);
        $ifactorIgv = round($percentageIgv / 100, 2);

        $valueUnit = 0;
        $igv = 0;
        $icbper = 0;
        $valueSale = 0;
        $totalItem = 0;
        $mtoDiscount = 0;
        $mtoBaseIgv = 0;
        $effectivePriceSale = $priceSale;
        $unitPrice = $priceSale;
        $arrayDiscounts = [];

        if (($produc['afe_igv'] ?? '10') == '10') {
            if ($discountMode === ElectronicDiscountMode::ITEM_DISCOUNT) {
                $valueUnit = round($priceSale / $nfactorIgv, 2);
                $base = round($valueUnit * $quantity, 2);
                $factor = $priceSale > 0 ? (($discount * 100) / $priceSale) / 100 : 0;
                $descuentoMonto = $factor * $valueUnit * $quantity;
                $mtoBaseIgv = ($valueUnit * $quantity) - $descuentoMonto;
                $igv = $mtoBaseIgv * $ifactorIgv;
                $totalItem = (($valueUnit * $quantity) - $descuentoMonto) + $igv;
                $valueSale = ($valueUnit * $quantity) - $descuentoMonto;

                if ($discount > 0) {
                    $unitPrice = round(($valueSale + $igv) / $quantity, 2);
                    $arrayDiscounts[0] = [
                        'value' => $discount,
                        'type' => '00',
                        'base' => round($base, 2),
                        'factor' => $factor,
                        'monto' => round($descuentoMonto, 2),
                    ];
                }

                $mtoDiscount = round($descuentoMonto, 2);
            } else {
                $effectivePriceSale = max(0, $priceSale - $discount);
                $valueUnit = round($effectivePriceSale / $nfactorIgv, 2);
                $mtoBaseIgv = round($valueUnit * $quantity, 2);
                $igv = $mtoBaseIgv * $ifactorIgv;
                $valueSale = $mtoBaseIgv;
                $totalItem = $valueSale + $igv;
                $unitPrice = $effectivePriceSale;
            }
        }

        $porcentageItemIcbper = 0;
        if (($produc['icbper'] ?? 0) == 1) {
            $porcentageItemIcbper = $porcentageIcbper;
            $icbper = $quantity * $porcentageItemIcbper;
        }

        $totalTax = $igv + $icbper;

        return [
            'value_unit' => round($valueUnit, 2),
            'igv' => round($igv, 2),
            'icbper' => round($icbper, 2),
            'porcentage_item_icbper' => $porcentageItemIcbper,
            'value_sale' => round($valueSale, 2),
            'total_item' => round($totalItem, 2),
            'mto_base_igv' => round($mtoBaseIgv, 2),
            'mto_discount' => round($mtoDiscount, 2),
            'unit_price' => round($unitPrice, 2),
            'price_sale' => round($discountMode === ElectronicDiscountMode::ITEM_DISCOUNT ? $priceSale : $effectivePriceSale, 2),
            'discount_unit' => round($discount, 2),
            'discount_mode' => $discountMode,
            'total_tax' => round($totalTax, 2),
            'array_discounts' => $arrayDiscounts,
            'line_total' => round($unitPrice * $quantity, 2),
        ];
    }
}
