<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Models;

use Illuminate\Database\Eloquent\Builder;
use ModulesShoppingComplex\Billing\Exceptions\LedgerIsAppendOnlyException;

/**
 * Model events only fire on hydrated instances, so a bulk update() or delete()
 * walks straight past them. This closes that path for ledger tables.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends Builder<TModel>
 */
final class AppendOnlyBuilder extends Builder
{
    /** {@inheritdoc} */
    public function update(array $values)
    {
        throw $this->refuse('updated');
    }

    /** {@inheritdoc} */
    public function upsert(array $values, $uniqueBy, $update = null)
    {
        throw $this->refuse('updated');
    }

    /** {@inheritdoc} */
    public function increment($column, $amount = 1, array $extra = [])
    {
        throw $this->refuse('updated');
    }

    /** {@inheritdoc} */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        throw $this->refuse('updated');
    }

    /** {@inheritdoc} */
    public function delete()
    {
        throw $this->refuse('deleted');
    }

    /** {@inheritdoc} */
    public function forceDelete()
    {
        throw $this->refuse('deleted');
    }

    private function refuse(string $verb): LedgerIsAppendOnlyException
    {
        return new LedgerIsAppendOnlyException(
            $this->getModel()->getTable()." is append-only; rows cannot be {$verb}."
        );
    }
}
