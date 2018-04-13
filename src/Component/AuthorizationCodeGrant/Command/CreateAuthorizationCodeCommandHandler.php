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

class CreateAuthorizationCodeCommandHandler
{
    /**
     * @var AuthorizationCodeRepository
     */
    private $authorizationCodeRepository;

    /**
     * CreateAuthorizationCodeCommandHandler constructor.
     *
     * @param AuthorizationCodeRepository $authorizationCodeRepository
     */
    public function __construct(AuthorizationCodeRepository $authorizationCodeRepository)
    {
        $this->authorizationCodeRepository = $authorizationCodeRepository;
    }

    /**
     * @param CreateAuthorizationCodeCommand $command
     */
    public function handle(CreateAuthorizationCodeCommand $command)
    {
        $authorizationCode = $this->authorizationCodeRepository->find($command->getAuthorizationCodeId());
        if (null !== $authorizationCode) {
            throw new \InvalidArgumentException(sprintf('The authorization code with ID "%s" already exists.', $command->getAuthorizationCodeId()->getValue()));
        }
        $authorizationCode = AuthorizationCode::createEmpty();
        $authorizationCode = $authorizationCode->create(
            $command->getAuthorizationCodeId(),
            $command->getClientId(),
            $command->getUserAccountId(),
            $command->getQueryParameters(),
            $command->getRedirectUri(),
            $command->getExpiresAt(),
            $command->getParameters(),
            $command->getMetadatas(),
            $command->getResourceServerId()
        );
        $this->authorizationCodeRepository->save($authorizationCode);
    }
}
