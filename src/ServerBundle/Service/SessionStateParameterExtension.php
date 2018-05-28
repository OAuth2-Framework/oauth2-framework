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

namespace OAuth2Framework\ServerBundle\Service;

use Base64Url\Base64Url;
use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionStateParameterExtension extends \OAuth2Framework\Component\OpenIdConnect\ConsentScreen\SessionStateParameterExtension
{
    /**
     * @var string
     */
    private $storageName;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * SessionStateParameterExtension constructor.
     *
     * @param SessionInterface $session
     * @param string           $storageName
     */
    public function __construct(SessionInterface $session, string $storageName)
    {
        $this->session = $session;
        $this->storageName = $storageName;
    }

    public function processBefore(ServerRequestInterface $request, Authorization $authorization): Authorization
    {
        return $authorization;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBrowserState(ServerRequestInterface $request, Authorization &$authorization): string
    {
        if ($this->session->has($this->storageName)) {
            return $this->session->get($this->storageName);
        }

        $browserState = Base64Url::encode(random_bytes(64));
        $this->session->set($this->storageName, $browserState);
        $cookie = new Cookie($this->storageName, $browserState);
        $authorization = $authorization->withResponseHeader('Set-Cookie', (string) $cookie);

        return $browserState;
    }

    /**
     * {@inheritdoc}
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
