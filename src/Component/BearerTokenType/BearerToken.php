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
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class BearerToken implements TokenType
{
    private $realm;

    private $tokenFromAuthorizationHeaderAllowed;

    private $tokenFromRequestBodyAllowed;

    private $tokenFromQueryStringAllowed;

    public function __construct(string $realm, bool $tokenFromAuthorizationHeaderAllowed, bool $tokenFromRequestBodyAllowed, bool $tokenFromQueryStringAllowed)
    {
        $this->realm = $realm;
        $this->tokenFromAuthorizationHeaderAllowed = $tokenFromAuthorizationHeaderAllowed;
        $this->tokenFromRequestBodyAllowed = $tokenFromRequestBodyAllowed;
        $this->tokenFromQueryStringAllowed = $tokenFromQueryStringAllowed;
    }

    public function name(): string
    {
        return 'Bearer';
    }

    private function isTokenFromAuthorizationHeaderAllowed(): bool
    {
        return $this->tokenFromAuthorizationHeaderAllowed;
    }

    private function isTokenFromRequestBodyAllowed(): bool
    {
        return $this->tokenFromRequestBodyAllowed;
    }

    private function isTokenFromQueryStringAllowed(): bool
    {
        return $this->tokenFromQueryStringAllowed;
    }

    public function getScheme(): string
    {
        return \Safe\sprintf('%s realm="%s"', $this->name(), $this->realm);
    }

    public function getAdditionalInformation(): array
    {
        return [];
    }

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

    public function isRequestValid(Token $token, ServerRequestInterface $request, array $additionalCredentialValues): bool
    {
        if (!$token instanceof AccessToken || !$token->getParameter()->has('token_type')) {
            return false;
        }

        return $token->getParameter()->get('token_type') === $this->name();
    }

    /**
     * Get the token from the authorization header.
     */
    private function getTokenFromAuthorizationHeaders(ServerRequestInterface $request): ?string
    {
        $authorization_headers = $request->getHeader('AUTHORIZATION');

        foreach ($authorization_headers as $authorization_header) {
            if (1 === \Safe\preg_match('/'.\preg_quote('Bearer', '/').'\s([a-zA-Z0-9\-_\+~\/\.]+)/', $authorization_header, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Get the token from the request body.
     */
    private function getTokenFromRequestBody(ServerRequestInterface $request): ?string
    {
        try {
            $parameters = RequestBodyParser::parseFormUrlEncoded($request);

            return \is_array($parameters) ? $this->getAccessTokenFromParameters($parameters) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the token from the query string.
     */
    private function getTokenFromQuery(ServerRequestInterface $request): ?string
    {
        $query_params = $request->getQueryParams();

        return $this->getAccessTokenFromParameters($query_params);
    }

    private function getAccessTokenFromParameters(array $params): ?string
    {
        return \array_key_exists('access_token', $params) ? $params['access_token'] : null;
    }
}
