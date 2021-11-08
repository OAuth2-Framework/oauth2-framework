<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Annotation\Checker;

use function count;
use Exception;
use OAuth2Framework\SecurityBundle\Annotation\OAuth2;
use OAuth2Framework\SecurityBundle\Security\Authentication\OAuth2Token;

final class ScopeChecker implements Checker
{
    public function check(OAuth2Token $token, OAuth2 $configuration): void
    {
        $scope = $configuration->getScope();
        if ($scope === null) {
            return;
        }

        $scopes = explode(' ', $scope);
        $tokenScope = $token->getAccessToken()
            ->getParameter()
            ->has('scope') ? explode(' ', $token->getAccessToken()->getParameter()->get('scope')) : [];
        $diff = array_diff($scopes, $tokenScope);

        if (count($diff) !== 0) {
            throw new Exception(sprintf('Insufficient scope. The required scope is "%s"', $configuration->getScope()));
        }
    }
}
