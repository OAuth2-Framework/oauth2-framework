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

class ParameterCheckerManager
{
    /**
     * @var ParameterChecker[]
     */
    private $parameterCheckers = [];

    /**
     * @param ParameterChecker $parameterChecker
     *
     * @return ParameterCheckerManager
     */
    public function add(ParameterChecker $parameterChecker): self
    {
        $this->parameterCheckers[] = $parameterChecker;

        return $this;
    }

    /**
     * @param Authorization $authorization
     *
     * @return Authorization
     */
    public function process(Authorization $authorization): Authorization
    {
        foreach ($this->parameterCheckers as $parameterChecker) {
            $authorization = $parameterChecker->check($authorization);
        }

        return $authorization;
    }
}
