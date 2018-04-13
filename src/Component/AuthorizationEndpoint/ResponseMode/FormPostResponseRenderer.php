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

namespace OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode;

interface FormPostResponseRenderer
{
    /**
     * @param string $redirectUri
     * @param array  $data
     *
     * @return string
     */
    public function render(string $redirectUri, array $data): string;
}
