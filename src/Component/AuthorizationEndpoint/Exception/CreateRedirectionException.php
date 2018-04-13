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

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;

class CreateRedirectionException extends \Exception
{
    /**
     * @var Authorization
     */
    private $authorization;

    /**
     * @var string
     */
    private $description = null;

    /**
     * CreateRedirectionException constructor.
     *
     * @param Authorization $authorization
     * @param string        $message
     * @param null|string   $description
     */
    public function __construct(Authorization $authorization, string $message, ? string $description)
    {
        parent::__construct($message);
        $this->authorization = $authorization;
        $this->description = $description;
    }

    /**
     * @return Authorization
     */
    public function getAuthorization(): Authorization
    {
        return $this->authorization;
    }

    /**
     * @return null|string
     */
    public function getDescription(): ? string
    {
        return $this->description;
    }
}
