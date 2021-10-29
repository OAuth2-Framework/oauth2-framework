<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint;

use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\Identifier;

interface ResourceRepository
{
    public function find(string $resource, Identifier $identifier): ?ResourceDescriptor;
}
