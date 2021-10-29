<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Message;

interface MessageExtension
{
    /**
     * @param OAuth2Error $error The code of the response
     */
    public function process(OAuth2Error $error): array;
}
