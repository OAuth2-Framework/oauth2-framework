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

use Psr\Http\Message\ResponseInterface;

class OAuth2Response implements OAuth2ResponseInterface
{
    /**
     * @var int
     */
    protected $code;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * OAuth2Response constructor.
     *
     * @param int                                 $code     HTTP error code
     * @param array                               $data     Data to add to the response body
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function __construct(int $code, array $data, ResponseInterface $response)
    {
        $this->code = $code;
        $this->data = $data;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(): ResponseInterface
    {
        $this->response->getBody()->write($this->getBody());
        $this->response = $this->response->withStatus($this->getCode());
        foreach ($this->getHeaders() as $header => $value) {
            $this->response = $this->response->withHeader($header, $value);
        }

        return $this->response;
    }

    /**
     * @return array
     */
    protected function getHeaders(): array
    {
        return ['Content-Type' => 'application/json', 'Cache-Control' => 'no-store, private', 'Pragma' => 'no-cache'];
    }

    /**
     * @return string
     */
    protected function getBody(): string
    {
        $data = $this->getData();

        return json_encode($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }
}
