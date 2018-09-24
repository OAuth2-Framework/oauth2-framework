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

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

class ParameterCheckerManager
{
    /**
     * @var ParameterChecker[]
     */
    private $parameterCheckers = [];

    public function add(ParameterChecker $parameterChecker): void
    {
        $this->parameterCheckers[] = $parameterChecker;
    }

    public function process(AuthorizationRequest $authorization): AuthorizationRequest
    {
        foreach ($this->parameterCheckers as $parameterChecker) {
            $parameterChecker->check($authorization);
        }

        return $authorization;
    }
}
