<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Annotation\Checker;

use OAuth2Framework\SecurityBundle\Annotation\OAuth2;
use OAuth2Framework\SecurityBundle\Security\Authentication\OAuth2Token;

interface Checker
{
    public function check(OAuth2Token $token, OAuth2 $configuration): void;
}
