<?php

namespace Brights\ExchangeRates\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Psr\Http\Message\MessageInterface;

trait CollectableResponse
{
    /**
     * Collects the response from a PSR-7 compatible HTTP response and returns it as a Laravel Collection.
     *
     * @param Response $response The PSR-7 compatible HTTP response.
     * @return Collection The response body decoded as an array and wrapped in a Laravel Collection.
     */
    protected function collectResponse(Response $response): Collection
    {
        return collect(json_decode($response->getBody()->getContents(), true));
    }

    /**
     * Collects the request body from a PSR-7 compatible HTTP request and returns it as a Laravel Collection.
     *
     * @param Request $request The PSR-7 compatible HTTP request.
     * @return Collection The request body decoded as an array and wrapped in a Laravel Collection.
     */
    protected function collectRequest(Request $request): Collection
    {
        return collect(json_decode($request->getContent(), true));
    }

    /**
     * Collects the message body from a PSR-7 compatible HTTP message and returns it as a Laravel Collection.
     *
     * @param MessageInterface $message The PSR-7 compatible HTTP message.
     * @return Collection The message body decoded as an array and wrapped in a Laravel Collection.
     */
    protected function collectMessage(MessageInterface $message): Collection
    {
        return collect(json_decode($message->getBody(), true));
    }
}
