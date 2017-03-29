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

namespace OAuth2Framework\Component\Server\Command;

use OAuth2Framework\Component\Server\DataTransporter;

abstract class CommandWithDataTransporter
{
    /**
     * @var DataTransporter|null
     */
    private $dataTransporter;

    /**
     * CommandWithDataTransporter constructor.
     *
     * @param DataTransporter|null $dataTransporter
     */
    protected function __construct(?DataTransporter $dataTransporter)
    {
        $this->dataTransporter = $dataTransporter;
    }

    /**
     * @return null|DataTransporter
     */
    public function getDataTransporter(): ?DataTransporter
    {
        return $this->dataTransporter;
    }
}
