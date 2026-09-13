<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Data;

final readonly class CoinPack
{
    public function __construct(
        public string $key,
        public string $name,
        public int $price,
        public int $coins,
        public int $bonusCoins,
    ) {}

    /**
     * @param  array{name: string, price: int|numeric-string, coins: int|numeric-string, bonus_coins: int|numeric-string}  $config
     */
    public static function fromConfig(string $key, array $config): self
    {
        return new self(
            key: $key,
            name: (string) $config['name'],
            price: (int) $config['price'],
            coins: (int) $config['coins'],
            bonusCoins: (int) $config['bonus_coins'],
        );
    }

    public function totalCoins(): int
    {
        return $this->coins + $this->bonusCoins;
    }

    public function priceInKobo(): int
    {
        return $this->price * 100;
    }
}
