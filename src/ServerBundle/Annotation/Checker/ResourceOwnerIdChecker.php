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

class ResourceOwnerIdChecker implements Checker
{
    /**
     * {@inheritdoc}
     */
    public function check(OAuth2Token $token, OAuth2 $configuration): ?string
    {
        if (null === $configuration->getResourceOwnerId()) {
            return null;
        }

        if ($configuration->getResourceOwnerId() !== $token->getResourceOwnerId()) {
            return 'Resource owner not authorized.';
        }

        return null;
    }
}
