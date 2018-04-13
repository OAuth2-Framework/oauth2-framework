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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Service;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;

class AccessTokenHandler implements \OAuth2Framework\Component\Core\AccessToken\AccessTokenHandler
{
    /**
     * @var AccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * AccessTokenHandler constructor.
     *
     * @param AccessTokenRepository $accessTokenRepository
     */
    public function __construct(AccessTokenRepository $accessTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * @param AccessTokenId $token
     *
     * @return null|AccessToken
     */
    public function find(AccessTokenId $token): ?AccessToken
    {
        return $this->accessTokenRepository->find($token);
    }
}
