<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Client\Response;

/**
 * @method string getAccessToken()
 * @method bool hasIdToken()
 * @method string getIdToken()
 */
interface AccessTokenInterface extends OAuth2ResponseInterface
{
}
