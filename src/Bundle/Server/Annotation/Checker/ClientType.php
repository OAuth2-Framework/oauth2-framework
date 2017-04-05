<?php

declare(strict_types = 1);

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

class ClientType implements CheckerInterface
{
    const TYPE_PUBLIC = 'public';
    const TYPE_CONFIDENTIAL = 'confidential';

    /**
     * {@inheritdoc}
     */
    public function check(OAuth2Token $token, OAuth2 $configuration)
    {
        if (null === $configuration->getClientType()) {
            return null;
        }

        if (self::TYPE_PUBLIC === $configuration->getClientType() && 'none' === $token->getClient()->get('token_endpoint_auth_method')) {
            return null;
        }

        if (self::TYPE_CONFIDENTIAL === $configuration->getClientType() && 'none' !== $token->getClient()->get('token_endpoint_auth_method')) {
            return null;
        }

        return 'Resource owner not authorized.';
    }
}
