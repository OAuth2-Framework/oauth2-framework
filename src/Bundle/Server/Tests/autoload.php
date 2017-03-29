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

use Doctrine\Common\Annotations\AnnotationRegistry;

$files = [
    __DIR__.'/vendor/autoload.php',
    __DIR__.'/../../../../vendor/autoload.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $loader = require $file;
        AnnotationRegistry::registerLoader([$loader, 'loadClass']);

        return $loader;
    }
}

throw new \RuntimeException('Unable to find the composer vendor directory.');
