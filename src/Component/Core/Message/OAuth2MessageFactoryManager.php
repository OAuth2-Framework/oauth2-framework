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

namespace OAuth2Framework\Component\Core\Message;

use Http\Message\ResponseFactory as Psr7ResponseFactory;
use OAuth2Framework\Component\Core\Message\Factory\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

class OAuth2MessageFactoryManager
{
    /**
     * @var MessageExtension[]
     */
    private $extensions = [];

    /**
     * @var ResponseFactory[]
     */
    private $responseFactories = [];

    /**
     * @var Psr7ResponseFactory
     */
    private $psr7responseFactory;

    /**
     * OAuth2ResponseFactoryManager constructor.
     *
     * @param Psr7ResponseFactory $psr7responseFactory
     */
    public function __construct(Psr7ResponseFactory $psr7responseFactory)
    {
        $this->psr7responseFactory = $psr7responseFactory;
    }

    /**
     * @param ResponseFactory $responseFactory
     */
    public function addFactory(ResponseFactory $responseFactory)
    {
        $this->responseFactories[$responseFactory->getSupportedCode()] = $responseFactory;
    }

    /**
     * @param MessageExtension $extension
     */
    public function addExtension(MessageExtension $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * @param OAuth2Message $message
     * @param array $additionalData
     *
     * @return ResponseInterface
     */
    public function getResponse(OAuth2Message $message, array $additionalData = []): ResponseInterface
    {
        $code = $message->getCode();
        $data = array_merge(
            $additionalData,
            $message->getData()
        );
        foreach ($this->extensions as $extension) {
            $data = $extension->process($message, $data);
        }

        $factory = $this->getFactory($code);
        $response = $this->psr7responseFactory->createResponse($code);

        return $factory->createResponse($data, $response);
    }

    /**
     * @param int $code
     *
     * @throws \InvalidArgumentException
     *
     * @return ResponseFactory
     */
    private function getFactory(int $code): ResponseFactory
    {
        if (!array_key_exists($code, $this->responseFactories)) {
            throw new \InvalidArgumentException(sprintf('The response code "%d" is not supported', $code));
        }

        return $this->responseFactories[$code];
    }
}
