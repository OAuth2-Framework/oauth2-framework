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

class AuthorizationCodeRepository implements AuthorizationCodeRepositoryInterface
{
    /**
     * @var AuthorizationCode[]
     */
    private $authorizationCodes = [];

    public function __construct()
    {
        $this->initAuthorizationCodes();
    }

    /**
     * {@inheritdoc}
     */
    public function find(AuthorizationCodeId $authCodeId): ? AuthorizationCode
    {
        return array_key_exists($authCodeId->getValue(), $this->authorizationCodes) ? $this->authorizationCodes[$authCodeId->getValue()] : null;
    }

    /**
     * @param AuthorizationCode $authCode
     */
    public function save(AuthorizationCode $authCode)
    {
        $this->authorizationCodes[$authCode->getTokenId()->getValue()] = $authCode;
    }

    private function initAuthorizationCodes()
    {
        $refreshToken = AuthorizationCode::createEmpty();
        $refreshToken = $refreshToken->create(
            AuthorizationCodeId::create('VALID_AUTHORIZATION_CODE'),
            ClientId::create('CLIENT_ID_3'),
            UserAccountId::create('john.1'),
            [],
            'http://localhost/callback',
            new \DateTimeImmutable('now +1 day'),
            DataBag::create([]),
            DataBag::create([]),
            null
        );
        $refreshToken->eraseMessages();
        $this->save($refreshToken);

        $refreshToken = AuthorizationCode::createEmpty();
        $refreshToken = $refreshToken->create(
            AuthorizationCodeId::create('REVOKED_AUTHORIZATION_CODE'),
            ClientId::create('CLIENT_ID_3'),
            UserAccountId::create('john.1'),
            [],
            'http://localhost/callback',
            new \DateTimeImmutable('now +1 day'),
            DataBag::create([]),
            DataBag::create([]),
            null
        );
        $refreshToken = $refreshToken->markAsRevoked();
        $refreshToken->eraseMessages();
        $this->save($refreshToken);

        $refreshToken = AuthorizationCode::createEmpty();
        $refreshToken = $refreshToken->create(
            AuthorizationCodeId::create('EXPIRED_AUTHORIZATION_CODE'),
            ClientId::create('CLIENT_ID_3'),
            UserAccountId::create('john.1'),
            [],
            'http://localhost/callback',
            new \DateTimeImmutable('now -1 day'),
            DataBag::create([]),
            DataBag::create([]),
            null
        );
        $refreshToken->eraseMessages();
        $this->save($refreshToken);
    }
}
