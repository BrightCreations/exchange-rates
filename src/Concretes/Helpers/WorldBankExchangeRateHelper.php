<?php

namespace BrightCreations\ExchangeRates\Concretes\Helpers;

use Illuminate\Support\Collection;

/**
 * WorldBankExchangeRateHelper is a helper trait that provides methods to help with World Bank Exchange Rate API.
 * 
 * @package BrightCreations\ExchangeRates\Concretes\Helpers
 * @author Bright Creations <kareem.shaaban@brightcreations.com>
 * @license MIT
 * 
 * Example response for `GET https://api.worldbank.org/v2/country/all/indicator/PA.NUS.FCRF?date=2024&format=json&per_page=1000` endpoint:
 * 
 * ```json
[
    {
        "page": 1,
        "pages": 266,
        "per_page": 1,
        "total": 266,
        "sourceid": "2",
        "lastupdated": "2025-10-07"
    },
    [
        {
            "indicator": {
                "id": "PA.NUS.FCRF",
                "value": "Official exchange rate (LCU per US$, period average)"
            },
            "country": {
                "id": "ZH",
                "value": "Africa Eastern and Southern"
            },
            "countryiso3code": "AFE",
            "date": "2024",
            "value": null,
            "unit": "",
            "obs_status": "",
            "decimal": 2
        }
    ]
]
 * ```
 */
trait WorldBankExchangeRateHelper
{

    protected function get

    public function extractExchangeRatesForCurrency(string $currency_code, Collection $response)
    {

    }

}
