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

namespace OAuth2Framework\Component\Core\TokenType;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterChecker;
use OAuth2Framework\Component\Core\Message\OAuth2Message;

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
     */
    public function __construct(TokenTypeManager $tokenTypeManager, bool $tokenTypeParameterAllowed)
    {
        $this->tokenTypeManager = $tokenTypeManager;
        $this->tokenTypeParameterAllowed = $tokenTypeParameterAllowed;
    }

    public function check(Authorization $authorization): Authorization
    {
        try {
            $tokenType = $this->getTokenType($authorization);
            $authorization = $authorization->withTokenType($tokenType);

            return $authorization;
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2AuthorizationException(400, OAuth2Message::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
        }
    }

    private function getTokenType(Authorization $authorization): TokenType
    {
        if (true === $this->tokenTypeParameterAllowed && $authorization->hasQueryParam('token_type')) {
            return $this->tokenTypeManager->get($authorization->getQueryParam('token_type'));
        }

        return $this->tokenTypeManager->getDefault();
    }
}
