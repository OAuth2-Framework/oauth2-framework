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

namespace OAuth2Framework\Component\Server\Command\RefreshToken;

use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenRepositoryInterface;

final class CreateRefreshTokenCommandHandler
{
    /**
     * @var RefreshTokenRepositoryInterface
     */
    private $refreshTokenRepository;

    /**
     * CreateRefreshTokenCommandHandler constructor.
     *
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     */
    public function __construct(RefreshTokenRepositoryInterface $refreshTokenRepository)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * @param CreateRefreshTokenCommand $command
     */
    public function handle(CreateRefreshTokenCommand $command)
    {
        $refreshToken = $this->refreshTokenRepository->create(
            $command->getResourceOwnerId(),
            $command->getClientId(),
            $command->getParameters(),
            $command->getMetadatas(),
            $command->getScopes(),
            null,
            $command->getExpiresAt()
        );
        $this->refreshTokenRepository->save($refreshToken);
        if (null !== $data = $command->getDataTransporter()) {
            $data($refreshToken);
        }
    }
}
