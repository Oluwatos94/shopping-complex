<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Data;

use Carbon\CarbonImmutable;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;

final readonly class ContactLinkPayload
{
    public function __construct(
        public int $vendorId,
        public ViewSourceEnum $source,
        public ?string $buyerIdentity,
        public ?string $prefilledMessage,
        public ?CarbonImmutable $issuedAt,
    ) {}
}
