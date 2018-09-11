<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\WebFingerEndpoint;

class Link implements \JsonSerializable
{
    private $rel;
    private $type;
    private $href;
    private $titles;
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

    public function getHref(): string
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

    public function jsonSerialize()
    {
        $result = [
            'rel' => $this->rel,
        ];

        foreach (['type', 'href', 'titles', 'properties'] as $property) {
            if (!empty($this->$property)) {
                $result[$property] = $this->$property;
            }
        }

        return $result;
    }
}
