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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Exception;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

class OAuth2AuthorizationException extends \Exception
{
    private $authorization;

    private $errorDescription;

    public function __construct(string $error, ?string $errorDescription, AuthorizationRequest $authorization, ?\Exception $previous = null)
    {
        $this->authorization = $authorization;
        $this->errorDescription = $errorDescription;

        parent::__construct($error, 0, $previous);
    }

    public function getAuthorization(): AuthorizationRequest
    {
        return $this->authorization;
    }

    public function getErrorDescription(): ?string
    {
        return $this->errorDescription;
    }
}
