<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientRule;

use const FILTER_VALIDATE_EMAIL;
use InvalidArgumentException;
use function is_array;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class ContactsParametersRule implements Rule
{
    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        if ($commandParameters->has('contacts')) {
            $contacts = $commandParameters->get('contacts');
            if (! is_array($contacts)) {
                throw new InvalidArgumentException('The parameter "contacts" must be a list of e-mail addresses.');
            }
            array_map(static function ($contact): void {
                if (filter_var($contact, FILTER_VALIDATE_EMAIL) === false) {
                    throw new InvalidArgumentException('The parameter "contacts" must be a list of e-mail addresses.');
                }
            }, $contacts);
            $validatedParameters->set('contacts', $contacts);
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }
}
