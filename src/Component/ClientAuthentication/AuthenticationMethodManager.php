<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientAuthentication;

use function array_key_exists;
use Assert\Assertion;
use function in_array;
use function is_string;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
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

    public static function create(): self
    {
        return new self();
    }

    public function add(AuthenticationMethod $method): self
    {
        $class = $method::class;
        $this->methods[$class] = $method;
        foreach ($method->getSupportedMethods() as $name) {
            $this->names[$name] = $class;
        }

        return $this;
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
        Assertion::true(
            $this->has($name),
            sprintf(
                'The token endpoint authentication method "%s" is not supported. Please use one of the following values: %s',
                $name,
                implode(', ', $this->list())
            )
        );
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
     * @param mixed|null $clientCredentials The client credentials found in the request
     */
    public function findClientIdAndCredentials(
        ServerRequestInterface $request,
        AuthenticationMethod &$authenticationMethod = null,
        mixed &$clientCredentials = null
    ): ?ClientId {
        $clientId = null;
        $clientCredentials = null;
        foreach ($this->methods as $method) {
            $tempClientId = $method->findClientIdAndCredentials($request, $clientCredentials);
            if ($tempClientId === null) {
                continue;
            }
            if ($clientId === null) {
                $clientId = $tempClientId;
                $authenticationMethod = $method;

                continue;
            }
            if (! $method instanceof None && ! $authenticationMethod instanceof None) {
                throw OAuth2Error::invalidRequest(
                    'Only one authentication method may be used to authenticate the client.'
                );
            }
            if (! $method instanceof None) {
                $authenticationMethod = $method;
            }
        }

        return $clientId;
    }

    /**
     * @param mixed $clientCredentials The client credentials found in the request
     */
    public function isClientAuthenticated(
        ServerRequestInterface $request,
        Client $client,
        AuthenticationMethod $authenticationMethod,
        mixed $clientCredentials
    ): bool {
        if (in_array($client->get('token_endpoint_auth_method'), $authenticationMethod->getSupportedMethods(), true)) {
            if ($client->areClientCredentialsExpired() === false) {
                return $authenticationMethod->isClientAuthenticated($client, $clientCredentials, $request);
            }
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

    public function getSchemes(array $additionalAuthenticationParameters = []): array
    {
        $schemes = [];
        foreach ($this->all() as $method) {
            $schemes = array_merge($schemes, $method->getSchemesParameters());
        }
        foreach ($schemes as $k => $scheme) {
            $schemes[$k] = $this->appendParameters($scheme, $additionalAuthenticationParameters);
        }

        return $schemes;
    }

    private function appendParameters(string $scheme, array $parameters): string
    {
        $position = mb_strpos($scheme, ' ', 0, 'utf-8');
        $add_comma = ! ($position === false);

        foreach ($parameters as $key => $value) {
            $value = is_string($value) ? sprintf('"%s"', $value) : $value;
            if ($add_comma === false) {
                $add_comma = true;
                $scheme = sprintf('%s %s=%s', $scheme, $key, $value);
            } else {
                $scheme = sprintf('%s,%s=%s', $scheme, $key, $value);
            }
        }

        return $scheme;
    }
}
