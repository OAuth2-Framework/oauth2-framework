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

namespace OAuth2Framework\Component\Server\Command\Client;

use OAuth2Framework\Component\Server\Command\CommandWithDataTransporter;
use OAuth2Framework\Component\Server\DataTransporter;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

final class CreateClientCommand extends CommandWithDataTransporter
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
     * @var UserAccountId|null
     */
    private $userAccountId;

    /**
     * CreateClientCommand constructor.
     *
     * @param ClientId             $clientId
     * @param null|UserAccountId   $userAccountId
     * @param DataBag              $parameters
     * @param null|DataTransporter $dataTransporter
     */
    protected function __construct(ClientId $clientId, ? UserAccountId $userAccountId, DataBag $parameters, ? DataTransporter $dataTransporter)
    {
        $this->clientId = $clientId;
        $this->parameters = $parameters;
        $this->userAccountId = $userAccountId;
        parent::__construct($dataTransporter);
    }

    /**
     * @param ClientId             $clientId
     * @param UserAccountId|null   $userAccountId
     * @param DataBag              $parameters
     * @param DataTransporter|null $dataTransporter
     *
     * @return CreateClientCommand
     */
    public static function create(ClientId $clientId, ? UserAccountId $userAccountId, DataBag $parameters, ? DataTransporter $dataTransporter): CreateClientCommand
    {
        return new self($clientId, $userAccountId, $parameters, $dataTransporter);
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
    public function getUserAccountId(): ? UserAccountId
    {
        return $this->userAccountId;
    }
}
