<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Annotation\Checker;

use Exception;
use OAuth2Framework\SecurityBundle\Annotation\OAuth2;
use OAuth2Framework\SecurityBundle\Security\Authentication\OAuth2Token;

final class ResourceOwnerIdChecker implements Checker
{
    public function check(OAuth2Token $token, OAuth2 $configuration): void
    {
        if ($configuration->getResourceOwnerId() === null) {
            return;
        }

        if ($configuration->getResourceOwnerId() !== $token->getResourceOwnerId()) {
            throw new Exception('Resource owner not authorized.');
        }
    }
}
