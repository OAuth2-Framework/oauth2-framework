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

namespace OAuth2Framework\ServerBundle\Annotation\Checker;

use OAuth2Framework\ServerBundle\Annotation\OAuth2;
use OAuth2Framework\ServerBundle\Security\Authentication\Token\OAuth2Token;

class ScopeChecker implements Checker
{
    /**
     * {@inheritdoc}
     */
    public function check(OAuth2Token $token, OAuth2 $configuration): ?string
    {
        $scope = $configuration->getScope();
        if (null === $scope) {
            return null;
        }

        $scopes = explode(' ', $scope);
        $tokenScope = $token->getAccessToken()->hasParameter('scope') ? explode(' ', $token->getAccessToken()->getParameter('scope')) : [];
        $diff = array_diff($scopes, $tokenScope);

        if (!empty($diff)) {
            return sprintf('Insufficient scope. The scope rule is: %s', $configuration->getScope());
        }

        return  null;
    }
}
