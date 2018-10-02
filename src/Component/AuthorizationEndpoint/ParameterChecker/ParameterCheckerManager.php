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
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\Core\Message\OAuth2Error;

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

    public function check(AuthorizationRequest $authorization): void
    {
        foreach ($this->parameterCheckers as $parameterChecker) {
            try {
                $parameterChecker->check($authorization);
            } catch (OAuth2AuthorizationException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new OAuth2AuthorizationException(OAuth2Error::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
            }
        }
    }
}
