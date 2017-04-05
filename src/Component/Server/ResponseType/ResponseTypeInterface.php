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

namespace OAuth2Framework\Component\Server\ResponseType;

use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;

interface ResponseTypeInterface
{
    const RESPONSE_TYPE_MODE_FRAGMENT = 'fragment';
    const RESPONSE_TYPE_MODE_QUERY = 'query';
    const RESPONSE_TYPE_MODE_FORM_POST = 'form_post';

    /**
     * This function returns the supported response type.
     *
     * @return string
     */
    public function getResponseType(): string;

    /**
     * This function returns the list of associated grant types.
     *
     * @return string[]
     */
    public function getAssociatedGrantTypes(): array;

    /**
     * Returns the response mode of the response type or the error returned.
     * For possible values, see constants above.
     *
     * @return string
     */
    public function getResponseMode(): string;

    /**
     * @param Authorization $authorization
     * @param callable      $next
     *
     * @return Authorization
     */
    public function process(Authorization $authorization, callable $next): Authorization;
}
