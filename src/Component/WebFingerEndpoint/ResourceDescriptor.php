<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint;

use function count;
use function is_array;
use JsonSerializable;

final class ResourceDescriptor implements JsonSerializable
{
    private array $aliases;

    private array $properties;

    private array $links;

    /**
     * @param string[] $aliases
     * @param mixed[]  $properties
     * @param Link[]   $links
     */
    public function __construct(
        private ?string $subject,
        array $aliases,
        array $properties,
        array $links
    ) {
        $this->aliases = $aliases;
        $this->properties = $properties;
        $this->links = $links;
    }

    /**
     * @param string[] $aliases
     * @param mixed[]  $properties
     * @param Link[]   $links
     */
    public static function create(?string $subject, array $aliases, array $properties, array $links): self
    {
        return new self($subject, $aliases, $properties, $links);
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

    public function addAlias(string $key, string $alias): self
    {
        $this->properties[$key] = $alias;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function addProperty(string $key, string $property): self
    {
        $this->properties[$key] = $property;

        return $this;
    }

    /**
     * @return Link[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    public function addLink(string $key, Link $link): self
    {
        $this->links[$key] = $link;

        return $this;
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
            if ($v === null || (is_array($v) && count($v) === 0)) {
                unset($result[$k]);
            }
        }

        return $result;
    }
}
