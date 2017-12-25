<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Core\Response;

use Http\Message\ResponseFactory as Psr7ResponseFactory;
use OAuth2Framework\Component\Server\Core\Response\Extension\Extension;
use OAuth2Framework\Component\Server\Core\Response\Factory\ResponseFactory;

final class OAuth2ResponseFactoryManager
{
    /**
     * @var Extension[]
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
     *
     * @return OAuth2ResponseFactoryManager
     */
    public function addResponseFactory(ResponseFactory $responseFactory): self
    {
        $this->responseFactories[$responseFactory->getSupportedCode()] = $responseFactory;

        return $this;
    }

    /**
     * @param Extension $extension
     */
    public function addExtension(Extension $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * @param int   $code The code of the response
     * @param array $data Data sent to the response
     *
     * @return OAuth2ResponseInterface
     */
    public function getResponse(int $code, array $data): OAuth2ResponseInterface
    {
        foreach ($this->extensions as $extension) {
            $data = $extension->process($code, $data);
        }

        $factory = $this->getResponseFactory($code);
        $response = $this->psr7responseFactory->createResponse($code);

        return $factory->createResponse($data, $response);
    }

    /**
     * @param int $code The code of the response
     *
     * @return bool
     */
    public function isResponseCodeSupported(int $code): bool
    {
        return array_key_exists($code, $this->responseFactories);
    }

    /**
     * @param int $code
     *
     * @throws \InvalidArgumentException
     *
     * @return ResponseFactory
     */
    private function getResponseFactory(int $code): ResponseFactory
    {
        if (!$this->isResponseCodeSupported($code)) {
            throw new \InvalidArgumentException(sprintf('The response code "%d" is not supported', $code));
        }

        return $this->responseFactories[$code];
    }
}
