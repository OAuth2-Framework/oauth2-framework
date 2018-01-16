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

namespace OAuth2Framework\Component\Server\Core\Client\Rule;

use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;

/**
If there are multiple hostnames in the registered redirect_uris and pairwise ID is set, the Client MUST register a sector_identifier_uri.
 */
final class RedirectionUriRule implements Rule
{
    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        $validatedParameters = $next($clientId, $commandParameters, $validatedParameters);

        // No need for redirect URIs as no response type to is used.
        if (!$validatedParameters->has('response_types') || empty($validatedParameters->get('response_types'))) {
            return $validatedParameters;
        }

        if (!$validatedParameters->has('token_endpoint_auth_method')) {
            throw new \InvalidArgumentException('Unable to determine the token endpoint authentication method.');
        }
        $is_client_public = 'none' === $validatedParameters->get('token_endpoint_auth_method');

        $application_type = $validatedParameters->has('application_type') ? $validatedParameters->get('application_type') : 'web';
        $response_types = $validatedParameters->has('response_types') ? $validatedParameters->get('response_types') : [];
        $uses_implicit_grant_type = false;
        foreach ($response_types as $response_type) {
            $types = explode(' ', $response_type);
            if (in_array('token', $types)) {
                $uses_implicit_grant_type = true;

                break;
            }
        }

        if (!$commandParameters->has('redirect_uris')) {
            if ($is_client_public) {
                throw new \InvalidArgumentException('Non-confidential clients must register at least one redirect URI.');
            }
            if ($uses_implicit_grant_type) {
                throw new \InvalidArgumentException('Confidential clients must register at least one redirect URI when using the "token" response type.');
            }
            $redirectUris = [];
        } else {
            $redirectUris = $commandParameters->get('redirect_uris');
            if (!is_array($redirectUris)) {
                throw new \InvalidArgumentException('The parameter "redirect_uris" must be a list of URI or URN.');
            }
        }

        $this->checkAllUris($redirectUris, $application_type, $uses_implicit_grant_type, $is_client_public);
        $validatedParameters = $validatedParameters->with('redirect_uris', $redirectUris);

        return $validatedParameters;
    }

    /**
     * @param array  $value
     * @param string $application_type
     * @param bool   $uses_implicit_grant_type
     * @param bool   $is_client_public
     */
    private function checkAllUris(array $value, string $application_type, bool $uses_implicit_grant_type, bool $is_client_public)
    {
        foreach ($value as $redirectUri) {
            if (!is_string($redirectUri)) {
                throw new \InvalidArgumentException('The parameter "redirect_uris" must be a list of URI or URN.');
            }
            $this->checkUri($redirectUri, $application_type, $uses_implicit_grant_type);
        }
    }

    /**
     * @param string $uri
     * @param string $application_type
     * @param bool   $uses_implicit_grant_type
     */
    private function checkUri(string $uri, string $application_type, bool $uses_implicit_grant_type)
    {
        if ('urn:' === mb_substr($uri, 0, 4, '8bit')) {
            $this->checkUrn($uri);
        } else {
            $this->checkUrl($uri, $application_type, $uses_implicit_grant_type);
        }
    }

    /**
     * @param string $url
     * @param string $application_type
     * @param bool   $uses_implicit_grant_type
     */
    public function checkUrl(string $url, string $application_type, bool $uses_implicit_grant_type)
    {
        // If URI is not a valid URI, return false
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('The parameter "redirect_uris" must be a list of URI or URN.');
        }

        $parsed = parse_url($url);

        // Checks for path traversal (e.g. http://foo.bar/redirect/../bad/url)
        if (isset($parsed['path'])) {
            $path = urldecode($parsed['path']);
            // check for 'path traversal'
            if (preg_match('#/\.\.?(/|$)#', $path)) {
                throw new \InvalidArgumentException('The URI listed in the "redirect_uris" parameter must not contain any path traversal.');
            }
        }
        if (array_key_exists('fragment', $parsed)) {
            throw new \InvalidArgumentException('The parameter "redirect_uris" must only contain URIs without fragment.');
        }
        if ('web' === $application_type && true === $uses_implicit_grant_type) {
            if ('localhost' === $parsed['host']) {
                throw new \InvalidArgumentException('The host "localhost" is not allowed for web applications that use the Implicit Grant Type.');
            }
            if ('https' !== $parsed['scheme']) {
                throw new \InvalidArgumentException('The parameter "redirect_uris" must only contain URIs with the HTTPS scheme for web applications that use the Implicit Grant Type.');
            }
        }
    }

    /**
     * @param string $urn
     */
    private function checkUrn(string $urn)
    {
        if (1 !== preg_match('/^urn:[a-z0-9][a-z0-9-]{0,31}:([a-z0-9()+,-.:=@;$_!*\']|%(0[1-9a-f]|[1-9a-f][0-9a-f]))+$/i', $urn)) {
            throw new \InvalidArgumentException('The parameter "redirect_uris" must be a list of URI or URN.');
        }
    }
}
