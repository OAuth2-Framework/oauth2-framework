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

use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Security\AccessTokenHandlerInterface;

final class AccessTokenHandlerUsingRepository implements AccessTokenHandlerInterface
{
    /**
     * @var AccessTokenRepositoryInterface
     */
    private $accessTokenRepository;

    /**
     * AccessTokenHandlerUsingRepository constructor.
     *
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     */
    public function __construct(AccessTokenRepositoryInterface $accessTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $token)
    {
        $tokenId = AccessTokenId::create($token);

        return $this->accessTokenRepository->find($tokenId);
    }
}
