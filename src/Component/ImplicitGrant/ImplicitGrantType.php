<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ImplicitGrant;

use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class ImplicitGrantType implements GrantType
{
    public function associatedResponseTypes(): array
    {
        return ['token'];
    }

    public function name(): string
    {
        return 'implicit';
    }

    public function checkRequest(ServerRequestInterface $request): void
    {
        throw OAuth2Error::invalidGrant('The implicit grant type cannot be called from the token endpoint.');
    }

    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
        throw OAuth2Error::invalidGrant('The implicit grant type cannot be called from the token endpoint.');
    }

    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
        throw OAuth2Error::invalidGrant('The implicit grant type cannot be called from the token endpoint.');
    }
}
