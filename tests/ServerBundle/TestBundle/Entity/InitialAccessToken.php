<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\ClientRegistrationEndpoint\AbstractInitialAccessToken;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

final class InitialAccessToken extends AbstractInitialAccessToken
{
    private $id;

    public function __construct(InitialAccessTokenId $id, ?UserAccountId $userAccountId, ?\DateTimeImmutable $expiresAt)
    {
        parent::__construct($userAccountId, $expiresAt);
        $this->id = $id;
    }

    public function getId(): InitialAccessTokenId
    {
        return $this->id;
    }
}
