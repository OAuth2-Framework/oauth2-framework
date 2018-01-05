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

namespace OAuth2Framework\Component\Server\MetadataEndpoint\Tests;

use Http\Message\MessageFactory\DiactorosMessageFactory;
use Http\Message\ResponseFactory;
use OAuth2Framework\Component\Server\MetadataEndpoint\Metadata;
use OAuth2Framework\Component\Server\MetadataEndpoint\MetadataEndpoint;
use PHPUnit\Framework\TestCase;

/**
 * @group MetadataEndpoint
 */
final class MetadataEndpointTest extends TestCase
{
    /**
     * @test
     */
    public function theMetadataEndpointCanReceiveRegistrationRequests()
    {
    }

    /**
     * @var null|MetadataEndpoint
     */
    private $metadataEndpoint = null;

    /**
     * @return MetadataEndpoint
     */
    private function getMetadataEndpoint(): MetadataEndpoint
    {
        if (null === $this->metadataEndpoint) {
            $metadata = new Metadata();
            $metadata->set('foo', 'bar');

            $this->metadataEndpoint = new MetadataEndpoint(
                $this->getResponseFactory(),
                $metadata
            );
        }

        return $this->metadataEndpoint;
    }

    /**
     * @var ResponseFactory|null
     */
    private $responseFactory = null;

    /**
     * @return ResponseFactory
     */
    private function getResponseFactory(): ResponseFactory
    {
        if (null === $this->responseFactory) {
            $this->responseFactory = new DiactorosMessageFactory();
        }

        return $this->responseFactory;
    }
}
