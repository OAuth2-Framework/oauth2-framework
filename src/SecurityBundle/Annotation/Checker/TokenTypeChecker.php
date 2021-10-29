<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Annotation\Checker;

use Exception;
use OAuth2Framework\SecurityBundle\Annotation\OAuth2;
use OAuth2Framework\SecurityBundle\Security\Authentication\Token\OAuth2Token;

final class TokenTypeChecker implements Checker
{
    public function check(OAuth2Token $token, OAuth2 $configuration): void
    {
        if ($configuration->getTokenType() === null) {
            return;
        }

        if ($configuration->getTokenType() !== $token->getTokenType()) {
            throw new Exception(sprintf(
                'Token type "%s" not allowed. Please use "%s"',
                $token->getTokenType(),
                $configuration->getTokenType()
            ));
        }
    }
}
