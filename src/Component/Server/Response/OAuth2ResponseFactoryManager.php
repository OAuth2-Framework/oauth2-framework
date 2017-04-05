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

namespace OAuth2Framework\Component\Server\Response;

use Assert\Assertion;
use Interop\Http\Factory\ResponseFactoryInterface as Psr7ResponseFactory;
use OAuth2Framework\Component\Server\Response\Extension\ExtensionInterface;
use OAuth2Framework\Component\Server\Response\Factory\ResponseFactoryInterface;

final class OAuth2ResponseFactoryManager
{
    //Error messages from the RFC5749
    const ERROR_INVALID_REQUEST = 'invalid_request';
    const ERROR_INVALID_CLIENT = 'invalid_client';
    const ERROR_INVALID_GRANT = 'invalid_grant';
    const ERROR_INVALID_SCOPE = 'invalid_scope';
    const ERROR_INVALID_TOKEN = 'invalid_token';
    const ERROR_UNAUTHORIZED_CLIENT = 'unauthorized_client';
    const ERROR_UNSUPPORTED_GRANT_TYPE = 'unsupported_grant_type';
    const ERROR_ACCESS_DENIED = 'access_denied';
    const ERROR_UNSUPPORTED_RESPONSE_TYPE = 'unsupported_response_type';
    const ERROR_SERVER_ERROR = 'server_error';
    const ERROR_TEMPORARILY_UNAVAILABLE = 'temporarily_unavailable';

    // Error messages from the RFC5750
    const ERROR_INSUFFICIENT_SCOPE = 'insufficient_scope';

    //Error messages from OpenID Connect specifications
    const ERROR_INTERACTION_REQUIRED = 'interaction_required';
    const ERROR_LOGIN_REQUIRED = 'login_required';
    const ERROR_ACCOUNT_SELECTION_REQUIRED = 'account_selection_required';
    const ERROR_CONSENT_REQUIRED = 'consent_required';
    const ERROR_INVALID_REQUEST_URI = 'invalid_request_uri';
    const ERROR_INVALID_REQUEST_OBJECT = 'invalid_request_object';
    const ERROR_REQUEST_NOT_SUPPORTED = 'request_not_supported';
    const ERROR_REQUEST_URI_NOT_SUPPORTED = 'request_uri_not_supported';
    const ERROR_REGISTRATION_NOT_SUPPORTED = 'registration_not_supported';

    //Error message for server errors (codes 5xx)
    const ERROR_INTERNAL = 'internal_server_error';

    //Custom message for this library
    const ERROR_INVALID_RESOURCE_SERVER = 'invalid_resource_server';
    /**
     * @var \OAuth2Framework\Component\Server\Response\Extension\ExtensionInterface[]
     */
    private $extensions = [];

    /**
     * @var \OAuth2Framework\Component\Server\Response\Factory\ResponseFactoryInterface[]
     */
    private $responseFactories = [];

    /**
     * @var Psr7ResponseFactory
     */
    private $psr7ResponseFactory;

    /**
     * OAuth2ResponseFactoryManager constructor.
     *
     * @param Psr7ResponseFactory $psr7ResponseFactory
     */
    public function __construct(Psr7ResponseFactory $psr7ResponseFactory)
    {
        $this->psr7ResponseFactory = $psr7ResponseFactory;
    }

    /**
     * @param ResponseFactoryInterface $responseFactory
     *
     * @return OAuth2ResponseFactoryManager
     */
    public function addResponseFactory(ResponseFactoryInterface $responseFactory): OAuth2ResponseFactoryManager
    {
        $this->responseFactories[$responseFactory->getSupportedCode()] = $responseFactory;

        return $this;
    }

    /**
     * @param ExtensionInterface $extension
     */
    public function addExtension(ExtensionInterface $extension)
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
        Assertion::integer($code);

        foreach ($this->extensions as $extension) {
            $data = $extension->process($code, $data);
        }

        $factory = $this->getResponseFactory($code);
        $response = $this->psr7ResponseFactory->createResponse($code);

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
     * @return ResponseFactoryInterface
     */
    private function getResponseFactory(int $code): ResponseFactoryInterface
    {
        Assertion::true($this->isResponseCodeSupported($code), sprintf('The response code \'%d\' is not supported', $code));

        return $this->responseFactories[$code];
    }
}
