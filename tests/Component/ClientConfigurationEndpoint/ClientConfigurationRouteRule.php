<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientConfigurationEndpoint;

use OAuth2Framework\Component\ClientConfigurationEndpoint\Rule\ClientConfigurationRouteRule as Base;
use OAuth2Framework\Component\Core\Client\ClientId;

class ClientConfigurationRouteRule extends Base
{
    protected function getRegistrationClientUri(ClientId $clientId): string
    {
        return sprintf('https://www.example.com/client/%s', $clientId->getValue());
    }

    protected function generateRegistrationAccessToken(): string
    {
        return base64_encode(random_bytes(16));
    }
}
