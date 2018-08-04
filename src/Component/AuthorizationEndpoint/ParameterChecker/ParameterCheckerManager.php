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

namespace OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\Core\Message\OAuth2Message;

class ParameterCheckerManager
{
    /**
     * @var ParameterChecker[]
     */
    private $parameterCheckers = [];

    /**
     * @return ParameterCheckerManager
     */
    public function add(ParameterChecker $parameterChecker): self
    {
        $this->parameterCheckers[] = $parameterChecker;

        return $this;
    }

    /**
     * @throws OAuth2Message
     * @throws OAuth2AuthorizationException
     */
    public function process(Authorization $authorization): Authorization
    {
        foreach ($this->parameterCheckers as $parameterChecker) {
            $authorization = $parameterChecker->check($authorization);
        }

        return $authorization;
    }
}
