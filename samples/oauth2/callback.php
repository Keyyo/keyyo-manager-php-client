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
session_start();

// Check state
if (isset($_GET["state"]) && $_GET["state"] != $_SESSION["oauth_state"]) {
	header('Content-Type: application/json');
	die(json_encode(array("error" => "invalid_state", "error_description" => "Invalid state")));
}

// Retrieve client ID & secret

require_once __DIR__ . '/config.php';

// This is the Keyyo access token URL (which can also be found on your application's settings form at https://api.keyyo.com/developers/apps)

$keyyo_token_endpoint = 'https://api.keyyo.com/oauth2/token.php';

// Send a cURL request using request's authorization code + state

$curl = curl_init($keyyo_token_endpoint);

curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, array(
	'client_id'     => $client_id,
	'client_secret' => $client_secret,
	'grant_type'    => 'authorization_code',
	'redirect_uri'  => $redirect_uri,
	'code'          => $_GET['code'],
));

curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

$auth_data = curl_exec($curl);

// Retrieve the access token

if ($auth_data === false)
	die('cURL request failed');

$response = json_decode($auth_data);

if (is_null($response))
	die('Could not parse cURL response body.');

if (isset($response->error))
	die(isset($response->error_description) ? $response->error_description : $response->error);

// Output the access token and its lifetime

echo 'Your access token is: ', $response->access_token, '<br />';
echo 'It expires in: ', $response->expires_in, ' seconds<br />';
