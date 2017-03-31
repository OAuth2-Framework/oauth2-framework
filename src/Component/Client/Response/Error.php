<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Client\Response;

/**
 * @method string getError()
 * @method bool hasErrorDescription()
 * @method string getErrorDescription()
 * @method bool hasErrorUri()
 * @method string getErrorUri()
 */
final class Error extends OAuth2Response implements ErrorInterface
{
}
