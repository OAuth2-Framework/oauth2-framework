<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Annotation\Checker;

use OAuth2Framework\Bundle\Server\Annotation\OAuth2;
use OAuth2Framework\Bundle\Server\Security\Authentication\Token\OAuth2Token;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;

final class ResourceOwnerType implements CheckerInterface
{
    const TYPE_CLIENT = 'client';
    const TYPE_USER = 'end_user';

    /**
     * {@inheritdoc}
     */
    public function check(OAuth2Token $token, OAuth2 $configuration)
    {
        if (null === $configuration->getResourceOwnerType()) {
            return null;
        }

        if (self::TYPE_CLIENT === $configuration->getResourceOwnerType() && $token->getResourceOwner() instanceof Client) {
            return null;
        }

        if (self::TYPE_USER === $configuration->getResourceOwnerType() && $token->getResourceOwner() instanceof UserAccountInterface) {
            return null;
        }

        return 'Resource owner not authorized.';
    }
}
