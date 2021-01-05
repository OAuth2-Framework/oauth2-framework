<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant;

use function Safe\sprintf;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class ResourceOwnerPasswordCredentialsGrantType implements GrantType
{
    private ResourceOwnerPasswordCredentialManager $resourceOwnerPasswordCredentialManager;

    public function __construct(ResourceOwnerPasswordCredentialManager $resourceOwnerPasswordCredentialManager)
    {
        $this->resourceOwnerPasswordCredentialManager = $resourceOwnerPasswordCredentialManager;
    }

    public function associatedResponseTypes(): array
    {
        return [];
    }

    public function name(): string
    {
        return 'password';
    }

    public function checkRequest(ServerRequestInterface $request): void
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $requiredParameters = ['username', 'password'];

        $diff = array_diff($requiredParameters, array_keys($parameters));
        if (0 !== \count($diff)) {
            throw OAuth2Error::invalidRequest(sprintf('Missing grant type parameter(s): %s.', implode(', ', $diff)));
        }
    }

    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
    }

    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $username = $parameters['username'];
        $password = $parameters['password'];

        $resourceOwnerId = $this->resourceOwnerPasswordCredentialManager->findResourceOwnerIdWithUsernameAndPassword($username, $password);
        if (null === $resourceOwnerId) {
            throw OAuth2Error::invalidGrant('Invalid username and password combination.');
        }

        $grantTypeData->setResourceOwnerId($resourceOwnerId);
    }
}
