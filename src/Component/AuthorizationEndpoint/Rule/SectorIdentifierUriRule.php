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
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use function League\Uri\parse;

final class SectorIdentifierUriRule implements Rule
{
    private $client;

    private $requestFactory;

    public function __construct(RequestFactory $requestFactory, HttpClient $client)
    {
        $this->requestFactory = $requestFactory;
        $this->client = $client;
    }

    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        $validatedParameters = $next($clientId, $commandParameters, $validatedParameters);

        if ($commandParameters->has('sector_identifier_uri')) {
            $redirectUris = $validatedParameters->has('redirect_uris') ? $validatedParameters->get('redirect_uris') : [];
            $this->checkSectorIdentifierUri($commandParameters->get('sector_identifier_uri'), $redirectUris);
            $validatedParameters->with('sector_identifier_uri', $commandParameters->get('sector_identifier_uri'));
        }

        return $validatedParameters;
    }

    private function checkSectorIdentifierUri(string $url, array $redirectUris): void
    {
        $data = parse($url);

        if ('https' !== $data['scheme'] || null === $data['host']) {
            throw new \InvalidArgumentException(\sprintf('The sector identifier URI "%s" is not valid.', $url));
        }

        $request = $this->requestFactory->createRequest('GET', $url);
        $response = $this->client->sendRequest($request);
        if (200 !== $response->getStatusCode()) {
            throw new \InvalidArgumentException(\sprintf('Unable to get Uris from the Sector Identifier Uri "%s".', $url));
        }

        $body = $response->getBody()->getContents();
        $data = \json_decode($body, true);
        if (!\is_array($data) || empty($data)) {
            throw new \InvalidArgumentException('The provided sector identifier URI is not valid: it must contain at least one URI.');
        }

        $diff = \array_diff($redirectUris, $data);
        if (!empty($diff)) {
            throw new \InvalidArgumentException('The provided sector identifier URI is not valid: it must contain at least the redirect URI(s) set in the registration request.');
        }
    }
}
