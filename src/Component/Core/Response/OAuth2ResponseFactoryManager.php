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

namespace OAuth2Framework\Component\Core\Response;

use Http\Message\ResponseFactory as Psr7ResponseFactory;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\Core\Response\Extension\Extension;
use OAuth2Framework\Component\Core\Response\Factory\ResponseFactory;

class OAuth2ResponseFactoryManager
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
     * @param OAuth2Exception $e
     *
     * @return OAuth2ResponseInterface
     */
    public function getResponse(OAuth2Exception $e): OAuth2ResponseInterface
    {
        $code = $e->getCode();
        $data = $e->getData();
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
