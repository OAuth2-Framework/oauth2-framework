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

namespace OAuth2Framework\Component\Server\Middleware;

use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\BearerTokenType\BearerToken;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class BearerTokenMiddleware implements MiddlewareInterface
{
    /**
     * @var BearerToken
     */
    private $bearerToken;

    /**
     * @var AccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * BearerTokenMiddleware constructor.
     *
     * @param BearerToken           $bearerToken
     * @param AccessTokenRepository $accessTokenRepository
     */
    public function __construct(BearerToken $bearerToken, AccessTokenRepository $accessTokenRepository)
    {
        $this->bearerToken = $bearerToken;
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $additional_credential_values = [];
        $token = $this->bearerToken->find($request, $additional_credential_values);
        if (null !== $token) {
            $accessToken = $this->accessTokenRepository->find(AccessTokenId::create($token));
            if (null === $accessToken || false === $this->bearerToken->isRequestValid($accessToken, $request, $additional_credential_values)) {
                throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_TOKEN, 'Invalid access token.');
            }
            $request = $request->withAttribute('access_token', $accessToken);
        }

        return $handler->handle($request);
    }
}
