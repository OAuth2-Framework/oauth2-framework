<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientRule;

use Closure;
use OAuth2Framework\Component\Core\DataBag\DataBag;

abstract class AbstractInternationalizedRule implements Rule
{
    protected function getInternationalizedParameters(
        DataBag $requestedParameters,
        string $base,
        Closure $closure
    ): array {
        $result = [];
        foreach ($requestedParameters->all() as $k => $v) {
            if ($base === $k) {
                $closure($k, $v);
                $result[$k] = $v;

                continue;
            }

            $sub = mb_substr($k, 0, mb_strlen($base, '8bit') + 1, '8bit');
            if (sprintf('%s#', $base) === $sub && mb_substr($k, mb_strlen($base, '8bit') + 1, null, '8bit') !== '') {
                $closure($k, $v);
                $result[$k] = $v;

                continue;
            }
        }

        return $result;
    }
}
