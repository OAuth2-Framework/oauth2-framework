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

namespace OAuth2Framework\Component\Server\TokenType;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\ParameterChecker\ParameterChecker;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;

/**
 * Class TokenTypeParameterChecker.
 *
 * This parameter checker should be used with the Authorization Endpoint
 */
final class TokenTypeParameterChecker implements ParameterChecker
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
     * TokenTypeParameterChecker constructor.
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
    public function process(Authorization $authorization, callable $next): Authorization
    {
        try {
            if (true === $this->tokenTypeParameterAllowed && $authorization->hasQueryParam('token_type')) {
                $tokenType = $this->tokenTypeManager->get($authorization->getQueryParam('token_type'));
            } else {
                $tokenType = $this->tokenTypeManager->getDefault();
            }
            if (!$authorization->getClient()->isTokenTypeAllowed($tokenType->name())) {
                throw new \InvalidArgumentException(sprintf('The token type "%s" is not allowed for the client.', $tokenType->name()));
            }
            $authorization = $authorization->withTokenType($tokenType);

            return $next($authorization);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
        }
    }
}
