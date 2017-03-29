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

namespace OAuth2Framework\Component\Server\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Server\TokenType\TokenTypeManager;
use Psr\Http\Message\ServerRequestInterface;

final class AccessTokenMiddleware implements MiddlewareInterface
{
    /**
     * @var TokenTypeManager
     */
    private $tokenTypeManager;

    /**
     * @var AccessTokenRepositoryInterface
     */
    private $accessTokenRepository;

    /**
     * AccessTokenMiddleware constructor.
     *
     * @param TokenTypeManager               $tokenTypeManager
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     */
    public function __construct(TokenTypeManager $tokenTypeManager, AccessTokenRepositoryInterface $accessTokenRepository)
    {
        $this->tokenTypeManager = $tokenTypeManager;
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $additional_credential_values = [];
        $token = $this->tokenTypeManager->findToken($request, $additional_credential_values, $type);
        if (null !== $token) {
            $tokenId = AccessTokenId::create($token);
            $accessToken = $this->accessTokenRepository->find($tokenId);
            if (null === $accessToken || false === $type->isTokenRequestValid($accessToken, $request, $additional_credential_values)) {
                throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_TOKEN, 'error_description' => 'Invalid access token.']);
            }
            $request = $request->withAttribute('access_token', $accessToken);
        }

        return $delegate->process($request);
    }
}
