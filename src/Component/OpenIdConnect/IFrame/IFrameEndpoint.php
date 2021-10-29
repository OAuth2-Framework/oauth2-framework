<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\IFrame;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class IFrameEndpoint implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $content = $this->renderTemplate();

        $response = $this->responseFactory->createResponse();
        $headers = [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private',
            'Pragma' => 'no-cache',
        ];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }
        $response->getBody()
            ->write($content)
        ;

        return $response;
    }

    private function renderTemplate(): string
    {
        return <<<'CODE_SAMPLE'
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
CODE_SAMPLE;
    }
}
