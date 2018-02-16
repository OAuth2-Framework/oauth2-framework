<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

require_once 'vendor/autoload.php';

use function League\Uri\parse;

dump(parse('https://foo.com'));
dump(parse('com.example.app:/oauth2redirect/example-provider'));
dump(parse('urn:ietf:wg:oauth:2.0:oob'));
dump(parse('https://my-service.com:9000/'));
