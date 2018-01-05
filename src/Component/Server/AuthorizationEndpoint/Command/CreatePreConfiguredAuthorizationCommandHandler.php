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

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint\Command;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\PreConfiguredAuthorizationRepository;

final class CreatePreConfiguredAuthorizationCommandHandler
{
    /**
     * @var PreConfiguredAuthorizationRepository
     */
    private $preConfiguredAuthorizationRepository;

    /**
     * CreateClientCommandHandler constructor.
     *
     * @param PreConfiguredAuthorizationRepository $PreConfiguredAuthorizationRepository
     */
    public function __construct(PreConfiguredAuthorizationRepository $PreConfiguredAuthorizationRepository)
    {
        $this->preConfiguredAuthorizationRepository = $PreConfiguredAuthorizationRepository;
    }

    /**
     * @param CreatePreConfiguredAuthorizationCommand $command
     */
    public function handle(CreatePreConfiguredAuthorizationCommand $command)
    {
        $PreConfiguredAuthorization = $this->preConfiguredAuthorizationRepository->create(
            $command->getUserAccountId(),
            $command->getClientId(),
            $command->getScopes(),
            $command->getResourceServerId()
        );
        $this->preConfiguredAuthorizationRepository->save($PreConfiguredAuthorization);
    }
}
