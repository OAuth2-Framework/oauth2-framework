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
use Http\Message\ResponseFactory;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

class SectorIdentifierUriRule implements Rule
{
    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * SectorIdentifierUriRule constructor.
     *
     * @param ResponseFactory $responseFactory
     * @param HttpClient      $client
     */
    public function __construct(ResponseFactory $responseFactory, HttpClient $client)
    {
        $this->responseFactory = $responseFactory;
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if ($commandParameters->has('sector_identifier_uri')) {
            Assertion::url($commandParameters->get('sector_identifier_uri'), sprintf('The sector identifier URI "%s" is not valid.', $commandParameters->get('sector_identifier_uri')));
            $this->checkSectorIdentifierUri($commandParameters->get('sector_identifier_uri'));
            $validatedParameters = $validatedParameters->with('sector_identifier_uri', $commandParameters->get('sector_identifier_uri'));
        }

        return $next($clientId, $commandParameters, $validatedParameters);
    }

    /**
     * @param string $url
     *
     * @throws \InvalidArgumentException
     */
    private function checkSectorIdentifierUri(string $url)
    {
        $allowedProtocols = ['https'];
        Assertion::inArray(mb_substr($url, 0, mb_strpos($url, '://', 0, '8bit'), '8bit'), $allowedProtocols, sprintf('The provided sector identifier URI is not valid: scheme must be one of the following: %s.', implode(', ', $allowedProtocols)));
        $request = $this->responseFactory->createRequest('GET', $url);
        $response = $this->client->sendRequest($request);
        Assertion::eq(200, $response->getStatusCode(), sprintf('Unable to get Uris from the Sector Identifier Uri "%s".', $url));

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        Assertion::isArray($data, 'The provided sector identifier URI is not valid: bad response.');
        Assertion::notEmpty($data, 'The provided sector identifier URI is not valid: it must contain at least one URI.');
        foreach ($data as $sector_url) {
            Assertion::url($sector_url, 'The provided sector identifier URI is not valid: it must contain only URIs.');
            Assertion::inArray(mb_substr($sector_url, 0, mb_strpos($sector_url, '://', 0, '8bit'), '8bit'), $allowedProtocols, sprintf('An URL provided in the sector identifier URI is not valid: scheme must be one of the following: %s.', implode(', ', $allowedProtocols)));
        }
    }
}
