<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\Rule;

use Assert\Assertion;
use function League\Uri\parse;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class SectorIdentifierUriRule implements Rule
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    public function __construct(RequestFactoryInterface $requestFactory, ClientInterface $client)
    {
        $this->requestFactory = $requestFactory;
        $this->client = $client;
    }

    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, RuleHandler $next): DataBag
    {
        $validatedParameters = $next->handle($clientId, $commandParameters, $validatedParameters);

        if ($commandParameters->has('sector_identifier_uri')) {
            $redirectUris = $validatedParameters->has('redirect_uris') ? $validatedParameters->get('redirect_uris') : [];
            $this->checkSectorIdentifierUri($commandParameters->get('sector_identifier_uri'), $redirectUris);
            $validatedParameters->set('sector_identifier_uri', $commandParameters->get('sector_identifier_uri'));
        }

        return $validatedParameters;
    }

    private function checkSectorIdentifierUri(string $url, array $redirectUris): void
    {
        $data = parse($url);
        Assertion::eq('https', $data['scheme'], \Safe\sprintf('The sector identifier URI "%s" is not valid.', $url));
        Assertion::notEmpty($data['host'], \Safe\sprintf('The sector identifier URI "%s" is not valid.', $url));

        $request = $this->requestFactory->createRequest('GET', $url);
        $response = $this->client->sendRequest($request);
        Assertion::eq(200, $response->getStatusCode(), \Safe\sprintf('Unable to get Uris from the Sector Identifier Uri "%s".', $url));

        $body = $response->getBody()->getContents();
        $data = \Safe\json_decode($body, true);
        Assertion::isArray($data, 'The provided sector identifier URI is not valid: it must contain at least one URI.');
        Assertion::notEmpty($data, 'The provided sector identifier URI is not valid: it must contain at least one URI.');

        $diff = \array_diff($redirectUris, $data);
        Assertion::noContent($diff, 'The provided sector identifier URI is not valid: it must contain at least the redirect URI(s) set in the registration request.');
    }
}
