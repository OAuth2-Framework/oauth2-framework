<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\Controller;

use OAuth2Framework\Bundle\Server\Service\Metadata;
use Symfony\Component\HttpFoundation\Response;

class MetadataController
{
    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * MetadataController constructor.
     *
     * @param Metadata $metadata
     */
    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    public function metadataAction()
    {
        $response = new Response(
            json_encode($this->metadata),
            200,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
            ]
        );

        return $response;
    }
}
