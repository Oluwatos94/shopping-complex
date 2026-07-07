<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services\Payments\Stellar;

/**
 * A Stellar keypair the platform can sign with. Decouples {@see AnchorClient} from where
 * the key comes from — the Jiidaa platform wallet (config) for Anchor deposits, or a
 * vendor's custodial wallet.
 */
final readonly class StellarSigner
{
    public function __construct(
        public string $publicKey,
        public string $secret,
    ) {}
}
