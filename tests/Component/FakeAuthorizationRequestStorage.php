<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component;

use function array_key_exists;
use Assert\Assertion;
use Base64Url\Base64Url;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestStorage;
use Psr\Http\Message\ServerRequestInterface;

class FakeAuthorizationRequestStorage implements AuthorizationRequestStorage
{
    private const SESSION_AUTHORIZATION_REQUEST_PARAMETER_NAME = 'oauth2_server.authorization_request.session_storage';

    /**
     * @var array<string, AuthorizationRequest>
     */
    private array $storage = [];

    public static function create(): self
    {
        return new self();
    }

    public function generateId(): string
    {
        return Base64Url::encode(random_bytes(32));
    }

    public function getId(ServerRequestInterface $request): string
    {
        $authorizationRequestId = $request->getAttribute('authorization_request_id');
        Assertion::string($authorizationRequestId, 'The parameter "authorization_request_id" is missing or invalid');

        return $authorizationRequestId;
    }

    public function set(string $authorizationId, AuthorizationRequest $authorization): void
    {
        $key = $this->getKey($authorizationId);
        $this->storage[$key] = $authorization;
    }

    public function remove(string $authorizationId): void
    {
        $key = $this->getKey($authorizationId);
        unset($this->storage[$key]);
    }

    public function get(string $authorizationId): AuthorizationRequest
    {
        $key = $this->getKey($authorizationId);
        $authorization = $this->storage[$key] ?? null;
        Assertion::isInstanceOf($authorization, AuthorizationRequest::class, 'Invalid authorization ID.');

        return $authorization;
    }

    public function has(string $authorizationId): bool
    {
        $key = $this->getKey($authorizationId);

        return array_key_exists($key, $this->storage);
    }

    private function getKey(string $authorizationId): string
    {
        return sprintf('/%s/%s', self::SESSION_AUTHORIZATION_REQUEST_PARAMETER_NAME, $authorizationId);
    }
}
