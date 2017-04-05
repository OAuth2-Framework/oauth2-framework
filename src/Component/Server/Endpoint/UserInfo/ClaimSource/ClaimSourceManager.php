<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Endpoint\UserInfo\ClaimSource;

use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;

final class ClaimSourceManager
{
    /**
     * @var ClaimSourceInterface[]
     */
    private $claimSources = [];

    /**
     * @param ClaimSourceInterface $claimSource
     *
     * @return ClaimSourceManager
     */
    public function add(ClaimSourceInterface $claimSource): ClaimSourceManager
    {
        $this->claimSources[] = $claimSource;

        return $this;
    }

    /**
     * @return ClaimSourceInterface[]
     */
    public function all(): array
    {
        return $this->claimSources;
    }

    /**
     * @param UserAccountInterface $userAccount
     * @param string[]             $scope
     * @param array                $claims
     *
     * @return array
     */
    public function getUserInfo(UserAccountInterface $userAccount, array $scope, array $claims)
    {
        $claims = [
            '_claim_names' => [],
            '_claimSources' => [],
        ];
        $i = 0;

        foreach ($this->all() as $claimSource) {
            $result = $claimSource->getUserInfo($userAccount, $scope, $claims);
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
                $claims['_claimSources'][$src] = $result->getSource();
            }
        }

        return empty($claims['_claim_names']) ? [] : $claims;
    }
}
