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

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint\Command;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\PreConfiguredAuthorizationRepository;

final class RevokePreConfiguredAuthorizationCommandHandler
{
    /**
     * @var PreConfiguredAuthorizationRepository
     */
    private $preConfiguredAuthorizationRepository;

    /**
     * RevokeClientCommandHandler constructor.
     *
     * @param PreConfiguredAuthorizationRepository $preConfiguredAuthorizationRepository
     */
    public function __construct(PreConfiguredAuthorizationRepository $preConfiguredAuthorizationRepository)
    {
        $this->preConfiguredAuthorizationRepository = $preConfiguredAuthorizationRepository;
    }

    /**
     * @param RevokePreConfiguredAuthorizationCommand $command
     */
    public function handle(RevokePreConfiguredAuthorizationCommand $command)
    {
        $preConfiguredAuthorization = $this->preConfiguredAuthorizationRepository->find(
            $command->getUserAccountId(),
            $command->getClientId(),
            $command->getScopes(),
            $command->getResourceServerId()
        );

        if (null !== $preConfiguredAuthorization && !$preConfiguredAuthorization->isRevoked()) {
            $preConfiguredAuthorization = $preConfiguredAuthorization->markAsRevoked();
            $this->preConfiguredAuthorizationRepository->save($preConfiguredAuthorization);
        }
    }
}
