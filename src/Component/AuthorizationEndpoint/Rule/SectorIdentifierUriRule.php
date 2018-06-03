<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\Rule;

use Http\Client\HttpClient;
use Http\Message\RequestFactory;
use function League\Uri\parse;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class SectorIdentifierUriRule implements Rule
{
    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * SectorIdentifierUriRule constructor.
     *
     * @param RequestFactory $requestFactory
     * @param HttpClient     $client
     */
    public function __construct(RequestFactory $requestFactory, HttpClient $client)
    {
        $this->requestFactory = $requestFactory;
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if ($commandParameters->has('sector_identifier_uri')) {
            $this->checkSectorIdentifierUri($commandParameters->get('sector_identifier_uri'));
            $validatedParameters = $validatedParameters->with('sector_identifier_uri', $commandParameters->get('sector_identifier_uri'));
        }

        return $next($clientId, $commandParameters, $validatedParameters);
    }

    private function checkSectorIdentifierUri(string $url)
    {
        $data = parse($url);

        if ('https' !== $data['scheme'] || null === $data['host']) {
            throw new \InvalidArgumentException(sprintf('The sector identifier URI "%s" is not valid.', $url));
        }

        $request = $this->requestFactory->createRequest('GET', $url);
        $response = $this->client->sendRequest($request);
        if (200 !== $response->getStatusCode()) {
            throw new \InvalidArgumentException(sprintf('Unable to get Uris from the Sector Identifier Uri "%s".', $url));
        }

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        if (!is_array($data) || empty($data)) {
            throw new \InvalidArgumentException('The provided sector identifier URI is not valid: it must contain at least one URI.');
        }
    }
}
