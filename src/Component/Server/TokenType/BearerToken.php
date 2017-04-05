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

namespace OAuth2Framework\Component\Server\TokenType;

use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;
use Psr\Http\Message\ServerRequestInterface;

final class BearerToken implements TokenTypeInterface
{
    /**
     * @var string
     */
    private $realm;

    /**
     * @var bool
     */
    private $tokenFromAuthorizationHeaderAllowed;

    /**
     * @var bool
     */
    private $tokenFromRequestBodyAllowed;

    /**
     * @var bool
     */
    private $tokenFromQueryStringAllowed;

    /**
     * BearerToken constructor.
     *
     * @param string $realm
     * @param bool   $tokenFromAuthorizationHeaderAllowed
     * @param bool   $tokenFromRequestBodyAllowed
     * @param bool   $tokenFromQueryStringAllowed
     */
    public function __construct(string $realm, bool $tokenFromAuthorizationHeaderAllowed, bool $tokenFromRequestBodyAllowed, bool $tokenFromQueryStringAllowed)
    {
        $this->realm = $realm;
        $this->tokenFromAuthorizationHeaderAllowed = $tokenFromAuthorizationHeaderAllowed;
        $this->tokenFromRequestBodyAllowed = $tokenFromRequestBodyAllowed;
        $this->tokenFromQueryStringAllowed = $tokenFromQueryStringAllowed;
    }

    /**
     * @return bool
     */
    public function isTokenFromAuthorizationHeaderAllowed(): bool
    {
        return $this->tokenFromAuthorizationHeaderAllowed;
    }

    /**
     * @return bool
     */
    public function isTokenFromRequestBodyAllowed(): bool
    {
        return $this->tokenFromRequestBodyAllowed;
    }

    /**
     * @return bool
     */
    public function isTokenFromQueryStringAllowed(): bool
    {
        return $this->tokenFromQueryStringAllowed;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'Bearer';
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme(): string
    {
        return sprintf('%s realm="%s"', $this->name(), $this->realm);
    }

    /**
     * {@inheritdoc}
     */
    public function getInformation(): array
    {
        return [
            'token_type' => $this->name(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findToken(ServerRequestInterface $request, array &$additionalCredentialValues)
    {
        $methods = [
            'isTokenFromAuthorizationHeaderAllowed' => 'getTokenFromAuthorizationHeaders',
            'isTokenFromQueryStringAllowed' => 'getTokenFromQuery',
            'isTokenFromRequestBodyAllowed' => 'getTokenFromRequestBody',
        ];

        foreach ($methods as $test => $method) {
            if (true === $this->$test() && null !== $token = $this->$method($request)) {
                return $token;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isTokenRequestValid(AccessToken $accessToken, ServerRequestInterface $request, array $additionalCredentialValues): bool
    {
        return $accessToken->getParameter('token_type') === $this->name();
    }

    /**
     * Get the token from the authorization header.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return string|null
     */
    protected function getTokenFromAuthorizationHeaders(ServerRequestInterface $request)
    {
        $authorization_headers = $request->getHeader('AUTHORIZATION');

        if (0 === count($authorization_headers)) {
            return;
        }

        foreach ($authorization_headers as $authorization_header) {
            if (1 === preg_match('/'.preg_quote('Bearer', '/').'\s([a-zA-Z0-9\-_\+~\/\.]+)/', $authorization_header, $matches)) {
                return $matches[1];
            }
        }
    }

    /**
     * Get the token from the request body.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return string|null
     */
    protected function getTokenFromRequestBody(ServerRequestInterface $request)
    {
        $request_params = $request->getParsedBody();
        if (is_array($request_params)) {
            return $this->getAccessTokenFromParameters($request_params);
        }
    }

    /**
     * Get the token from the query string.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return string|null
     */
    protected function getTokenFromQuery(ServerRequestInterface $request)
    {
        $query_params = $request->getQueryParams();

        return $this->getAccessTokenFromParameters($query_params);
    }

    /**
     * @param array $params
     *
     * @return string|null
     */
    private function getAccessTokenFromParameters(array $params)
    {
        return array_key_exists('access_token', $params) ? $params['access_token'] : null;
    }
}
