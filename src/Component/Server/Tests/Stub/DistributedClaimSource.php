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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use OAuth2Framework\Component\Server\Endpoint\UserInfo\ClaimSource\ClaimSourceInterface;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\ClaimSource\Source;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;

final class DistributedClaimSource implements ClaimSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function getUserInfo(UserAccountInterface $userAccount, array $scope, array $claims)
    {
        if ('user2' === $userAccount->getPublicId()->getValue()) {
            $claims = ['address', 'email', 'email_verified'];
            $source = [
                'endpoint' => 'https://external.service.local/user/info',
                'access_token' => '0123456789',
                'token_type' => 'Bearer',
            ];

            return new Source($claims, $source);
        }
    }
}
