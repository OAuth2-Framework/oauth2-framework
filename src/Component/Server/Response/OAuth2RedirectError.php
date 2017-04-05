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

use OAuth2Framework\Component\Server\ResponseMode\ResponseModeInterface;
use Psr\Http\Message\ResponseInterface;

final class OAuth2RedirectError extends OAuth2Error
{
    /**
     * @var ResponseModeInterface
     */
    private $responseMode;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * OAuth2RedirectError constructor.
     *
     * @param int                   $code
     * @param array                 $data
     * @param string                $redirectUri
     * @param ResponseModeInterface $responseMode
     * @param ResponseInterface     $response
     */
    public function __construct(int $code, array $data, string $redirectUri, ResponseModeInterface $responseMode, ResponseInterface $response)
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
