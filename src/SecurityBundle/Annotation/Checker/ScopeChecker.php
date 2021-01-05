<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\SecurityBundle\Annotation\Checker;

use function Safe\sprintf;
use OAuth2Framework\SecurityBundle\Annotation\OAuth2;
use OAuth2Framework\SecurityBundle\Security\Authentication\Token\OAuth2Token;

final class ScopeChecker implements Checker
{
    public function check(OAuth2Token $token, OAuth2 $configuration): void
    {
        $scope = $configuration->getScope();
        if (null === $scope) {
            return;
        }

        $scopes = explode(' ', $scope);
        $tokenScope = $token->getAccessToken()->getParameter()->has('scope') ? explode(' ', $token->getAccessToken()->getParameter()->get('scope')) : [];
        $diff = array_diff($scopes, $tokenScope);

        if (0 !== \count($diff)) {
            throw new \Exception(sprintf('Insufficient scope. The required scope is "%s"', $configuration->getScope()));
        }
    }
}
