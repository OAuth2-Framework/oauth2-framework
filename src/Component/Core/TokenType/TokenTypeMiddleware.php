<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Core\TokenType;

use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class TokenTypeMiddleware.
 *
 * This middleware should be used with the Token Endpoint.
 */
final class TokenTypeMiddleware implements MiddlewareInterface
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
     */
    public function __construct(TokenTypeManager $tokenTypeManager, bool $tokenTypeParameterAllowed)
    {
        $this->tokenTypeManager = $tokenTypeManager;
        $this->tokenTypeParameterAllowed = $tokenTypeParameterAllowed;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $tokenType = $this->findTokenType($request);
        $request = $request->withAttribute('token_type', $tokenType);

        return $handler->handle($request);
    }

    private function findTokenType(ServerRequestInterface $request): TokenType
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        if (true === $this->tokenTypeParameterAllowed && \array_key_exists('token_type', $parameters)) {
            return $this->tokenTypeManager->get($parameters['token_type']);
        } else {
            return $this->tokenTypeManager->getDefault();
        }
    }
}
