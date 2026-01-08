<?php

namespace App\Traits;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;
use ValueError;

trait EnumToArray
{
    public static function names(): array
    {
        return array_map(fn (mixed $name): mixed => self::translateName($name), array_column(self::cases(), 'name'));
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @param  self[]  $items
     */
    public static function fromItems(array $items): array
    {
        throw_if($items === [], new InvalidArgumentException);

        $enums = [];

        foreach ($items as $item) {
            $enums[$item->value] = self::translateName($item->value);
        }

        return $enums;
    }

    public static function array(): array
    {
        return array_combine(self::values(), self::names());
    }

    public static function associativeValueArray(): array
    {
        return array_combine(self::values(), self::values());
    }

    public static function associativeArray(?string $suffix = null): array
    {
        $values = array_map(static fn (string $value): string => $value.$suffix, self::values());

        return array_combine(self::names(), $values);
    }

    public static function fromName(string $name): ?self
    {
        try {
            foreach (self::cases() as $case) {
                if ($name === $case->name) {
                    return $case;
                }
            }
            throw new ValueError("$name is not a valid enum name ".self::class);
        } catch (Throwable $e) {
            Log::error('Error in Enum::fromName() - '.$e->getMessage());

            return null;
        }
    }

    /**
     * @return self[]
     */
    public static function fromValues(array $values): array
    {
        $enums = [];
        $values = array_unique($values);

        try {
            foreach (self::cases() as $case) {
                if (\in_array($case->value, $values, true)) {
                    $enums[] = $case;
                }
            }

            throw_if(count($values) !== count($enums), new ValueError('Some values are not valid enum values '.self::class));

            return $enums;
        } catch (Throwable $e) {
            Log::error('Error in Enum::fromValues() - '.$e->getMessage());

            return $enums;
        }
    }

    public static function sqlFormat(): string
    {
        return sprintf("('%s')", implode("','", self::associativeArray()));
    }

    private static function translateName(mixed $name): mixed
    {
        return Lang::has('enums.'.$name) ? __('enums.'.$name) : $name;
    }
}
