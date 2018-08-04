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
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeIdGenerator as AuthorizationCodeManagerInterface;

class AuthorizationCodeIdGenerator implements AuthorizationCodeManagerInterface
{
    /**
     * @var AuthorizationCodeRepository
     */
    private $repository;

    /**
     * AuthorizationCodeManager constructor.
     */
    public function __construct(AuthorizationCodeRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function createAuthorizationCodeId(): AuthorizationCodeId
    {
        return new AuthorizationCodeId(\bin2hex(\random_bytes(32)));
    }

    /**
     * {@inheritdoc}
     */
    public function save(AuthorizationCode $authCode): void
    {
        $this->repository->save($authCode);
    }
}
