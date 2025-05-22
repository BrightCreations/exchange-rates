<?php

namespace BrightCreations\ExchangeRates\Enums;

use BrightCreations\ExchangeRates\Traits\EnumHelpers;

/**
 * ExchangeRateProvidersEnum
 * 
 * PDO, CONFIGURABLE providers
 * @see https://github.com/brick/money?tab=readme-ov-file#configurableprovider
 */
enum ExchangeRateProvidersEnum: string
{
    use EnumHelpers;

    case CONFIGURABLE = "CONFIGURABLE";
    case PDO = "PDO";
}
