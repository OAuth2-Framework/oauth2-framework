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

namespace OAuth2Framework\ServerBundle\Annotation\Checker;

use OAuth2Framework\ServerBundle\Annotation\OAuth2;
use OAuth2Framework\ServerBundle\Security\Authentication\Token\OAuth2Token;

final class TokenTypeChecker implements Checker
{
    /**
     * {@inheritdoc}
     */
    public function check(OAuth2Token $token, OAuth2 $configuration): void
    {
        if (null === $configuration->getTokenType()) {
            return;
        }

        if ($configuration->getTokenType() !== $token->getTokenType()) {
            throw new \Exception('Client not authorized.');
        }
    }
}
