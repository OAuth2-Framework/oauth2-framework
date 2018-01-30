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

namespace OAuth2Framework\Component\Core\Response\Extension;

interface Extension
{
    /**
     * @param int   $code The code of the response
     * @param array $data Data that will be sent
     *
     * @return array
     */
    public function process(int $code, array $data): array;
}
