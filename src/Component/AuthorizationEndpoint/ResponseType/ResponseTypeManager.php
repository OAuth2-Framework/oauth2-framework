<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ResponseType;

use function array_key_exists;
use Assert\Assertion;

class ResponseTypeManager
{
    /**
     * @var ResponseType[]
     */
    private array $responseTypes = [];

    public static function create(): static
    {
        return new self();
    }

    public function add(ResponseType $responseType): static
    {
        $this->responseTypes[$responseType->name()] = $responseType;

        return $this;
    }

    public function has(string $responseType): bool
    {
        return array_key_exists($responseType, $this->responseTypes);
    }

    public function get(string $responseType): ResponseType
    {
        Assertion::true($this->has($responseType), sprintf('The response type "%s" is not supported.', $responseType));

        return $this->responseTypes[$responseType];
    }

    /**
     * @return string[]
     */
    public function list(): array
    {
        return array_keys($this->responseTypes);
    }

    /**
     * @return ResponseType[]
     */
    public function all(): array
    {
        return array_values($this->responseTypes);
    }
}
