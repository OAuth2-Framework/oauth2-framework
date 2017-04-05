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
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenRepositoryInterface;

final class CreateAccessTokenWithRefreshTokenCommandHandler
{
    /**
     * @var AccessTokenRepositoryInterface
     */
    private $accessTokenRepository;

    /**
     * @var RefreshTokenRepositoryInterface|null
     */
    private $refreshTokenRepository;

    /**
     * CreateAccessTokenWithRefreshTokenCommandHandler constructor.
     *
     * @param AccessTokenRepositoryInterface       $accessTokenRepository
     * @param RefreshTokenRepositoryInterface|null $refreshTokenRepository
     */
    public function __construct(AccessTokenRepositoryInterface $accessTokenRepository, ? RefreshTokenRepositoryInterface $refreshTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * @param CreateAccessTokenWithRefreshTokenCommand $command
     */
    public function handle(CreateAccessTokenWithRefreshTokenCommand $command)
    {
        if (null !== $this->refreshTokenRepository) {
            $refreshToken = $this->refreshTokenRepository->create(
                $command->getResourceOwnerId(),
                $command->getClientId(),
                $command->getParameters(),
                $command->getMetadatas(),
                $command->getScopes(),
                $command->getResourceServerId(),
                $command->getExpiresAt()
            );
        } else {
            $refreshToken = null;
        }

        $accessToken = $this->accessTokenRepository->create(
            $command->getResourceOwnerId(),
            $command->getClientId(),
            $command->getParameters(),
            $command->getMetadatas(),
            $command->getScopes(),
            null === $refreshToken ? null : $refreshToken->getTokenId(),
            $command->getResourceServerId(),
            $command->getExpiresAt()
        );

        if (null !== $refreshToken) {
            $refreshToken = $refreshToken->addAccessToken($accessToken->getTokenId());
            $this->refreshTokenRepository->save($refreshToken);
        }

        $this->accessTokenRepository->save($accessToken);
        if (null !== $data = $command->getDataTransporter()) {
            $data($accessToken);
        }
    }
}
