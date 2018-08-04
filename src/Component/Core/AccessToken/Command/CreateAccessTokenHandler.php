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
use OAuth2Framework\Component\Core\AccessToken\Event\AccessTokenCreatedEvent;
use SimpleBus\SymfonyBridge\Bus\EventBus;

class CreateAccessTokenHandler
{
    private $accessTokenRepository;
    private $eventBus;

    public function __construct(AccessTokenRepository $accessTokenRepository, EventBus $eventBus)
    {
        $this->accessTokenRepository = $accessTokenRepository;
        $this->eventBus = $eventBus;
    }

    public function handle(CreateAccessToken $command): void
    {
        $accessToken = $this->accessTokenRepository->find($command->getAccessTokenId());
        if ($accessToken) {
            throw new \InvalidArgumentException(\sprintf('The access token with ID "%s" already exist.', $command->getAccessTokenId()->getValue()));
        }

        $accessToken = new AccessToken(
            $command->getAccessTokenId(),
            $command->getResourceOwnerId(),
            $command->getClientId(),
            $command->getParameter(),
            $command->getMetadata(),
            $command->getExpiresAt(),
            $command->getResourceServerId()
        );
        $this->accessTokenRepository->save($accessToken);
        $event = new AccessTokenCreatedEvent(
            $command->getAccessTokenId(),
            $command->getResourceOwnerId(),
            $command->getClientId(),
            $command->getParameter(),
            $command->getMetadata(),
            $command->getExpiresAt(),
            $command->getResourceServerId()
        );
        $this->eventBus->handle($event);
    }
}
