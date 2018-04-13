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

class OAuth2AuthorizationException extends \Exception
{
    /**
     * @var Authorization
     */
    private $authorization;

    /**
     * @var null|string
     */
    private $errorDescription;

    /**
     * OAuth2AuthorizationException constructor.
     *
     * @param int             $code
     * @param string          $error
     * @param null|string     $errorDescription
     * @param Authorization   $authorization
     * @param \Exception|null $previous
     */
    public function __construct(int $code, string $error, ?string $errorDescription, Authorization $authorization, ? \Exception $previous = null)
    {
        $this->authorization = $authorization;
        $this->errorDescription = $errorDescription;

        parent::__construct($error, $code, $previous);
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
    public function getErrorDescription(): ?string
    {
        return $this->errorDescription;
    }
}
