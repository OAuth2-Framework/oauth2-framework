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

namespace OAuth2Framework\ServerBundle\Form\Model;

class AuthorizationModel
{
    /**
     * @var bool
     */
    private $saveConfiguration;

    /**
     * @var array
     */
    private $scopes = [];

    /**
     * @return bool
     */
    public function isConfigurationSaved()
    {
        return $this->saveConfiguration;
    }

    public function setSaveConfiguration(bool $saveConfiguration)
    {
        $this->saveConfiguration = $saveConfiguration;
    }

    /**
     * @return string[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @param string[] $scopes
     */
    public function setScopes(array $scopes)
    {
        $this->scopes = $scopes;
    }
}
