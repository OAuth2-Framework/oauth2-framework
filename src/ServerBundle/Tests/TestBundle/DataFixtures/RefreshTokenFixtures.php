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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\DataFixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\RefreshToken;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\RefreshTokenRepository;

final class RefreshTokenFixtures implements FixtureInterface
{
    private $refreshTokenRepository;

    public function __construct(RefreshTokenRepository $refreshTokenRepository)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    public function load(ObjectManager $manager)
    {
        foreach ($this->getData() as $datum) {
            $refreshToken = new RefreshToken(
                new RefreshTokenId($datum['refresh_token_id']),
                new ClientId($datum['client_id']),
                $datum['resource_owner_id'],
                $datum['expires_at'],
                new DataBag($datum['parameter']),
                new DataBag($datum['metadata']),
                $datum['resource_server_id']
            );
            if ($datum['is_revoked']) {
                $refreshToken->markAsRevoked();
            }
            $this->refreshTokenRepository->save($refreshToken);
        }
    }

    private function getData(): array
    {
        return [
            [
                'refresh_token_id' => 'VALID_REFRESH_TOKEN',
                'client_id' => 'CLIENT_ID_3',
                'resource_owner_id' => new UserAccountId('john.1'),
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
            ],
            [
                'refresh_token_id' => 'REVOKED_REFRESH_TOKEN',
                'client_id' => 'CLIENT_ID_3',
                'resource_owner_id' => new UserAccountId('john.1'),
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => true,
            ],
            [
                'refresh_token_id' => 'EXPIRED_REFRESH_TOKEN',
                'client_id' => 'CLIENT_ID_3',
                'resource_owner_id' => new UserAccountId('john.1'),
                'expires_at' => new \DateTimeImmutable('now -1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
            ],
        ];
    }
}
