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

namespace OAuth2Framework\Bundle\Service;

use Base64Url\Base64Url;

final class RandomIdGenerator
{
    /**
     * @param int $min
     * @param int $max
     *
     * @return string
     */
    public static function generate(int $min, int $max): string
    {
        $length = random_int($min, $max);

        return Base64Url::encode(random_bytes($length));
    }
}
