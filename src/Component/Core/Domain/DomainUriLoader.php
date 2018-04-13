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

namespace OAuth2Framework\Component\Core\Domain;

use League\JsonReference\SchemaLoadingException;
use League\JsonReference\LoaderInterface;

class DomainUriLoader implements LoaderInterface
{
    /**
     * @var string[]
     */
    private $mappings = [];

    /**
     * @param string $schema
     * @param string $filename
     */
    public function add(string $schema, string $filename)
    {
        $this->mappings[$schema] = $filename;
    }

    public function load($path)
    {
        if (array_key_exists($path, $this->mappings)) {
            $content = file_get_contents($this->mappings[$path]);

            return json_decode($content);
        }

        throw SchemaLoadingException::notFound(sprintf('The schema "%s" is not supported.', $path));
    }
}
