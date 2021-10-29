<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Annotation\Checker;

use Exception;
use OAuth2Framework\SecurityBundle\Annotation\OAuth2;
use OAuth2Framework\SecurityBundle\Security\Authentication\Token\OAuth2Token;

final class ClientIdChecker implements Checker
{
    public function check(OAuth2Token $token, OAuth2 $configuration): void
    {
        if ($configuration->getClientId() === null) {
            return;
        }

        if ($configuration->getClientId() !== $token->getClientId()) {
            throw new Exception('Client not authorized.');
        }
    }
}
