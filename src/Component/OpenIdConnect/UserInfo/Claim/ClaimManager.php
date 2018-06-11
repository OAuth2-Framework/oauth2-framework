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

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim;

use OAuth2Framework\Component\Core\UserAccount\UserAccount;

class ClaimManager
{
    /**
     * @var Claim[]
     */
    private $claims = [];

    /**
     * @param Claim $claim
     *
     * @return ClaimManager
     */
    public function add(Claim $claim): self
    {
        $this->claims[$claim->name()] = $claim;

        return $this;
    }

    /**
     * @return Claim[]
     */
    public function list(): array
    {
        return array_keys($this->claims);
    }

    /**
     * @return Claim[]
     */
    public function all(): array
    {
        return $this->claims;
    }

    public function has(string $claim): bool
    {
        return array_key_exists($claim, $this->claims);
    }

    public function get(string $claim): Claim
    {
        if (!$this->has($claim)) {
            throw new \InvalidArgumentException(sprintf('Unsupported claim "%s".', $claim));
        }

        return $this->claims[$claim];
    }

    public function getUserInfo(UserAccount $userAccount, array $claims, array $claimLocales): array
    {
        $result = [];
        $claimLocale[] = null;
        foreach ($claims as $claimName => $config) {
            if ($this->has($claimName)) {
                $claim = $this->get($claimName);
                foreach ($claimLocales as $claimLocale) {
                    if ($claim->isAvailableForUserAccount($userAccount, $claimLocale)) {
                        $result[$claimName] = $claim->getForUserAccount($userAccount, $claimLocale);
                    }
                }
            }
        }

        return $result;
    }
}
