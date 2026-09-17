<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use ModulesShoppingComplex\Billing\Data\CoinPack;

final class CoinPackRegistry
{
    /**
     * @return array<string, CoinPack>
     */
    public function all(): array
    {
        $packs = [];

        /** @var array<string, array{name: string, price: int, coins: int, bonus_coins: int}> $configured */
        $configured = config('billing.coins.packs', []);

        foreach ($configured as $key => $config) {
            $packs[$key] = CoinPack::fromConfig($key, $config);
        }

        return $packs;
    }

    public function find(string $key): ?CoinPack
    {
        return $this->all()[$key] ?? null;
    }
}
