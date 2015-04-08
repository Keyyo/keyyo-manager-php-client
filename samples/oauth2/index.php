<?php
/**
 * @license https://github.com/Keyyo/keyyo-manager-php-client/blob/master/LICENSE MIT License
 * Copyright (c) 2013 Keyyo
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

// Retrieve client ID & secret
session_start();
require_once __DIR__ . '/config.php';

// This is the Keyyo authorization URL (which can also be found on your application's settings form at https://api.keyyo.com/developers/apps)

$keyyo_authorize_endpoint = 'https://ssl.keyyo.com/oauth2/authorize.php';

$_SESSION["oauth_state"] = uniqid();

// Redirect the browser to Keyyo's login/authorization form
$authorize_url = sprintf("%s?client_id=%s&response_type=code&state=%s&redirect_uri=%s", $keyyo_authorize_endpoint, $client_id, $_SESSION["oauth_state"], $redirect_uri);
header('Location: ' . $authorize_url);
