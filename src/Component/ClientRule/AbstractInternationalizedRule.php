<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\ClientRule;

use OAuth2Framework\Component\Core\DataBag\DataBag;

abstract class AbstractInternationalizedRule implements Rule
{
    protected function getInternationalizedParameters(DataBag $requestedParameters, string $base, \Closure $closure): array
    {
        $result = [];
        foreach ($requestedParameters->all() as $k => $v) {
            if ($base === $k) {
                $closure($k, $v);
                $result[$k] = $v;

                continue;
            }

            $sub = \mb_substr($k, 0, \mb_strlen($base, '8bit') + 1, '8bit');
            if (\Safe\sprintf('%s#', $base) === $sub && '' !== \mb_substr($k, \mb_strlen($base, '8bit') + 1, null, '8bit')) {
                $closure($k, $v);
                $result[$k] = $v;

                continue;
            }
        }

        return $result;
    }
}
