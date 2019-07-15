<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\WebFingerEndpoint;

class ResourceDescriptor implements \JsonSerializable
{
    /**
     * @var null|string
     */
    private $subject;

    /**
     * @var array|string[]
     */
    private $aliases;

    /**
     * @var array|mixed[]
     */
    private $properties;

    /**
     * @var array|Link[]
     */
    private $links;

    /**
     * @param string[] $aliases
     * @param mixed[]  $properties
     * @param Link[]   $links
     */
    public function __construct(?string $subject, array $aliases, array $properties, array $links)
    {
        $this->subject = $subject;
        $this->aliases = $aliases;
        $this->properties = $properties;
        $this->links = $links;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function addAlias(string $key, string $alias): void
    {
        $this->properties[$key] = $alias;
    }

    /**
     * @return mixed[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function addProperty(string $key, string $property): void
    {
        $this->properties[$key] = $property;
    }

    /**
     * @return Link[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    public function addLink(string $key, Link $link): void
    {
        $this->links[$key] = $link;
    }

    public function jsonSerialize(): array
    {
        $result = [
            'subject' => $this->subject,
            'aliases' => $this->aliases,
            'properties' => $this->properties,
            'links' => $this->links,
        ];

        foreach ($result as $k => $v) {
            if (null === $v || (\is_array($v) && 0 === \count($v))) {
                unset($result[$k]);
            }
        }

        return $result;
    }
}
