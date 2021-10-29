<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Repository;

use function array_key_exists;
use InvalidArgumentException;
use OAuth2Framework\Component\Scope\Scope as ScopeInterface;
use OAuth2Framework\Component\Scope\ScopeRepository as ScopeRepositoryInterface;
use OAuth2Framework\Tests\TestBundle\Entity\Scope;

final class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * @var ScopeInterface[]
     */
    private array $scopes = [];

    public function __construct()
    {
        $this->scopes['openid'] = new Scope('openid');
        $this->scopes['scope1'] = new Scope('scope1');
    }

    public function has(string $scope): bool
    {
        return array_key_exists($scope, $this->scopes);
    }

    public function get(string $scope): ScopeInterface
    {
        if (! $this->has($scope)) {
            throw new InvalidArgumentException(sprintf('The scope "%s" is not supported.', $scope));
        }

        return $this->scopes[$scope];
    }

    public function all(): array
    {
        return array_values($this->scopes);
    }
}
