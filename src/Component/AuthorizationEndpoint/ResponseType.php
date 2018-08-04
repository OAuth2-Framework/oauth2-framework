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

namespace OAuth2Framework\Component\AuthorizationEndpoint;

use OAuth2Framework\Component\Core\Message\OAuth2Message;

interface ResponseType
{
    public const RESPONSE_TYPE_MODE_FRAGMENT = 'fragment';

    public const RESPONSE_TYPE_MODE_QUERY = 'query';

    public const RESPONSE_TYPE_MODE_FORM_POST = 'form_post';

    /**
     * This function returns the supported response type.
     */
    public function name(): string;

    /**
     * This function returns the list of associated grant types.
     *
     * @return string[]
     */
    public function associatedGrantTypes(): array;

    /**
     * Returns the response mode of the response type or the error returned.
     * For possible values, see constants above.
     */
    public function getResponseMode(): string;

    /**
     * @throws OAuth2Message
     */
    public function preProcess(Authorization $authorization): Authorization;

    /**
     * @throws OAuth2Message
     */
    public function process(Authorization $authorization): Authorization;
}
