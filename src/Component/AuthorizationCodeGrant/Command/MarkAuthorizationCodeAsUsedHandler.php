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

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\AuthorizationCodeGrant\Event\AuthorizationCodeMarkedAsUsedEvent;
use SimpleBus\SymfonyBridge\Bus\EventBus;

class MarkAuthorizationCodeAsUsedHandler
{
    private $authorizationCodeRepository;
    private $eventBus;

    public function __construct(AuthorizationCodeRepository $authorizationCodeRepository, EventBus $eventBus)
    {
        $this->authorizationCodeRepository = $authorizationCodeRepository;
        $this->eventBus = $eventBus;
    }

    public function handle(MarkAuthorizationCodeAsUsed $command): void
    {
        $authorizationCode = $this->authorizationCodeRepository->find($command->getAuthorizationCodeId());
        if (!$authorizationCode) {
            throw new \InvalidArgumentException(\sprintf('The authorization code with ID "%s" does not exist.', $command->getAuthorizationCodeId()->getValue()));
        }

        $authorizationCode->markAsUsed();
        $this->authorizationCodeRepository->save($authorizationCode);
        $event = new AuthorizationCodeMarkedAsUsedEvent(
            $command->getAuthorizationCodeId()
        );
        $this->eventBus->handle($event);
    }
}
