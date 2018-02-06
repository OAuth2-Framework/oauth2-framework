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

namespace OAuth2Framework\Component\Core\AccessToken\Command;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;

class RevokeAccessTokenCommandHandler
{
    /**
     * @var AccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * CreateClientCommandHandler constructor.
     *
     * @param AccessTokenRepository $accessTokenRepository
     */
    public function __construct(AccessTokenRepository $accessTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * @param RevokeAccessTokenCommand $command
     */
    public function handle(RevokeAccessTokenCommand $command)
    {
        $accessTokenId = $command->getAccessTokenId();
        $accessToken = $this->accessTokenRepository->find($accessTokenId);
        if (null === $accessToken) {
            throw new \InvalidArgumentException(sprintf('Unable to find the access token with ID "%s".', $command->getAccessTokenId()->getValue()));
        }
        $accessToken = $accessToken->markAsRevoked();
        $this->accessTokenRepository->save($accessToken);
    }
}
