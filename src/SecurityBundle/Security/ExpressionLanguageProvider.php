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

namespace OAuth2Framework\SecurityBundle\Security;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\SecurityBundle\Security\Authentication\Token\OAuth2Token;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

final class ExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @return ExpressionFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('has_oauth2_scope', static function ($scope) {
                return sprintf('in_array(%s, $scopes)', $scope);
            }, static function (array $variables, $scope) {
                $accessToken = self::getAccessToken($variables);
                if (null === $accessToken) {
                    return false;
                }

                return self::hasScope($accessToken, $scope);
            }),
        ];
    }

    private static function hasScope(AccessToken $accessToken, string $scope): bool
    {
        $parameters = $accessToken->getParameter();
        if (!$parameters->has('scope')) {
            return false;
        }
        $availableScope = $parameters->get('scope');
        if (!\is_string($availableScope)) {
            return false;
        }
        $availableScopes = explode(' ', $availableScope);

        return \in_array($scope, $availableScopes, true);
    }

    private static function getSecurityToken(array $variables): ?OAuth2Token
    {
        $securityToken = $variables['token'] ?? null;
        if (!$securityToken instanceof OAuth2Token) {
            return null;
        }

        return $securityToken;
    }

    private static function getAccessToken(array $variables): ?AccessToken
    {
        $securityToken = self::getSecurityToken($variables);
        if (null === $securityToken) {
            return null;
        }

        return $securityToken->getAccessToken();
    }
}
