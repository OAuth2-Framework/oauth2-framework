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

final class CreateResourceServerCommand extends CommandWithDataTransporter
{
    /**
     * @var DataBag
     */
    private $parameters;

    /**
     * CreateResourceServerCommand constructor.
     *
     * @param DataBag              $parameters
     * @param DataTransporter|null $dataTransporter
     */
    protected function __construct(DataBag $parameters, ?DataTransporter $dataTransporter)
    {
        $this->parameters = $parameters;
        parent::__construct($dataTransporter);
    }

    /**
     * @param DataBag              $parameters
     * @param DataTransporter|null $dataTransporter
     *
     * @return CreateResourceServerCommand
     */
    public static function create(DataBag $parameters, ?DataTransporter $dataTransporter): CreateResourceServerCommand
    {
        return new self($parameters, $dataTransporter);
    }

    /**
     * @return DataBag
     */
    public function getParameters(): DataBag
    {
        return $this->parameters;
    }
}
