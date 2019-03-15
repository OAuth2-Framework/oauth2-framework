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

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterChecker;

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

    public function __construct(TokenTypeManager $tokenTypeManager, bool $tokenTypeParameterAllowed)
    {
        $this->tokenTypeManager = $tokenTypeManager;
        $this->tokenTypeParameterAllowed = $tokenTypeParameterAllowed;
    }

    public function check(AuthorizationRequest $authorization): void
    {
        if (!$this->tokenTypeParameterAllowed || !$authorization->hasQueryParam('token_type')) {
            $tokenType = $this->tokenTypeManager->getDefault();
        } else {
            $tokenType = $this->tokenTypeManager->get($authorization->getQueryParam('token_type'));
        }

        $authorization->setTokenType($tokenType);
    }
}
