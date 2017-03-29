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
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;

final class UpdateClientCommand extends CommandWithDataTransporter
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var DataBag
     */
    private $parameters;

    /**
     * UpdateClientCommand constructor.
     *
     * @param Client          $client
     * @param DataBag         $parameters
     * @param DataTransporter $dataTransporter
     */
    protected function __construct(Client $client, DataBag $parameters, DataTransporter $dataTransporter)
    {
        $this->client = $client;
        $this->parameters = $parameters;
        parent::__construct($dataTransporter);
    }

    /**
     * @param Client          $client
     * @param DataBag         $parameters
     * @param DataTransporter $dataTransporter
     *
     * @return UpdateClientCommand
     */
    public static function create(Client $client, DataBag $parameters, DataTransporter $dataTransporter): UpdateClientCommand
    {
        return new self($client, $parameters, $dataTransporter);
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return DataBag
     */
    public function getParameters(): DataBag
    {
        return $this->parameters;
    }
}
