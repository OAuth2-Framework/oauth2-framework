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

namespace OAuth2Framework\Bundle\Annotation\Checker;

use OAuth2Framework\Bundle\Annotation\OAuth2;
use OAuth2Framework\Bundle\Security\Authentication\Token\OAuth2Token;

class ClientIdChecker implements CheckerInterface
{
    /**
     * {@inheritdoc}
     */
    public function check(OAuth2Token $token, OAuth2 $configuration): ?string
    {
        if (null === $configuration->getClientId()) {
            return null;
        }

        if ($configuration->getClientId() !== $token->getClientId()) {
            return 'ClientCredentials not authorized.';
        }

        return null;
    }
}
