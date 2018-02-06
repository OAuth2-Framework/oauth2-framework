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

namespace OAuth2Framework\Component\BearerTokenType;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\TokenType\TokenType;
use Psr\Http\Message\ServerRequestInterface;

class BearerToken implements TokenType
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
    public function getAdditionalInformation(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function find(ServerRequestInterface $request, array &$additionalCredentialValues): ?string
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

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isRequestValid(Token $token, ServerRequestInterface $request, array $additionalCredentialValues): bool
    {
        if (!$token instanceof AccessToken || !$token->hasParameter('token_type')) {
            return false;
        }

        return $token->getParameter('token_type') === $this->name();
    }

    /**
     * Get the token from the authorization header.
     *
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    protected function getTokenFromAuthorizationHeaders(ServerRequestInterface $request): ?string
    {
        $authorization_headers = $request->getHeader('AUTHORIZATION');

        foreach ($authorization_headers as $authorization_header) {
            if (1 === preg_match('/'.preg_quote('Bearer', '/').'\s([a-zA-Z0-9\-_\+~\/\.]+)/', $authorization_header, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Get the token from the request body.
     *
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    private function getTokenFromRequestBody(ServerRequestInterface $request): ?string
    {
        $request_params = $request->getParsedBody() ?? [];

        return is_array($request_params) ? $this->getAccessTokenFromParameters($request_params) : null;
    }

    /**
     * Get the token from the query string.
     *
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    protected function getTokenFromQuery(ServerRequestInterface $request): ?string
    {
        $query_params = $request->getQueryParams();

        return $this->getAccessTokenFromParameters($query_params);
    }

    /**
     * @param array $params
     *
     * @return string|null
     */
    protected function getAccessTokenFromParameters(array $params): ?string
    {
        return array_key_exists('access_token', $params) ? $params['access_token'] : null;
    }
}
