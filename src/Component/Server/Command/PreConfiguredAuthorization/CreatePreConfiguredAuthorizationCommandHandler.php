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

use OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorizationRepositoryInterface;

final class CreatePreConfiguredAuthorizationCommandHandler
{
    /**
     * @var PreConfiguredAuthorizationRepositoryInterface
     */
    private $preConfiguredAuthorizationRepository;

    /**
     * CreateClientCommandHandler constructor.
     *
     * @param PreConfiguredAuthorizationRepositoryInterface $PreConfiguredAuthorizationRepository
     */
    public function __construct(PreConfiguredAuthorizationRepositoryInterface $PreConfiguredAuthorizationRepository)
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
        if (null !== $data = $command->getDataTransporter()) {
            $data($PreConfiguredAuthorization);
        }
    }
}
