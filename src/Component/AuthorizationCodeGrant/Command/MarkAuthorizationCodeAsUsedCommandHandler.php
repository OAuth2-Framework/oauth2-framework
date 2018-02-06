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

class MarkAuthorizationCodeAsUsedCommandHandler
{
    /**
     * @var AuthorizationCodeRepository
     */
    private $authorizationCodeRepository;

    /**
     * CreateClientCommandHandler constructor.
     *
     * @param AuthorizationCodeRepository $authorizationCodeRepository
     */
    public function __construct(AuthorizationCodeRepository $authorizationCodeRepository)
    {
        $this->authorizationCodeRepository = $authorizationCodeRepository;
    }

    /**
     * @param MarkAuthorizationCodeAsUsedCommand $command
     */
    public function handle(MarkAuthorizationCodeAsUsedCommand $command)
    {
        $authorizationCodeId = $command->getAuthorizationCodeId();
        $authorizationCode = $this->authorizationCodeRepository->find($authorizationCodeId);
        if (null === $authorizationCode) {
            throw new \InvalidArgumentException(sprintf('Unable to find the authorization code with ID "%s".', $command->getAuthorizationCodeId()->getValue()));
        }
        $authorizationCode = $authorizationCode->markAsUsed();
        $this->authorizationCodeRepository->save($authorizationCode);
    }
}
