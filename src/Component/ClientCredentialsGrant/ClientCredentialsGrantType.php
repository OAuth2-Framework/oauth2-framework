<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientCredentialsGrant;

use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class ClientCredentialsGrantType implements GrantType
{
    public function associatedResponseTypes(): array
    {
        return [];
    }

    public function name(): string
    {
        return 'client_credentials';
    }

    public function checkRequest(ServerRequestInterface $request): void
    {
        // Nothing to do
    }

    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
    }

    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
        $client = $grantTypeData->getClient();
        if ($client->isPublic()) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_CLIENT, 'The client is not a confidential client.');
        }

        $grantTypeData->setResourceOwnerId($grantTypeData->getClient()->getPublicId());
    }
}
