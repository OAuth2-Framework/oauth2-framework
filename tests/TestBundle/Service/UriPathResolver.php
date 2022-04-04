<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Service;

use Assert\Assertion;
use League\Uri\Components\UserInfo;
use League\Uri\Uri;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\Identifier;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\IdentifierResolver;

final class UriPathResolver implements IdentifierResolver
{
    public static function create(): self
    {
        return new self();
    }

    public function supports(string $resource): bool
    {
        $uri = Uri::createFromString($resource);
        $userInfo = UserInfo::createFromUri($uri);

        return $uri->getScheme() === 'https'
            && $userInfo->getUser() === null
            && str_starts_with($uri->getPath(), '/+')
            ;
    }

    public function resolve(string $resource): Identifier
    {
        $uri = Uri::createFromString($resource);
        Assertion::string($uri->getHost(), 'Invalid resource.');

        return Identifier::create(mb_substr($uri->getPath(), 2), $uri->getHost(), $uri->getPort());
    }
}
