<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

class Identifier
{
    public function __construct(
        private string $id,
        private string $domain,
        private ?int $port
    ) {
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
