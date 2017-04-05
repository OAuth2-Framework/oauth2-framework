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
use OAuth2Framework\Component\Server\Response\OAuth2Exception;

interface ParameterCheckerInterface
{
    /**
     * @param Authorization $authorization
     * @param callable      $next
     *
     * @throws OAuth2Exception
     *
     * @return Authorization
     */
    public function process(Authorization $authorization, callable $next): Authorization;
}
