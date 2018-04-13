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

namespace OAuth2Framework\Component\Core\Domain;

interface DomainObject extends \JsonSerializable
{
    /**
     * @return string
     */
    public static function getSchema(): string;

    /**
     * @param \stdClass $json
     *
     * @return DomainObject
     */
    public static function createFromJson(\stdClass $json): self;
}
