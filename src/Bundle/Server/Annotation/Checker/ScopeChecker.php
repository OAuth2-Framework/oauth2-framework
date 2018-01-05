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

namespace OAuth2Framework\Bundle\Server\Annotation\Checker;

use OAuth2Framework\Bundle\Server\Annotation\OAuth2;
use OAuth2Framework\Bundle\Server\Security\Authentication\Token\OAuth2Token;

final class ScopeChecker implements CheckerInterface
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
        $diff = array_diff($scopes, $token->getAccessToken()->getScopes());

        if (!empty($diff)) {
            return sprintf('Insufficient scope. The scope rule is: %s', $configuration->getScope());
        }

        return  null;
    }
}
