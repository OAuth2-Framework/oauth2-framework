<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Util;

final class Uri
{
    /**
     * Checks if the URI matches one of stored URIs.
     *
     * @param string $uri                  The URI to check
     * @param array  $storedUris           A list of stored URIs
     * @param bool   $pathTraversalAllowed
     *
     * @return bool
     */
    public static function isRedirectUriAllowed(string $uri, array $storedUris, bool $pathTraversalAllowed = false): bool
    {
        // If storedUris is empty, assume invalid
        if (count($storedUris) === 0) {
            return false;
        }

        if (false === self::isAnUrlOrUrn($uri, $pathTraversalAllowed)) {
            return false;
        }

        foreach ($storedUris as $storedUri) {
            if (strcasecmp(mb_substr($uri, 0, mb_strlen($storedUri, '8bit'), '8bit'), $storedUri) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $uri
     * @param bool   $pathTraversalAllowed
     *
     * @return bool
     */
    private static function isAnUrlOrUrn(string $uri, bool $pathTraversalAllowed): bool
    {
        if ('urn:' === mb_substr($uri, 0, 4, '8bit')) {
            if (false === self::checkUrn($uri)) {
                return false;
            }
        } else {
            if (false === self::checkUrl($uri, $pathTraversalAllowed)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $url
     * @param bool   $pathTraversalAllowed
     *
     * @return bool
     */
    public static function checkUrl(string $url, bool $pathTraversalAllowed): bool
    {
        // If URI is not a valid URI, return false
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsed = parse_url($url);

        // Checks for path traversal (e.g. http://foo.bar/redirect/../bad/url)
        if (isset($parsed['path']) && !$pathTraversalAllowed) {
            $path = urldecode($parsed['path']);
            // check for 'path traversal'
            if (preg_match('#/\.\.?(/|$)#', $path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $urn
     *
     * @return bool
     */
    private static function checkUrn(string $urn): bool
    {
        return 1 === preg_match('/^urn:[a-z0-9][a-z0-9-]{0,31}:[a-z0-9()+,\-.:=@;$_!*\'%\/?#]+$/', $urn);
    }
}
