<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Entity;

use function in_array;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\Identifier;
use OAuth2Framework\Component\WebFingerEndpoint\Link;
use OAuth2Framework\Component\WebFingerEndpoint\ResourceDescriptor;
use OAuth2Framework\Component\WebFingerEndpoint\ResourceRepository as ResourceRepositoryInterface;

final class ResourceRepository implements ResourceRepositoryInterface
{
    /**
     * @var ResourceDescriptor[]
     */
    private array $resources = [];

    public function __construct()
    {
        $this->resources['john'] = ResourceDescriptor::create(
            'acct:john@my-service.com:443',
            ['https://my-service.com:443/+john'],
            [],
            [new Link('http://openid.net/specs/connect/1.0/issuer', null, 'https://server.example.com', [], [])]
        );
    }

    public function find(string $resource, Identifier $identifier): ?ResourceDescriptor
    {
        $resourceDescriptor = $this->resources[$identifier->getId()] ?? null;
        if ($resourceDescriptor === null) {
            return null;
        }

        if ($resource !== $resourceDescriptor->getSubject() && ! in_array(
            $resource,
            $resourceDescriptor->getAliases(),
            true
        )) {
            return null;
        }

        return $resourceDescriptor;
    }
}
