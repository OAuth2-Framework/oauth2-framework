<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Service;

use Assert\Assertion;
use function League\Uri\parse;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\Identifier;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\IdentifierResolver;

final class UriPathResolver implements IdentifierResolver
{
    public function supports(string $resource_name): bool
    {
        $uri = parse($resource_name);

        return $uri['scheme'] === 'https'
            && $uri['user'] === null
            && $uri['path'] !== null
            && mb_substr($uri['path'], 0, 2) === '/+'
            ;
    }

    public function resolve(string $resource_name): Identifier
    {
        $uri = parse($resource_name);
        Assertion::string($uri['path'], 'Invalid resource.');
        Assertion::string($uri['host'], 'Invalid resource.');

        return new Identifier(mb_substr($uri['path'], 2), $uri['host'], $uri['port']);
    }
}
