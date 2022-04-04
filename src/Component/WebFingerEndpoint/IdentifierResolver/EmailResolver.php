<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

use InvalidArgumentException;
use League\Uri\Components\UserInfo;
use League\Uri\Uri;

final class EmailResolver implements IdentifierResolver
{
    public static function create(): static
    {
        return new self();
    }

    public function supports(string $resource): bool
    {
        $uri = Uri::createFromString('https://' . $resource);
        $userInfo = UserInfo::createFromUri($uri);

        return $uri->getScheme() === 'https' && $userInfo->getUser() !== null && $userInfo->getPass() === null && $uri->getHost() !== null && $uri->getPath() === '' && $uri->getQuery() === null && $uri->getFragment() === null;
    }

    public function resolve(string $resource): Identifier
    {
        $uri = Uri::createFromString('https://' . $resource);
        $userInfo = UserInfo::createFromUri($uri);

        if ($userInfo->getUser() === null || $uri->getHost() === null) {
            throw new InvalidArgumentException('Invalid resource.');
        }

        return Identifier::create($userInfo->getUser(), $uri->getHost(), $uri->getPort());
    }
}
