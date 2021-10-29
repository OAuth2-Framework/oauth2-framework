<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ResourceServerAuthentication;

use function array_key_exists;
use function in_array;
use InvalidArgumentException;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticationMethodManager
{
    /**
     * @var AuthenticationMethod[]
     */
    private array $methods = [];

    /**
     * @var string[]
     */
    private array $names = [];

    public function add(AuthenticationMethod $method): void
    {
        $class = $method::class;
        $this->methods[$class] = $method;
        foreach ($method->getSupportedMethods() as $name) {
            $this->names[$name] = $class;
        }
    }

    /**
     * @return string[]
     */
    public function list(): array
    {
        return array_keys($this->names);
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->names);
    }

    public function get(string $name): AuthenticationMethod
    {
        if (! $this->has($name)) {
            throw new InvalidArgumentException(sprintf(
                'The resource server authentication method "%s" is not supported. Please use one of the following values: %s',
                $name,
                implode(', ', $this->list())
            ));
        }
        $class = $this->names[$name];

        return $this->methods[$class];
    }

    /**
     * @return AuthenticationMethod[]
     */
    public function all(): array
    {
        return array_values($this->methods);
    }

    /**
     * @param mixed|null $resourceServerCredentials The resource server credentials found in the request
     */
    public function findResourceServerIdAndCredentials(
        ServerRequestInterface $request,
        AuthenticationMethod &$authenticationMethod = null,
        mixed &$resourceServerCredentials = null
    ): ?ResourceServerId {
        $resourceServerId = null;
        $resourceServerCredentials = null;
        foreach ($this->methods as $method) {
            $tempResourceServerId = $method->findResourceServerIdAndCredentials($request, $resourceServerCredentials);
            if ($tempResourceServerId === null) {
                continue;
            }
            if ($resourceServerId === null) {
                $resourceServerId = $tempResourceServerId;
                $authenticationMethod = $method;

                continue;
            }

            throw OAuth2Error::invalidRequest(
                'Only one authentication method may be used to authenticate the resource server.'
            );
        }

        return $resourceServerId;
    }

    public function isResourceServerAuthenticated(
        ServerRequestInterface $request,
        ResourceServer $resourceServer,
        AuthenticationMethod $authenticationMethod,
        mixed $resourceServerCredentials
    ): bool {
        if (in_array($resourceServer->getAuthenticationMethod(), $authenticationMethod->getSupportedMethods(), true)) {
            return $authenticationMethod->isResourceServerAuthenticated(
                $resourceServer,
                $resourceServerCredentials,
                $request
            );
        }

        return false;
    }

    /**
     * @return string[]
     */
    public function getSchemesParameters(): array
    {
        $schemes = [];
        foreach ($this->all() as $method) {
            $schemes = array_merge($schemes, $method->getSchemesParameters());
        }

        return $schemes;
    }
}
