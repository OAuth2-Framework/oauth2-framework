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

namespace OAuth2Framework\Component\AuthorizationCodeGrant\Command;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCode;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\AuthorizationCodeGrant\Event\AuthorizationCodeCreatedEvent;
use SimpleBus\SymfonyBridge\Bus\EventBus;

class CreateAuthorizationCodeHandler
{
    private $authorizationCodeRepository;
    private $eventBus;

    public function __construct(AuthorizationCodeRepository $authorizationCodeRepository, EventBus $eventBus)
    {
        $this->authorizationCodeRepository = $authorizationCodeRepository;
        $this->eventBus = $eventBus;
    }

    public function handle(CreateAuthorizationCode $command): void
    {
        $authorizationCode = $this->authorizationCodeRepository->find($command->getAuthorizationCodeId());
        if ($authorizationCode) {
            throw new \InvalidArgumentException(\sprintf('The authorization code/ with ID "%s" already exist.', $command->getAuthorizationCodeId()->getValue()));
        }

        $authorizationCode = new AuthorizationCode(
            $command->getAuthorizationCodeId(),
            $command->getQueryParameter(),
            $command->getRedirectUri(),
            $command->getUserAccountId(),
            $command->getClientId(),
            $command->getParameter(),
            $command->getMetadata(),
            $command->getExpiresAt(),
            $command->getResourceServerId()
        );
        $this->authorizationCodeRepository->save($authorizationCode);
        $event = new AuthorizationCodeCreatedEvent(
            $command->getAuthorizationCodeId(),
            $command->getQueryParameter(),
            $command->getRedirectUri(),
            $command->getUserAccountId(),
            $command->getClientId(),
            $command->getParameter(),
            $command->getMetadata(),
            $command->getExpiresAt(),
            $command->getResourceServerId()
        );
        $this->eventBus->handle($event);
    }
}
