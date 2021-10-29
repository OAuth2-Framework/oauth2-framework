<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Message\Factory;

use Psr\Http\Message\ResponseInterface;

interface ResponseFactory
{
    public function getSupportedCode(): int;

    /**
     * @param array $data Data sent to the response
     */
    public function createResponse(array $data, ResponseInterface $response): ResponseInterface;
}
