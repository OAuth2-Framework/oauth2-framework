<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant;

use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class ResourceOwnerPasswordCredentialsGrantType implements GrantType
{
    public function __construct(
        private readonly ResourceOwnerPasswordCredentialManager $resourceOwnerPasswordCredentialManager
    ) {
    }

    public static function create(
        ResourceOwnerPasswordCredentialManager $resourceOwnerPasswordCredentialManager
    ): static {
        return new self($resourceOwnerPasswordCredentialManager);
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
        foreach ($requiredParameters as $requiredParameter) {
            if (! $parameters->has($requiredParameter)) {
                throw OAuth2Error::invalidRequest(sprintf('Missing grant type parameter(s): %s.', $requiredParameter));
            }
        }
    }

    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
    }

    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $username = $parameters->get('username');
        $password = $parameters->get('password');

        $resourceOwnerId = $this->resourceOwnerPasswordCredentialManager->findResourceOwnerIdWithUsernameAndPassword(
            $username,
            $password
        );
        if ($resourceOwnerId === null) {
            throw OAuth2Error::invalidGrant('Invalid username and password combination.');
        }

        $grantTypeData->setResourceOwnerId($resourceOwnerId);
    }
}
