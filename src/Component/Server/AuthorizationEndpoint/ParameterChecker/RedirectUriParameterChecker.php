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

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint\ParameterChecker;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Core\Util\Uri;

final class RedirectUriParameterChecker implements ParameterChecker
{
    /**
     * @var bool
     */
    private $securedRedirectUriEnforced;

    /**
     * @var bool
     */
    private $redirectUriStorageEnforced;

    /**
     * RedirectUriParameterChecker constructor.
     *
     * @param bool $securedRedirectUriEnforced
     * @param bool $redirectUriStorageEnforced
     */
    public function __construct(bool $securedRedirectUriEnforced, bool $redirectUriStorageEnforced)
    {
        $this->securedRedirectUriEnforced = $securedRedirectUriEnforced;
        $this->redirectUriStorageEnforced = $redirectUriStorageEnforced;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Authorization $authorization, callable $next): Authorization
    {
        try {
            Assertion::true($authorization->hasQueryParam('redirect_uri'), 'The parameter "redirect_uri" is mandatory.');
            $redirectUri = $authorization->getQueryParam('redirect_uri');
            $this->checkRedirectUriHasNoFragment($redirectUri);
            $this->checkIfRedirectUriIsSecuredIfNeeded($redirectUri);
            $this->checkRedirectUriForTheClient($authorization->getClient(), $redirectUri, $authorization->getQueryParams());
            $authorization = $authorization->withRedirectUri($redirectUri);

            return $next($authorization);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
        }
    }

    /**
     * Check if a fragment is in the URI.
     *
     * @param string $redirectUri
     *
     * @see http://tools.ietf.org/html/rfc6749#section-3.1.2
     *
     * @throws \InvalidArgumentException
     */
    private function checkRedirectUriHasNoFragment($redirectUri)
    {
        $uri = parse_url($redirectUri);
        Assertion::false(isset($uri['fragment']), 'The parameter "redirect_uri" must not contain fragment');
    }

    /**
     * Check if the redirect URI is secured if the configuration requires it.
     *
     * @param string $redirectUri
     *
     * @see http://tools.ietf.org/html/rfc6749#section-3.1.2.1
     *
     * @throws \InvalidArgumentException
     */
    private function checkIfRedirectUriIsSecuredIfNeeded($redirectUri)
    {
        if (false === $this->isSecuredRedirectUriEnforced()) {
            return;
        }
        if (true === $this->isSecuredRedirectUriEnforced() && 'https' !== mb_substr($redirectUri, 0, 5, '8bit')) {
            Assertion::true($this->isALocalUriOrAnUrn($redirectUri), 'The parameter "redirect_uri" must be a secured URI.');
        }
    }

    /**
     * Redirection to an URN or a local host is allowed if the https is required.
     *
     * @param string $redirectUri
     *
     * @return bool
     */
    private function isALocalUriOrAnUrn(string $redirectUri): bool
    {
        $parsed = parse_url($redirectUri);

        return array_key_exists('scheme', $parsed) && array_key_exists('host', $parsed) && 'http' === $parsed['scheme'] && in_array($parsed['host'], ['[::1]', '127.0.0.1']);
    }

    /**
     * @param Client $client
     * @param string $redirectUri
     * @param array  $queryParameters
     */
    public function checkRedirectUriForTheClient(Client $client, $redirectUri, array $queryParameters)
    {
        $client_redirect_uris = $this->getClientRedirectUris($client, $queryParameters);

        Assertion::false(!empty($client_redirect_uris) && false === Uri::isRedirectUriAllowed($redirectUri, $client_redirect_uris), 'The specified redirect URI is not valid.');
    }

    /**
     * Check if the redirect URIs stored by the client.
     *
     * @param Client $client
     * @param array  $queryParameters
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     *
     * @see http://tools.ietf.org/html/rfc6749#section-3.1.2.2
     */
    private function getClientRedirectUris(Client $client, array $queryParameters): array
    {
        if (!$client->has('redirect_uris') || empty($redirectUris = $client->get('redirect_uris'))) {
            Assertion::false($this->isRedirectUriStorageEnforced(), 'Clients must register at least one redirect URI.');
            Assertion::false($client->isPublic(), 'Non-confidential clients must register at least one redirect URI.');
            Assertion::false(!$client->isPublic() && array_key_exists('response_type', $queryParameters) && 'token' === $queryParameters['response_type'], 'Confidential clients must register at least one redirect URI when using "token" response type.');

            return [];
        }

        return $redirectUris;
    }

    /**
     * @return bool
     */
    private function isSecuredRedirectUriEnforced(): bool
    {
        return $this->securedRedirectUriEnforced;
    }

    /**
     * @return bool
     */
    private function isRedirectUriStorageEnforced(): bool
    {
        return $this->redirectUriStorageEnforced;
    }
}
