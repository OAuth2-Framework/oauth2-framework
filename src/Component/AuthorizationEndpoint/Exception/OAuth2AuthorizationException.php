<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\Exception;

use Exception;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use Throwable;

class OAuth2AuthorizationException extends Exception
{
    public function __construct(
        string $error,
        private ?string $errorDescription,
        private AuthorizationRequest $authorization,
        ?Throwable $previous = null
    ) {
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
