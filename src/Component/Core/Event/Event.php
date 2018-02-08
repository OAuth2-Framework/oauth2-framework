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

namespace OAuth2Framework\Component\Core\Event;

use OAuth2Framework\Component\Core\Id\Id;
use OAuth2Framework\Component\Core\Domain\DomainObject;

abstract class Event implements DomainObject
{
    /**
     * @return mixed
     */
    abstract public function getPayload();

    /**
     * @return Id
     */
    abstract public function getDomainId(): Id;

    /**
     * @return string
     */
    public function getType(): string
    {
        return get_class($this);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = [
            '$schema' => $this->getSchema(),
            'type' => get_class($this),
            'domain_id' => $this->getDomainId()->getValue(),
        ];
        $payload = $this->getPayload();
        if (null !== $payload) {
            $data['payload'] = $payload;
        }

        return $data;
    }
}
