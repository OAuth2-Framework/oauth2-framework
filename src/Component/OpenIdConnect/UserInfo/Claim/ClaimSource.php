<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim;

use OAuth2Framework\Component\Core\UserAccount\UserAccount;

interface ClaimSource
{
    /**
     * @param string[] $scope
     */
    public function getUserInfo(UserAccount $userAccount, array $scope, array $claims): ?Source;
}
