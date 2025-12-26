<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\QueryBuilder\AllowedFilter;

abstract class BasePageRepository
{
    protected const SEARCH_LIMIT = 100;

    protected const SMALL_SEARCH_LIMIT = 30;

    protected string $arrayValueDelimiterForFilters = '||||';

    public function createEnumFilter(string $filterName, string $column, string $enumClass): AllowedFilter
    {
        return AllowedFilter::callback($filterName, static function (Builder $query, string|int $value) use ($column, $enumClass): void {
            throw_unless(method_exists($enumClass, 'fromValues'), new RuntimeException("Method 'fromValues' does not exist on {$enumClass}"));

            $enums = $enumClass::fromValues([$value]);

            throw_if(empty($enums), new RuntimeException("No matching enum found for value '{$value}' in {$enumClass}."));

            $enum = $enums[0]; // Use the first enum from the array

            throw_if(! is_object($enum) || ! property_exists($enum, 'value'), new RuntimeException("Enum class {$enumClass} must have a 'value' property."));

            $query->where($column, $enum->value);
        });
    }

    protected function getEnablingFilter(): AllowedFilter
    {
        return AllowedFilter::callback('enabling', static function (Builder $query, string $value): void {
            if (! in_array($value, ['enabled', 'disabled'], true)) {
                return;
            }

            $query->where('enabled', $value === 'enabled');
        });
    }

    protected function getDateRangeFilter(string $column): AllowedFilter
    {
        return AllowedFilter::callback($column, static function (Builder $query, mixed $value) use ($column): void {
            if (
                ! is_array($value) || count($value) !== 2 || ! DateHelper::isValidDateFormat($value[0]) || ! DateHelper::isValidDateFormat($value[1])
            ) {
                return;
            }

            $query->whereBetween(DB::raw("DATE($column)"), $value);
        });
    }
}
