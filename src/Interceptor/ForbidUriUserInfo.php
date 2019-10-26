<?php

namespace Amp\Http\Client\Interceptor;

use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;

final class ForbidUriUserInfo extends ModifyRequest
{
    public function __construct()
    {
        parent::__construct(static function (Request $request) {
            if ($request->getUri()->getUserInfo() !== '') {
                throw new HttpException('The user information (username:password) component of URIs has been deprecated '
                    . '(see https://tools.ietf.org/html/rfc3986#section-3.2.1 and https://tools.ietf.org/html/rfc7230#section-2.7.1); '
                    . 'Instead, set an "Authorization" header containing "Basic " . \\base64_encode("username:password")');
            }
        });
    }
}