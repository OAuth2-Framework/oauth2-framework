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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use Base64Url\Base64Url;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AfterConsentScreen\SessionStateParameterExtension as Base;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Cookie;

class SessionStateParameterExtension extends Base
{
    /**
     * @var string
     */
    private $storageName;

    /**
     * SessionStateParameterExtension constructor.
     *
     * @param string $storageName
     */
    public function __construct(string $storageName)
    {
        $this->storageName = $storageName;
    }

    /**
     * {@inheritdoc}
     */
    public function getBrowserState(ServerRequestInterface $request, Authorization &$authorization): string
    {
        if (array_key_exists($this->storageName, $_SESSION)) {
            return $_SESSION[$this->storageName];
        }

        $browserState = Base64Url::encode(random_bytes(64));
        $_SESSION[$this->storageName] = $browserState;
        $cookie = new Cookie($this->storageName, $browserState);
        $authorization = $authorization->withResponseHeader('Set-Cookie', (string) $cookie);

        return $browserState;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     * @param string                 $browserState
     *
     * @return string
     */
    protected function calculateSessionState(ServerRequestInterface $request, Authorization $authorization, string $browserState): string
    {
        $origin = $this->getOriginUri($authorization->getRedirectUri());
        $salt = Base64Url::encode(random_bytes(16));
        $hash = hash('sha256', sprintf('%s%s%s%s', $authorization->getClient()->getPublicId(), $origin, $browserState, $salt));

        return sprintf('%s.%s', $hash, $salt);
    }

    /**
     * @param string $redirectUri
     *
     * @return string
     */
    private function getOriginUri(string $redirectUri): string
    {
        $url_parts = parse_url($redirectUri);

        return sprintf('%s://%s%s', $url_parts['scheme'], $url_parts['host'], isset($url_parts['port']) ? ':'.$url_parts['port'] : '');
    }
}
