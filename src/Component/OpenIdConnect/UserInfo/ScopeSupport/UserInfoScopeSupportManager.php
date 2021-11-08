<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport;

use function array_key_exists;
use InvalidArgumentException;

class UserInfoScopeSupportManager
{
    /**
     * @var UserInfoScopeSupport[]
     */
    private array $userinfoScopeSupports;

    public function __construct()
    {
        $this->userinfoScopeSupports = [
            'openid' => new OpenIdScopeSupport(),
        ];
    }

    public function add(UserInfoScopeSupport $userinfoScopeSupport): self
    {
        $this->userinfoScopeSupports[$userinfoScopeSupport->getName()] = $userinfoScopeSupport;

        return $this;
    }

    public function has(string $scope): bool
    {
        return array_key_exists($scope, $this->userinfoScopeSupports);
    }

    public function get(string $scope): UserInfoScopeSupport
    {
        if (! $this->has($scope)) {
            throw new InvalidArgumentException(sprintf('The userinfo scope "%s" is not supported.', $scope));
        }

        return $this->userinfoScopeSupports[$scope];
    }

    /**
     * @return UserInfoScopeSupport[]
     */
    public function all(): array
    {
        return $this->userinfoScopeSupports;
    }
}
