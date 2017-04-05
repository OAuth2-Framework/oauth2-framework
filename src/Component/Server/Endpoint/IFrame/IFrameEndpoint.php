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

namespace OAuth2Framework\Component\Server\Endpoint\IFrame;

use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

final class IFrameEndpoint implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * IFrameEndpoint constructor.
     *
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $content = $this->renderTemplate();

        $response = $this->responseFactory->createResponse();
        $headers = ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }
        $response->getBody()->write($content);

        return $response;
    }

    /**
     * @return string
     */
    private function renderTemplate(): string
    {
        $content = <<<'EOT'
<html>
    <head>
        <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
        <title>OP iFrame</title>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/components/sha256-min.js"></script>
            window.addEventListener("message",receiveMessage, false);
            
            function getCookie(c_name)
            {
                var i,x,y,ARRcookies=document.cookie.split(";");
                for (i=0;i<ARRcookies.length;i++) {
                    x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
                    y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
                    x=x.replace(/^\s+|\s+$/g,"");
                    if (x==c_name) {
                        return unescape(y);
                    }
                }
            }
            
            function receiveMessage(e){
                if ( e.origin !== origin) {
                    console.log(e.origin + ' !== ' + origin);
                    return;
                }
                var state = '';
                var parts = e.data.split(' ');
                var client_id = parts[0];
                var session_state = parts[1];
                var ss_parts = session_state.split('.');
                var salt = ss_parts[1];
                
                var ops = getCookie('ops');
                var ss = CryptoJS.SHA256(client_id + e.origin + ops + salt) + "." + salt;
                if (session_state == ss) {
                    state = 'unchanged';
                } else {
                    state = 'changed';
                }
                e.source.postMessage(state, e.origin);
            };
        //]]></script>
    </head>
    <body>
    </body>
</html>
EOT;

        return $content;
    }
}
