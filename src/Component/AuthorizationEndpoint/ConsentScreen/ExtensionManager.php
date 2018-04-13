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

namespace OAuth2Framework\Component\AuthorizationEndpoint\ConsentScreen;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use Psr\Http\Message\ServerRequestInterface;

class ExtensionManager
{
    /**
     * @var Extension[]
     */
    private $extensions = [];

    /**
     * @param Extension $extension
     */
    public function add(Extension $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     *
     * @return Authorization
     */
    public function processBefore(ServerRequestInterface $request, Authorization $authorization): Authorization
    {
        foreach ($this->extensions as $extension) {
            $authorization = $extension->processBefore($request, $authorization);
        }

        return $authorization;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     *
     * @return Authorization
     */
    public function processAfter(ServerRequestInterface $request, Authorization $authorization): Authorization
    {
        foreach ($this->extensions as $extension) {
            $authorization = $extension->processAfter($request, $authorization);
        }

        return $authorization;
    }
}
