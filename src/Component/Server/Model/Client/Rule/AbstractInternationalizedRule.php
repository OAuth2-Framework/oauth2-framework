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

namespace OAuth2Framework\Component\Server\Model\Client\Rule;

use OAuth2Framework\Component\Server\Model\DataBag\DataBag;

abstract class AbstractInternationalizedRule implements RuleInterface
{
    /**
     * @param DataBag       $requestedParameters
     * @param string        $base
     * @param \Closure|null $closure
     *
     * @return array
     */
    protected function getInternationalizedParameters(DataBag $requestedParameters, string $base, ?\Closure $closure): array
    {
        $result = [];
        foreach ($requestedParameters->all() as $k => $v) {
            if ($base === $k) {
                $closure($k, $v);
                $result[$k] = $v;

                continue;
            }

            $sub = mb_substr($k, 0, mb_strlen($base, '8bit') + 1, '8bit');
            if (sprintf('%s#', $base) === $sub && !empty(mb_substr($k, mb_strlen($base, '8bit') + 1, null, '8bit'))) {
                if (null !== $closure) {
                    $closure($k, $v);
                }
                $result[$k] = $v;

                continue;
            }
        }

        return $result;
    }
}
