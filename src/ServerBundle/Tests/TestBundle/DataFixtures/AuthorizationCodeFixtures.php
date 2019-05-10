<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\DataFixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AuthorizationCode;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AuthorizationCodeRepository;

final class AuthorizationCodeFixtures implements FixtureInterface
{
    private $authorizationCodeRepository;

    public function __construct(AuthorizationCodeRepository $authorizationCodeRepository)
    {
        $this->authorizationCodeRepository = $authorizationCodeRepository;
    }

    public function load(ObjectManager $manager)
    {
        foreach ($this->getData() as $datum) {
            $authorizationCode = new AuthorizationCode(
                new AuthorizationCodeId($datum['authorization_code_id']),
                new ClientId($datum['client_id']),
                new UserAccountId($datum['user_account_id']),
                $datum['query_parameters'],
                $datum['redirect_uri'],
                $datum['expires_at'],
                new DataBag($datum['parameter']),
                new DataBag($datum['metadata']),
                $datum['resource_server_id']
            );
            if ($datum['is_revoked']) {
                $authorizationCode->markAsUsed();
            }
            $this->authorizationCodeRepository->save($authorizationCode);
        }
    }

    private function getData(): array
    {
        return [
            [
                'authorization_code_id' => 'VALID_AUTHORIZATION_CODE',
                'client_id' => 'CLIENT_ID_3',
                'user_account_id' => 'john.1',
                'query_parameters' => [],
                'redirect_uri' => 'http://localhost/callback',
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
            ],
            [
                'authorization_code_id' => 'VALID_AUTHORIZATION_CODE_FOR_CONFIDENTIAL_CLIENT',
                'client_id' => 'CLIENT_ID_5',
                'user_account_id' => 'john.1',
                'query_parameters' => [],
                'redirect_uri' => 'http://localhost/callback',
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
            ],
            [
                'authorization_code_id' => 'REVOKED_AUTHORIZATION_CODE',
                'client_id' => 'CLIENT_ID_3',
                'user_account_id' => 'john.1',
                'query_parameters' => [],
                'redirect_uri' => 'http://localhost/callback',
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => true,
            ],
            [
                'authorization_code_id' => 'EXPIRED_AUTHORIZATION_CODE',
                'client_id' => 'CLIENT_ID_3',
                'user_account_id' => 'john.1',
                'query_parameters' => [],
                'redirect_uri' => 'http://localhost/callback',
                'expires_at' => new \DateTimeImmutable('now -1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
            ],
        ];
    }
}
