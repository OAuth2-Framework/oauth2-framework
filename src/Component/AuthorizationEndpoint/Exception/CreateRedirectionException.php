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

class CreateRedirectionException extends \Exception
{
    private $authorization;

    private $description;

    public function __construct(AuthorizationRequest $authorization, string $message, ?string $description)
    {
        parent::__construct($message);
        $this->authorization = $authorization;
        $this->description = $description;
    }

    public function getAuthorization(): AuthorizationRequest
    {
        return $this->authorization;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
