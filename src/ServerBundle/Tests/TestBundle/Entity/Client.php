<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\Client\AbstractClient;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class Client extends AbstractClient
{
    /**
     * @var ClientId
     */
    protected $clientId;

    public function __construct(ClientId $clientId, DataBag $parameters, ?UserAccountId $ownerId)
    {
        $this->clientId = $clientId;
        parent::__construct($parameters, $ownerId);
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }
}
