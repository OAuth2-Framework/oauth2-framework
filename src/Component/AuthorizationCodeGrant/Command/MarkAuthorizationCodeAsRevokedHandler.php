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
use OAuth2Framework\Component\AuthorizationCodeGrant\Event\AuthorizationCodeMarkedAsRevokedEvent;
use SimpleBus\SymfonyBridge\Bus\EventBus;

class MarkAuthorizationCodeAsRevokedHandler
{
    private $authorizationCodeRepository;
    private $eventBus;

    public function __construct(AuthorizationCodeRepository $authorizationCodeRepository, EventBus $eventBus)
    {
        $this->authorizationCodeRepository = $authorizationCodeRepository;
        $this->eventBus = $eventBus;
    }

    public function handle(MarkAuthorizationCodeAsRevoked $command): void
    {
        $authorizationCode = $this->authorizationCodeRepository->find($command->getAuthorizationCodeId());
        if (!$authorizationCode) {
            throw new \InvalidArgumentException(\sprintf('The authorization code with ID "%s" does not exist.', $command->getAuthorizationCodeId()->getValue()));
        }

        $authorizationCode->markAsRevoked();
        $this->authorizationCodeRepository->save($authorizationCode);
        $event = new AuthorizationCodeMarkedAsRevokedEvent(
            $command->getAuthorizationCodeId()
        );
        $this->eventBus->handle($event);
    }
}
