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

final class RevokeRefreshTokenCommandHandler
{
    /**
     * @var RefreshTokenRepositoryInterface
     */
    private $refreshTokenRepository;

    /**
     * CreateClientCommandHandler constructor.
     *
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     */
    public function __construct(RefreshTokenRepositoryInterface $refreshTokenRepository)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * @param RevokeRefreshTokenCommand $command
     */
    public function handle(RevokeRefreshTokenCommand $command)
    {
        $refreshTokenId = $command->getRefreshTokenId();
        $refreshToken = $this->refreshTokenRepository->find($refreshTokenId);
        if (null !== $refreshToken) {
            $refreshToken = $refreshToken->markAsRevoked();
            $this->refreshTokenRepository->save($refreshToken);
        }
    }
}
