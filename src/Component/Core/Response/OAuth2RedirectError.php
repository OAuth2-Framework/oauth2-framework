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

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use Psr\Http\Message\ResponseInterface;

class OAuth2RedirectError extends OAuth2Error
{
    /**
     * @var ResponseMode
     */
    private $responseMode;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * OAuth2RedirectError constructor.
     *
     * @param int               $code
     * @param array             $data
     * @param string            $redirectUri
     * @param ResponseMode      $responseMode
     * @param ResponseInterface $response
     */
    public function __construct(int $code, array $data, string $redirectUri, ResponseMode $responseMode, ResponseInterface $response)
    {
        $this->redirectUri = $redirectUri;
        $this->responseMode = $responseMode;

        parent::__construct($code, $data, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(): ResponseInterface
    {
        return $this->responseMode->buildResponse($this->redirectUri, $this->getData());
    }
}
