<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint;

use function count;
use function is_array;
use JsonSerializable;

final class Link implements JsonSerializable
{
    private array $titles;

    private array $properties;

    /**
     * @param string[] $titles
     * @param mixed[]  $properties
     */
    public function __construct(
        private string $rel,
        private ?string $type,
        private ?string $href,
        array $titles,
        array $properties
    ) {
        $this->titles = $titles;
        $this->properties = $properties;
    }

    /**
     * @param string[] $titles
     * @param mixed[]  $properties
     */
    public static function create(
        string $rel,
        ?string $type,
        ?string $href,
        array $titles,
        array $properties
    ): static {
        return new self($rel, $type, $href, $titles, $properties);
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

    public function addTitle(string $tag, string $title): static
    {
        $this->titles[$tag] = $title;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function addProperty(string $key, string $property): static
    {
        $this->properties[$key] = $property;

        return $this;
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
            if ($v === null || (is_array($v) && count($v) === 0)) {
                unset($result[$k]);
            }
        }

        return $result;
    }
}
