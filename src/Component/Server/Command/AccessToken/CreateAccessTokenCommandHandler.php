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

final class CreateAccessTokenCommandHandler
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
     * @param CreateAccessTokenCommand $command
     */
    public function handle(CreateAccessTokenCommand $command)
    {
        $accessToken = $this->accessTokenRepository->create(
            $command->getResourceOwnerId(),
            $command->getClientId(),
            $command->getParameters(),
            $command->getMetadatas(),
            $command->getScopes(),
            null,
            $command->getResourceServerId(),
            $command->getExpiresAt()
        );
        $this->accessTokenRepository->save($accessToken);
        if (null !== $data = $command->getDataTransporter()) {
            $data($accessToken);
        }
    }
}
