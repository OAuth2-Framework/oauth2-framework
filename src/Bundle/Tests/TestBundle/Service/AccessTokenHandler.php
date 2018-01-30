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

namespace OAuth2Framework\Bundle\Tests\TestBundle\Service;

use OAuth2Framework\Component\Model\AccessToken\AccessToken;
use OAuth2Framework\Component\Model\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Model\AccessToken\AccessTokenRepositoryInterface;
use OAuth2Framework\Component\Security\AccessTokenHandlerInterface;

final class AccessTokenHandler implements AccessTokenHandlerInterface
{
    /**
     * @var AccessTokenRepositoryInterface
     */
    private $accessTokenRepository;

    /**
     * AccessTokenHandler constructor.
     *
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     */
    public function __construct(AccessTokenRepositoryInterface $accessTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * @param AccessTokenId $token
     *
     * @return null|AccessToken
     */
    public function find(AccessTokenId $token)
    {
        return $this->accessTokenRepository->find($token);
    }
}
