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

namespace OAuth2Framework\Component\RefreshTokenGrant\Command;

use OAuth2Framework\Component\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository;

class CreateRefreshTokenCommandHandler
{
    /**
     * @var RefreshTokenRepository
     */
    private $refreshTokenRepository;

    /**
     * CreateRefreshTokenCommandHandler constructor.
     *
     * @param RefreshTokenRepository $refreshTokenRepository
     */
    public function __construct(RefreshTokenRepository $refreshTokenRepository)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * @param CreateRefreshTokenCommand $command
     */
    public function handle(CreateRefreshTokenCommand $command)
    {
        $refreshToken = $this->refreshTokenRepository->find($command->getRefreshTokenId());
        if (null !== $refreshToken) {
            throw new \InvalidArgumentException(sprintf('The refresh token with ID "%s" already exists.', $command->getRefreshTokenId()->getValue()));
        }
        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            $command->getRefreshTokenId(),
            $command->getResourceOwnerId(),
            $command->getClientId(),
            $command->getParameters(),
            $command->getMetadatas(),
            $command->getExpiresAt(),
            $command->getResourceServerId()
        );
        $this->refreshTokenRepository->save($refreshToken);
    }
}
