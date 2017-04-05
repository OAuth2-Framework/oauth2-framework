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

namespace OAuth2Framework\Component\Server\Command\PreConfiguredAuthorization;

use OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorizationManagerInterface;

final class CreatePreConfiguredAuthorizationCommandHandler
{
    /**
     * @var PreConfiguredAuthorizationManagerInterface
     */
    private $preConfiguredAuthorizationManager;

    /**
     * CreateClientCommandHandler constructor.
     *
     * @param PreConfiguredAuthorizationManagerInterface $PreConfiguredAuthorizationManager
     */
    public function __construct(PreConfiguredAuthorizationManagerInterface $PreConfiguredAuthorizationManager)
    {
        $this->preConfiguredAuthorizationManager = $PreConfiguredAuthorizationManager;
    }

    /**
     * @param CreatePreConfiguredAuthorizationCommand $command
     */
    public function handle(CreatePreConfiguredAuthorizationCommand $command)
    {
        $PreConfiguredAuthorization = $this->preConfiguredAuthorizationManager->create(
            $command->getClientId(),
            $command->getUserAccountId(),
            $command->getScopes()
        );
        $this->preConfiguredAuthorizationManager->save($PreConfiguredAuthorization);
        if (null !== $data = $command->getDataTransporter()) {
            $data($PreConfiguredAuthorization);
        }
    }
}
