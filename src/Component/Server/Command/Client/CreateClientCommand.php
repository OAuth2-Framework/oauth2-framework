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
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

final class CreateClientCommand extends CommandWithDataTransporter
{
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
     * @param UserAccountId|null   $userAccountId
     * @param DataBag              $parameters
     * @param DataTransporter|null $dataTransporter
     */
    protected function __construct(? UserAccountId $userAccountId, DataBag $parameters, ? DataTransporter $dataTransporter)
    {
        $this->parameters = $parameters;
        $this->userAccountId = $userAccountId;
        parent::__construct($dataTransporter);
    }

    /**
     * @param UserAccountId|null   $userAccountId
     * @param DataBag              $parameters
     * @param DataTransporter|null $dataTransporter
     *
     * @return CreateClientCommand
     */
    public static function create(? UserAccountId $userAccountId, DataBag $parameters, ? DataTransporter $dataTransporter): CreateClientCommand
    {
        return new self($userAccountId, $parameters, $dataTransporter);
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
