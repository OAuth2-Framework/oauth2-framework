<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant;

use function count;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class ResourceOwnerPasswordCredentialsGrantType implements GrantType
{
    public function __construct(
        private ResourceOwnerPasswordCredentialManager $resourceOwnerPasswordCredentialManager
    ) {
    }

    public static function create(
        ResourceOwnerPasswordCredentialManager $resourceOwnerPasswordCredentialManager
    ): self {
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

        $diff = array_diff($requiredParameters, array_keys($parameters));
        if (count($diff) !== 0) {
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
