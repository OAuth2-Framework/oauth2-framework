<?php

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
