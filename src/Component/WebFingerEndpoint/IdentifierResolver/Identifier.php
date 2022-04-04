<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

final class Identifier
{
    public function __construct(
        private readonly string $id,
        private readonly string $domain,
        private readonly ?int $port
    ) {
    }

    public static function create(string $id, string $domain, ?int $port): static
    {
        return new self($id, $domain, $port);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }
}
