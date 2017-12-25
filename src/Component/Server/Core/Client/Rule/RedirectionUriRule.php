<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Core\Client\Rule;

use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;

final class RedirectionUriRule implements Rule
{
    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        $validatedParameters = $next($clientId, $commandParameters, $validatedParameters);
        if (!$validatedParameters->has('response_types') || empty($validatedParameters->get('response_types'))) {
            return $validatedParameters;
        }
        if (!$commandParameters->has('redirect_uris')) {
            throw new \InvalidArgumentException('The parameter "redirect_uris" is mandatory when response types are used.');
        }
        $redirectUris = $commandParameters->get('redirect_uris');
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
        $this->checkAllUris($redirectUris, $application_type, $uses_implicit_grant_type);
        $validatedParameters = $validatedParameters->with('redirect_uris', $redirectUris);

        return $validatedParameters;
    }

    /**
     * @param mixed  $value
     * @param string $application_type
     * @param bool   $uses_implicit_grant_type
     */
    private function checkAllUris($value, string $application_type, bool $uses_implicit_grant_type)
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException('The parameter "redirect_uris" must be a list of URI or URN.');
        }
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
