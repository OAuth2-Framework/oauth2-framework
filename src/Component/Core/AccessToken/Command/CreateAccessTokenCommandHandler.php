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

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;

class CreateAccessTokenCommandHandler
{
    /**
     * @var AccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * CreateAccessTokenCommandHandler constructor.
     *
     * @param AccessTokenRepository $accessTokenRepository
     */
    public function __construct(AccessTokenRepository $accessTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * @param CreateAccessTokenCommand $command
     */
    public function handle(CreateAccessTokenCommand $command)
    {
        $accessToken = $this->accessTokenRepository->find($command->getAccessTokenId());
        if (null !== $accessToken) {
            throw new \InvalidArgumentException(sprintf('The access token with ID "%s" already exists.', $command->getAccessTokenId()->getValue()));
        }
        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            $command->getAccessTokenId(),
            $command->getResourceOwnerId(),
            $command->getClientId(),
            $command->getParameters(),
            $command->getMetadatas(),
            $command->getExpiresAt(),
            $command->getResourceServerId()
        );
        $this->accessTokenRepository->save($accessToken);
    }
}
