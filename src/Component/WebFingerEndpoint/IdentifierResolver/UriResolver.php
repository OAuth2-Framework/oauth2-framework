<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

use League\Uri\Components\UserInfo;
use League\Uri\Uri;

final class UriResolver implements IdentifierResolver
{
    public static function create(): static
    {
        return new self();
    }

    public function supports(string $resource): bool
    {
        $uri = Uri::createFromString($resource);
        $userInfo = UserInfo::createFromUri($uri);

        return $uri->getScheme() === 'https' && $uri->getHost() !== null && $userInfo->getUser() !== null;
    }

    public function resolve(string $resource): Identifier
    {
        $uri = Uri::createFromString($resource);
        $userInfo = UserInfo::createFromUri($uri);

        return Identifier::create($userInfo->getUser(), $uri->getHost(), $uri->getPort());
    }
}
