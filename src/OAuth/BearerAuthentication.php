<?php
/**
 *  Copyright (C) 2016 SURFnet.
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace fkooman\RemoteStorage\OAuth;

use fkooman\RemoteStorage\Http\Exception\HttpException;
use fkooman\RemoteStorage\Http\Request;

class BearerAuthentication
{
    /** @var TokenStorage */
    private $tokenStorage;

    /** @var string */
    private $realm;

    public function __construct(TokenStorage $tokenStorage, $realm = 'Protected Area')
    {
        $this->tokenStorage = $tokenStorage;
        $this->realm = $realm;
    }

    public function optionalAuth(Request $request)
    {
        $authorizationHeader = $request->getHeader('HTTP_AUTHORIZATION', false, null);

        // is authorization header there?
        if (null === $authorizationHeader || empty($authorizationHeader)) {
            return false;
        }

        return $this->requireAuth($request);
    }

    public function requireAuth(Request $request)
    {
        $authorizationHeader = $request->getHeader('HTTP_AUTHORIZATION');

        // validate the HTTP_AUTHORIZATION header
        if (false === $bearerToken = self::getBearerToken($authorizationHeader)) {
            throw $this->invalidTokenException();
        }

        $accessTokenKey = $bearerToken[0];
        $accessToken = $bearerToken[1];

        $tokenInfo = $this->tokenStorage->get($accessTokenKey);
        if (false === $tokenInfo) {
            throw $this->invalidTokenException();
        }

        // time safe string compare, using polyfill on PHP < 5.6
        if (hash_equals($tokenInfo['access_token'], $accessToken)) {
            return new TokenInfo($tokenInfo);
        }

        throw $this->invalidTokenException();
    }

    private static function getBearerToken($authorizationHeader)
    {
        if (1 !== preg_match('|^Bearer ([a-zA-Z0-9-._~+/]+=*)$|', $authorizationHeader, $m)) {
            return false;
        }

        $bearerToken = $m[1];
        if (false === strpos($bearerToken, '.')) {
            return false;
        }

        return explode('.', $bearerToken);
    }

    private function invalidTokenException()
    {
        return new HttpException(
            'invalid_token',
            401,
            [
                'WWW-Authenticate' => sprintf(
                    'Bearer realm="%s",error="invalid_token"',
                    $this->realm
                ),
            ]
        );
    }
}
