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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCode;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository as AuthorizationCodeRepositoryInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

final class AuthorizationCodeRepository implements AuthorizationCodeRepositoryInterface
{
    /**
     * @var AuthorizationCode[]
     */
    private $authorizationCodes = [];

    public function __construct()
    {
        $this->initAuthorizationCodes();
    }

    public function find(AuthorizationCodeId $authCodeId): ?AuthorizationCode
    {
        return \array_key_exists($authCodeId->getValue(), $this->authorizationCodes) ? $this->authorizationCodes[$authCodeId->getValue()] : null;
    }

    public function save(AuthorizationCode $authCode): void
    {
        $this->authorizationCodes[$authCode->getTokenId()->getValue()] = $authCode;
    }

    private function initAuthorizationCodes()
    {
        $authorizationCode = new AuthorizationCode(
            new AuthorizationCodeId('VALID_AUTHORIZATION_CODE'),
            new ClientId('CLIENT_ID_3'),
            new UserAccountId('john.1'),
            [],
            'http://localhost/callback',
            new \DateTimeImmutable('now +1 day'),
            new DataBag([]),
            new DataBag([]),
            null
        );
        $this->save($authorizationCode);

        $authorizationCode = new AuthorizationCode(
            new AuthorizationCodeId('VALID_AUTHORIZATION_CODE_FOR_CONFIDENTIAL_CLIENT'),
            new ClientId('CLIENT_ID_5'),
            new UserAccountId('john.1'),
            [],
            'http://localhost/callback',
            new \DateTimeImmutable('now +1 day'),
            new DataBag([]),
            new DataBag([]),
            null
        );
        $this->save($authorizationCode);

        $authorizationCode = new AuthorizationCode(
            new AuthorizationCodeId('REVOKED_AUTHORIZATION_CODE'),
            new ClientId('CLIENT_ID_3'),
            new UserAccountId('john.1'),
            [],
            'http://localhost/callback',
            new \DateTimeImmutable('now +1 day'),
            new DataBag([]),
            new DataBag([]),
            null
        );
        $authorizationCode->markAsRevoked();
        $this->save($authorizationCode);

        $authorizationCode = new AuthorizationCode(
            new AuthorizationCodeId('EXPIRED_AUTHORIZATION_CODE'),
            new ClientId('CLIENT_ID_3'),
            new UserAccountId('john.1'),
            [],
            'http://localhost/callback',
            new \DateTimeImmutable('now -1 day'),
            new DataBag([]),
            new DataBag([]),
            null
        );
        $this->save($authorizationCode);
    }
}
