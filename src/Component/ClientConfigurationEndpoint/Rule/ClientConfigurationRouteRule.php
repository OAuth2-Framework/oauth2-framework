<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientConfigurationEndpoint\Rule;

use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

abstract class ClientConfigurationRouteRule implements Rule
{
    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        $validatedParameters->set('registration_access_token', $this->generateRegistrationAccessToken());
        $validatedParameters->set('registration_client_uri', $this->getRegistrationClientUri($clientId));

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }

    abstract protected function getRegistrationClientUri(ClientId $clientId): string;

    abstract protected function generateRegistrationAccessToken(): string;
}
