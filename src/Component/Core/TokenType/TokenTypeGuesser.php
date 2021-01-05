<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Core\TokenType;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

class TokenTypeGuesser
{
    private bool $tokenTypeParameterAllowed;

    private TokenTypeManager $tokenTypeManager;

    public function __construct(TokenTypeManager $tokenTypeManager, bool $tokenTypeParameterAllowed)
    {
        $this->tokenTypeManager = $tokenTypeManager;
        $this->tokenTypeParameterAllowed = $tokenTypeParameterAllowed;
    }

    public function find(AuthorizationRequest $authorization): TokenType
    {
        if (!$this->tokenTypeParameterAllowed || !$authorization->hasQueryParam('token_type')) {
            return $this->tokenTypeManager->getDefault();
        }

        return $this->tokenTypeManager->get($authorization->getQueryParam('token_type'));
    }
}
