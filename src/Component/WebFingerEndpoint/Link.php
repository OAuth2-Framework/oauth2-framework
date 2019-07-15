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

class Link implements \JsonSerializable
{
    /**
     * @var string
     */
    private $rel;

    /**
     * @var null|string
     */
    private $type;

    /**
     * @var null|string
     */
    private $href;

    /**
     * @var array|string[]
     */
    private $titles;

    /**
     * @var array|mixed[]
     */
    private $properties;

    /**
     * @param string[] $titles
     * @param mixed[]  $properties
     */
    public function __construct(string $rel, ?string $type, ?string $href, array $titles, array $properties)
    {
        $this->rel = $rel;
        $this->type = $type;
        $this->href = $href;
        $this->titles = $titles;
        $this->properties = $properties;
    }

    public function getRel(): string
    {
        return $this->rel;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    /**
     * @return string[]
     */
    public function getTitles(): array
    {
        return $this->titles;
    }

    public function addTitle(string $tag, string $title): void
    {
        $this->titles[$tag] = $title;
    }

    /**
     * @return string[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function addProperty(string $key, string $property): void
    {
        $this->properties[$key] = $property;
    }

    public function jsonSerialize(): array
    {
        $result = [
            'rel' => $this->rel,
            'type' => $this->type,
            'href' => $this->href,
            'titles' => $this->titles,
            'properties' => $this->properties,
        ];

        foreach ($result as $k => $v) {
            if (null === $v || (\is_array($v) && 0 === \count($v))) {
                unset($result[$k]);
            }
        }

        return $result;
    }
}
