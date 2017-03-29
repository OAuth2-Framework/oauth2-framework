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

namespace OAuth2Framework\Component\Server\Command\AuthCode;

use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeRepositoryInterface;

final class CreateAuthCodeCommandHandler
{
    /**
     * @var AuthCodeRepositoryInterface
     */
    private $authCodeRepository;

    /**
     * CreateClientCommandHandler constructor.
     *
     * @param AuthCodeRepositoryInterface $authCodeRepository
     */
    public function __construct(AuthCodeRepositoryInterface $authCodeRepository)
    {
        $this->authCodeRepository = $authCodeRepository;
    }

    /**
     * @param CreateAuthCodeCommand $command
     */
    public function handle(CreateAuthCodeCommand $command)
    {
        $authCode = $this->authCodeRepository->create(
            $command->getClientId(),
            $command->getUserAccountId(),
            $command->getQueryParameters(),
            $command->getRedirectUri(),
            $command->getParameters(),
            $command->getMetadatas(),
            $command->getScopes(),
            $command->hasRefreshToken(),
            $command->getResourcesServerId(),
            $command->getExpiresAt()
        );
        $this->authCodeRepository->save($authCode);
        if (null !== $data = $command->getDataTransporter()) {
            $data($authCode);
        }
    }
}
