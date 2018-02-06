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

namespace OAuth2Framework\Component\Core\Client\Command;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class CreateClientCommand
{
    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * @var DataBag
     */
    private $parameters;

    /**
     * @var null|UserAccountId
     */
    private $userAccountId;

    /**
     * CreateClientCommand constructor.
     *
     * @param ClientId           $clientId
     * @param null|UserAccountId $userAccountId
     * @param DataBag            $parameters
     */
    protected function __construct(ClientId $clientId, ?UserAccountId $userAccountId, DataBag $parameters)
    {
        $this->clientId = $clientId;
        $this->parameters = $parameters;
        $this->userAccountId = $userAccountId;
    }

    /**
     * @param ClientId           $clientId
     * @param null|UserAccountId $userAccountId
     * @param DataBag            $parameters
     *
     * @return CreateClientCommand
     */
    public static function create(ClientId $clientId, ?UserAccountId $userAccountId, DataBag $parameters): self
    {
        return new self($clientId, $userAccountId, $parameters);
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    /**
     * @return DataBag
     */
    public function getParameters(): DataBag
    {
        return $this->parameters;
    }

    /**
     * @return UserAccountId|null
     */
    public function getUserAccountId(): ?UserAccountId
    {
        return $this->userAccountId;
    }
}
