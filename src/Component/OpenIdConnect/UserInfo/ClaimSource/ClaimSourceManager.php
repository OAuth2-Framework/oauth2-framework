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

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\ClaimSource;

use OAuth2Framework\Component\Core\UserAccount\UserAccount;

class ClaimSourceManager
{
    /**
     * @var ClaimSource[]
     */
    private $claimSources = [];

    /**
     * @param ClaimSource $claimSource
     *
     * @return ClaimSourceManager
     */
    public function add(ClaimSource $claimSource): self
    {
        $this->claimSources[] = $claimSource;

        return $this;
    }

    /**
     * @return ClaimSource[]
     */
    public function all(): array
    {
        return $this->claimSources;
    }

    /**
     * @param UserAccount $userAccount
     * @param string[]    $scope
     * @param array       $previousClaims
     *
     * @return array
     */
    public function getUserInfo(UserAccount $userAccount, array $scope, array $previousClaims)
    {
        $claims = [
            '_claim_names' => [],
            '_claim_sources' => [],
        ];
        $i = 0;

        foreach ($this->all() as $claimSource) {
            $result = $claimSource->getUserInfo($userAccount, $scope, $previousClaims);
            if (null !== $result) {
                ++$i;
                $src = sprintf('src%d', $i);
                $_claim_names = [];
                foreach ($result->getAvailableClaims() as $claim) {
                    if ('sub' !== $claim) {
                        $_claim_names[$claim] = $src;
                    }
                }
                $claims['_claim_names'] = array_merge(
                    $claims['_claim_names'],
                    $_claim_names
                );
                $claims['_claim_sources'][$src] = $result->getSource();
            }
        }

        return empty($claims['_claim_names']) ? [] : $previousClaims;
    }
}
