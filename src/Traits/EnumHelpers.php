<?php

namespace Brights\ExchangeRates\Traits;

use Exception;

trait EnumHelpers
{
    /**
     * Return an array of enum names.
     *
     * @return string[]
     */
    public static function names(): array
    {
        return array_column(static::cases(), 'name');
    }

    /**
     * Return an array of enum values.
     *
     * @return mixed[]
     */
    public static function values(): array
    {
        return array_column(static::cases(), 'value');
    }

    /**
     * Returns an associative array of enum values to names.
     *
     * Example:
     * [
     *     'value1' => 'name1',
     *     'value2' => 'name2',
     *     ...
     * ]
     *
     * @return array
     */
    public static function array(): array
    {
        return array_combine(static::values(), static::names());
    }

    /**
     * Search for an enum value or name and return the matching case instance.
     *
     * @param string|int $type
     * @return static|null
     */
    public static function search($type): ?static
    {
        foreach (static::cases() as $case) {
            if ($case->value == $type || $case->name == $type) {
                return $case;
            }
        }
        return null;
    }

    /**
     * Magic method to allow static calling of enum cases as methods.
     *
     * This method enables the retrieval of enum case values by calling the case name
     * statically as a method. If the method name matches an enum case name, the corresponding
     * case value is returned. If no matching case is found, an Exception is thrown.
     *
     * @param string $method The name of the method being called, expected to match a case name.
     * @param array $args The arguments passed to the method, not used in this implementation.
     * @return mixed The value of the matching enum case.
     * @throws Exception If no matching enum case is found for the given method name.
     */
    public static function __callStatic($method, $args)
    {
        foreach (static::cases() as $case) {
            if ($case->name === $method) {
                return $case->value;
            }
        }
        $className = static::class;
        throw new Exception("[$className\EnumHelpers] Method [$method] does not exist.");
    }

    public static function valuesToString($separator = ','): string
    {
        return implode($separator, static::values());
    }

    public function displayName(): string
    {
        return ucwords(
            strtolower(
                str_replace(
                    '_',
                    ' ',
                    $this->value
                )
            )
        );
    }

}
