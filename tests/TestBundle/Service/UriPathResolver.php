<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Service;

use Assert\Assertion;
use function League\Uri\parse;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\Identifier;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\IdentifierResolver;

final class UriPathResolver implements IdentifierResolver
{
    public static function create(): self
    {
        return new self();
    }

    public function supports(string $resource_name): bool
    {
        $uri = parse($resource_name);

        return $uri['scheme'] === 'https'
            && $uri['user'] === null
            && $uri['path'] !== null
            && mb_strpos($uri['path'], '/+') === 0
            ;
    }

    public function resolve(string $resource): Identifier
    {
        $uri = parse($resource);
        Assertion::string($uri['path'], 'Invalid resource.');
        Assertion::string($uri['host'], 'Invalid resource.');

        return Identifier::create(mb_substr($uri['path'], 2), $uri['host'], $uri['port']);
    }
}
