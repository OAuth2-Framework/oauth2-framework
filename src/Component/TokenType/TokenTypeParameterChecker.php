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

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterChecker;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;

/**
 * Class TokenTypeParameterChecker.
 *
 * This parameter checker should be used with the Authorization Endpoint
 */
class TokenTypeParameterChecker implements ParameterChecker
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
    public function check(Authorization $authorization): Authorization
    {
        try {
            $tokenType = $this->getTokenType($authorization);
            $this->checkTokenTypeForClient($tokenType, $authorization->getClient());
            $authorization = $authorization->withTokenType($tokenType);

            return $authorization;
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2AuthorizationException(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
        }
    }

    /**
     * @param Authorization $authorization
     *
     * @return TokenType
     */
    private function getTokenType(Authorization $authorization): TokenType
    {
        if (true === $this->tokenTypeParameterAllowed && $authorization->hasQueryParam('token_type')) {
            return $this->tokenTypeManager->get($authorization->getQueryParam('token_type'));
        }

        return $this->tokenTypeManager->getDefault();
    }

    /**
     * @param TokenType $tokenType
     * @param Client    $client
     */
    private function checkTokenTypeForClient(TokenType $tokenType, Client $client)
    {
        if (!$client->isTokenTypeAllowed($tokenType->name())) {
            throw new \InvalidArgumentException(sprintf('The token type "%s" is not allowed for the client.', $tokenType->name()));
        }
    }
}
