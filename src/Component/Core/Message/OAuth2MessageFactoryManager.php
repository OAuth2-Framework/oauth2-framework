<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Core\Message;

use function Safe\sprintf;
use OAuth2Framework\Component\Core\Message\Factory\ResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class OAuth2MessageFactoryManager
{
    /**
     * @var MessageExtension[]
     */
    private array $extensions = [];

    /**
     * @var ResponseFactory[]
     */
    private array $responseFactories = [];

    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function addFactory(ResponseFactory $responseFactory): void
    {
        $this->responseFactories[$responseFactory->getSupportedCode()] = $responseFactory;
    }

    public function addExtension(MessageExtension $extension): void
    {
        $this->extensions[] = $extension;
    }

    public function getResponse(OAuth2Error $message, array $additionalData = []): ResponseInterface
    {
        $code = $message->getCode();
        $data = array_merge(
            $additionalData,
            $message->getData()
        );
        foreach ($this->extensions as $extension) {
            $data = $data + $extension->process($message);
        }

        $factory = $this->getFactory($code);
        $response = $this->responseFactory->createResponse($code);

        return $factory->createResponse($data, $response);
    }

    private function getFactory(int $code): ResponseFactory
    {
        if (!\array_key_exists($code, $this->responseFactories)) {
            throw new \InvalidArgumentException(sprintf('The response code "%d" is not supported', $code));
        }

        return $this->responseFactories[$code];
    }
}
