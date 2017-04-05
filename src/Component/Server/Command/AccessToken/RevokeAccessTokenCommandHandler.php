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

namespace OAuth2Framework\Component\Server\Command\AccessToken;

use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenRepositoryInterface;

final class RevokeAccessTokenCommandHandler
{
    /**
     * @var AccessTokenRepositoryInterface
     */
    private $accessTokenRepository;

    /**
     * CreateClientCommandHandler constructor.
     *
     * @param AccessTokenRepositoryInterface $accessTokenRepository
     */
    public function __construct(AccessTokenRepositoryInterface $accessTokenRepository)
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
        if (null !== $accessToken) {
            $accessToken = $accessToken->markAsRevoked();
            $this->accessTokenRepository->save($accessToken);
        }
    }
}
