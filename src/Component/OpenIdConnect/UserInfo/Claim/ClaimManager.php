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

use OAuth2Framework\Component\Core\User\User;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

class ClaimManager
{
    /**
     * @var Claim[]
     */
    private $claims = [];

    public function add(Claim $claim): void
    {
        $this->claims[$claim->name()] = $claim;
    }

    /**
     * @return Claim[]
     */
    public function list(): array
    {
        return \array_keys($this->claims);
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
        return \array_key_exists($claim, $this->claims);
    }

    public function get(string $claim): Claim
    {
        if (!$this->has($claim)) {
            throw new \InvalidArgumentException(\Safe\sprintf('Unsupported claim "%s".', $claim));
        }

        return $this->claims[$claim];
    }

    public function getUserInfo(User $user, UserAccount $userAccount, array $claims, array $claimLocales): array
    {
        $result = [];
        $claimLocales[] = null;
        foreach ($claims as $claimName => $config) {
            if ($this->has($claimName)) {
                $claim = $this->get($claimName);
                foreach ($claimLocales as $claimLocale) {
                    if ($claim->isAvailableForUserAccount($user, $userAccount, $claimLocale)) {
                        $value = $claim->getForUserAccount($user, $userAccount, $claimLocale);
                        switch (true) {
                            case \is_array($config) && \array_key_exists('value', $config):
                                if ($claim === $config['value']) {
                                    $result[$claimName] = $value;
                                }

                                break;
                            case \is_array($config) && \array_key_exists('values', $config) && \is_array($config['values']):
                                if (\in_array($claim, $config['values'], true)) {
                                    $result[$claimName] = $value;
                                }

                                break;
                            default:
                                $result[$claimName] = $value;
                        }
                    }
                }
            }
        }

        return $result;
    }
}
