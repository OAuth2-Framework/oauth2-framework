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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use OAuth2Framework\Component\Server\ResponseMode\FormPostResponseRendererInterface;

final class FormPostResponseRenderer implements FormPostResponseRendererInterface
{
    /**
     * {@inheritdoc}
     */
    public function render(string $redirectUri, array $data): string
    {
        $content = <<<'EOT'
<!doctype html>
<html>
    <head>
    <title>Authorization form</title>
    <meta name="referrer" content="origin"/>
    <script type="text/javascript">
        function submitform() {
            document.forms[0].submit();
        }
    </script>
    </head>
    <body onload='submitform();'>
        <form method="post" action="{{redirect_uri}}">
        {{input}}
    </form>
    </body>
</html>
EOT;

        $input = [];
        foreach ($data as $key => $value) {
            $input[] = sprintf('<input type="hidden" name="%s" value="%s"/>', $key, $value);
        }
        $replacements = [
            '{{redirect_uri}}' => $redirectUri,
            '{{input}}' => implode(PHP_EOL, $input),
        ];

        return str_replace(array_keys($replacements), $replacements, $content);
    }
}
