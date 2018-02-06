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

namespace OAuth2Framework\Component\Middleware;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenHandlerManager;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\TokenType\TokenType;
use OAuth2Framework\Component\TokenType\TokenTypeManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class OAuth2SecurityMiddleware implements MiddlewareInterface
{
    /**
     * @var TokenTypeManager
     */
    private $tokenTypeManager;

    /**
     * @var AccessTokenHandlerManager
     */
    private $accessTokenHandlerManager;

    /**
     * @var \string[]
     */
    private $scope = [];

    /**
     * @var array
     */
    private $additionalData = [];

    /**
     * OAuth2SecurityMiddleware constructor.
     *
     * @param TokenTypeManager          $tokenTypeManager
     * @param AccessTokenHandlerManager $accessTokenHandlerManager
     * @param string|null               $scope
     * @param array                     $additionalData
     */
    public function __construct(TokenTypeManager $tokenTypeManager, AccessTokenHandlerManager $accessTokenHandlerManager, string $scope = null, array $additionalData = [])
    {
        $this->tokenTypeManager = $tokenTypeManager;
        $this->accessTokenHandlerManager = $accessTokenHandlerManager;
        //Assertion::nullOrRegex($scope, '/^[\x20\x23-\x5B\x5D-\x7E]+$/', 'Invalid characters found in the \'scope\' parameter.');
        $this->scope = $scope ? array_unique(explode(' ', $scope)) : [];
        $this->additionalData = $additionalData;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $additionalCredentialValues = [];
        $token = $this->tokenTypeManager->findToken($request, $additionalCredentialValues, $type);
        if (null === $token) {
            throw $this->getOAuth2Exception(401, OAuth2Exception::ERROR_INVALID_TOKEN, 'Access token required.');
        }
        $accessToken = $this->accessTokenHandlerManager->find(AccessTokenId::create($token));
        if (null === $accessToken) {
            throw $this->getOAuth2Exception(401, OAuth2Exception::ERROR_INVALID_TOKEN, 'Access token does not exist or is not valid.');
        }
        $this->checkAccessToken($type, $accessToken, $request, $additionalCredentialValues);

        $request = $request->withAttribute('access_token', $accessToken);

        return $handler->handle($request);
    }

    /**
     * @param TokenType              $type
     * @param AccessToken            $accessToken
     * @param ServerRequestInterface $request
     * @param array                  $additionalCredentialValues
     *
     * @throws OAuth2Exception
     */
    private function checkAccessToken(TokenType $type, AccessToken $accessToken, ServerRequestInterface $request, array $additionalCredentialValues)
    {
        if (false === $type->isRequestValid($accessToken, $request, $additionalCredentialValues)) {
            throw $this->getOAuth2Exception(401, OAuth2Exception::ERROR_INVALID_TOKEN, 'Access token does not exist or is not valid.');
        }
        if (true === $accessToken->hasExpired()) {
            throw $this->getOAuth2Exception(403, OAuth2Exception::ERROR_INVALID_TOKEN, 'Access token has expired.');
        }
        if (!empty($this->scope)) {
            $diff = array_diff(
                $this->scope,
                $accessToken->hasParameter('scope') ? $accessToken->getParameter('scope') : []
            );

            if (!empty($diff)) {
                throw $this->getOAuth2Exception(403, OAuth2Exception::ERROR_INVALID_TOKEN, 'Insufficient scope.');
            }
        }
    }

    /**
     * @param int    $code
     * @param string $error
     * @param string $errorDescription
     *
     * @return OAuth2Exception
     */
    private function getOAuth2Exception(int $code, string $error, string $errorDescription)
    {
        $data = [];
        if (null !== $this->scope) {
            $data['scope'] = implode(' ', $this->scope);
        }

        return new OAuth2Exception($code, $error, $errorDescription, $data);
    }
}
