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

namespace OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker;

use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;

final class ParameterCheckerManager
{
    /**
     * @var ParameterCheckerInterface[]
     */
    private $parameterCheckers = [];

    /**
     * @param ParameterCheckerInterface $parameterChecker
     *
     * @return ParameterCheckerManager
     */
    public function add(ParameterCheckerInterface $parameterChecker): ParameterCheckerManager
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
        return call_user_func($this->callableForNextExtension(0), $authorization, []);
    }

    /**
     * @param int $index
     *
     * @return \Closure
     */
    private function callableForNextExtension($index)
    {
        if (!array_key_exists($index, $this->parameterCheckers)) {
            return function (Authorization $authorization): Authorization {
                return $authorization;
            };
        }
        $parameterChecker = $this->parameterCheckers[$index];

        return function (Authorization $authorization) use ($parameterChecker, $index): Authorization {
            return $parameterChecker->process($authorization, $this->callableForNextExtension($index + 1));
        };
    }
}
