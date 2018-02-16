<?php

require_once 'vendor/autoload.php';

use function \League\Uri\parse;

dump(parse('https://foo.com'));
dump(parse('com.example.app:/oauth2redirect/example-provider'));
dump(parse('urn:ietf:wg:oauth:2.0:oob'));
dump(parse('https://my-service.com:9000/'));
