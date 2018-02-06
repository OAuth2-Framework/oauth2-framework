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

namespace OAuth2Framework\Component\TokenType;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class TokenTypeMiddleware.
 *
 * This middleware should be used with the Token Endpoint.
 */
class TokenTypeMiddleware implements MiddlewareInterface
{
    /**
     * @var bool
     */
    private $tokenTypeParameterAllowed;

    /**
     * @var TokenTypeManager
     */
    private $tokenTypeManager;

    /**
     * ClientAuthenticationMiddleware constructor.
     *
     * @param TokenTypeManager $tokenTypeManager
     * @param bool             $tokenTypeParameterAllowed
     */
    public function __construct(TokenTypeManager $tokenTypeManager, bool $tokenTypeParameterAllowed)
    {
        $this->tokenTypeManager = $tokenTypeManager;
        $this->tokenTypeParameterAllowed = $tokenTypeParameterAllowed;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $tokenType = $this->findTokenType($request);
        $request = $request->withAttribute('token_type', $tokenType);

        return $handler->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return TokenType
     */
    private function findTokenType(ServerRequestInterface $request): TokenType
    {
        $params = $request->getParsedBody() ?? [];
        if (true === $this->tokenTypeParameterAllowed && array_key_exists('token_type', $params)) {
            return $this->tokenTypeManager->get($params['token_type']);
        } else {
            return $this->tokenTypeManager->getDefault();
        }
    }
}
