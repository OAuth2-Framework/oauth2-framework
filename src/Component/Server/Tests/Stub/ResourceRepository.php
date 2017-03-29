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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use OAuth2Framework\Component\Server\Model\Resource\ResourceId;
use OAuth2Framework\Component\Server\Model\Resource\ResourceInterface;
use OAuth2Framework\Component\Server\Model\Resource\ResourceRepositoryInterface;

final class ResourceRepository implements ResourceRepositoryInterface
{
    /**
     * @var ResourceInterface[]
     */
    private $resources = [];

    public function __construct()
    {
        $this->resources['john'] = new ResourceObject('https://server.example.com');
    }

    /**
     * {@inheritdoc}
     */
    public function findResource(ResourceId $resourceId)
    {
        $server = 'my-service.com:9000';
        $length = mb_strlen($server, 'utf-8');
        if ('https://'.$server.'/+' === mb_substr($resourceId->getValue(), 0, $length + 10, 'utf-8')) {
            $resourceName = mb_substr($resourceId->getValue(), $length + 10, null, 'utf-8');
        } elseif ('acct:' === mb_substr($resourceId->getValue(), 0, 5, 'utf-8') && '@'.$server === mb_substr($resourceId->getValue(), -($length + 1), null, 'utf-8')) {
            $resourceName = mb_substr($resourceId->getValue(), 5, -($length + 1), 'utf-8');
        } else {
            return;
        }

        return array_key_exists($resourceName, $this->resources) ? $this->resources[$resourceName] : null;
    }
}
