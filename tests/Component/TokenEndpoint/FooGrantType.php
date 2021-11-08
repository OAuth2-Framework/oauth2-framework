<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\TokenEndpoint;

use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class FooGrantType implements GrantType
{
    public static function create(): self
    {
        return new self();
    }

    public function associatedResponseTypes(): array
    {
        return [];
    }

    public function name(): string
    {
        return 'foo';
    }

    public function checkRequest(ServerRequestInterface $request): void
    {
        //Nothing to do
    }

    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
    }

    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
        $grantTypeData->setResourceOwnerId($grantTypeData->getClient()->getPublicId());
    }
}
