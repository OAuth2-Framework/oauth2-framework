<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\Rule;

use InvalidArgumentException;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class UserParametersRule implements Rule
{
    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        if ($commandParameters->has('require_auth_time')) {
            $require_auth_time = $commandParameters->get('require_auth_time');
            if (! is_bool($require_auth_time)) {
                throw new InvalidArgumentException('The parameter "require_auth_time" must be a boolean.');
            }
            $validatedParameters->set('require_auth_time', $require_auth_time);
        }
        if ($commandParameters->has('default_max_age')) {
            $default_max_age = $commandParameters->get('default_max_age');
            if (! is_int($default_max_age) || $default_max_age < 0) {
                throw new InvalidArgumentException('The parameter "default_max_age" must be a positive integer.');
            }
            $validatedParameters->set('default_max_age', $default_max_age);
        }
        if ($commandParameters->has('default_acr_values')) {
            $default_acr_values = $commandParameters->get('default_acr_values');
            if (! is_array($default_acr_values)) {
                throw new InvalidArgumentException('The parameter "default_acr_values" must be an array of strings.');
            }
            array_map(static function ($default_acr_value): void {
                if (! is_string($default_acr_value)) {
                    throw new InvalidArgumentException(
                        'The parameter "default_acr_values" must be an array of strings.'
                    );
                }
            }, $default_acr_values);
            $validatedParameters->set('default_acr_values', $default_acr_values);
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }
}
