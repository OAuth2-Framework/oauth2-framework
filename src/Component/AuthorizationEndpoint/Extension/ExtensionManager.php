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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Extension;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use Psr\Http\Message\ServerRequestInterface;

class ExtensionManager
{
    /**
     * @var Extension[]
     */
    private $extensions = [];

    public function add(Extension $extension): void
    {
        $this->extensions[] = $extension;
    }

    public function processBefore(ServerRequestInterface $request, Authorization $authorization): Authorization
    {
        foreach ($this->extensions as $extension) {
            $extension->processBefore($request, $authorization);
        }

        return $authorization;
    }

    public function processAfter(ServerRequestInterface $request, Authorization $authorization): Authorization
    {
        foreach ($this->extensions as $extension) {
            $extension->processAfter($request, $authorization);
        }

        return $authorization;
    }
}
