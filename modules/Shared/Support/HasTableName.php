<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Shared\Support;

trait HasTableName
{
    /** {@inheritdoc} */
    final public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public static function getTableName(): string
    {
        return (new static)->getTable();
    }
}
