<?php
declare(strict_types=1);

function ecocart_promotions(): array
{
    return [
        'BIGBLOWOUT' => [
            'code' => 'BIGBLOWOUT',
            'label' => 'Big Blowout code',
            'rate' => 0.10,
        ],
    ];
}

function normalize_promo_code(?string $code): string
{
    return strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim((string) $code)) ?? '');
}

function promotion_for_code(?string $code): ?array
{
    $normalized = normalize_promo_code($code);

    return ecocart_promotions()[$normalized] ?? null;
}

function promotion_discount(float $subtotal, ?array $promotion): float
{
    if ($subtotal <= 0 || !$promotion) {
        return 0.0;
    }

    return round($subtotal * (float) $promotion['rate'], 2);
}
