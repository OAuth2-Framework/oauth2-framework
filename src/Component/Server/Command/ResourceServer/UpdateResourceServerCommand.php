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

namespace OAuth2Framework\Component\Server\Command\ResourceServer;

use OAuth2Framework\Component\Server\Command\CommandWithDataTransporter;
use OAuth2Framework\Component\Server\DataTransporter;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServer;

final class UpdateResourceServerCommand extends CommandWithDataTransporter
{
    /**
     * @var ResourceServer
     */
    private $resourceServer;

    /**
     * @var DataBag
     */
    private $parameters;

    /**
     * UpdateResourceServerCommand constructor.
     *
     * @param ResourceServer  $resourceServer
     * @param DataBag         $parameters
     * @param DataTransporter $dataTransporter
     */
    protected function __construct(ResourceServer $resourceServer, DataBag $parameters, DataTransporter $dataTransporter)
    {
        $this->resourceServer = $resourceServer;
        $this->parameters = $parameters;
        parent::__construct($dataTransporter);
    }

    /**
     * @param ResourceServer  $resourceServer
     * @param DataBag         $parameters
     * @param DataTransporter $dataTransporter
     *
     * @return UpdateResourceServerCommand
     */
    public static function create(ResourceServer $resourceServer, DataBag $parameters, DataTransporter $dataTransporter): UpdateResourceServerCommand
    {
        return new self($resourceServer, $parameters, $dataTransporter);
    }

    /**
     * @return ResourceServer
     */
    public function getResourceServer(): ResourceServer
    {
        return $this->resourceServer;
    }

    /**
     * @return DataBag
     */
    public function getParameters(): DataBag
    {
        return $this->parameters;
    }
}
