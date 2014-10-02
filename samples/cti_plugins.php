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

// Load Keyyo classpath

require_once dirname(__FILE__) . '/../lib/Keyyo/autoload.php';

// This is the OAuth2 access token that corresponds to a customer ID within Keyyo

$access_token = '<your access token goes here (see oauth2 sample for a way to retrieve it)>';

try {
	// Instantiate a Manager client (version 1.0 here)

	$keyyo_manager = new \Keyyo\Manager\Client('1.0', $access_token);

	// Retrieve a VoIP account service

	$service = $keyyo_manager->services('33123456789');

	// List available CTI plugins and display their availability

	echo 'Plugin status:<br />';
	foreach ($service->cti_plugins() as $plugin)
		echo 'Plugin ', $plugin->name, ' is ', ($plugin->enabled ? 'enabled' : 'disabled'), '<br />';

	// Retrieve a specific CTI

	$custom_plugin = $service->cti_plugins('custom');

	// Retrieve its settable parameters

	echo '<br />Parameters:<br />';
	foreach ($custom_plugin->parameters() as $parameter)
	{
		print_r($parameter->information);
		echo $parameter->name, ' => ', $parameter->value, '<br />';
	}

	// Enable it

	$custom_plugin->enabled = true;

	// Set one of its parameter

	$custom_plugin->parameters('url')->value = 'http://www.exemple.tld/notification.php?account=_ACCOUNT_&caller=_CALLER_&callee=_CALLEE_&type=_N_TYPE_';

} catch (\Keyyo\Manager\Exception\Exception $e) {
	echo 'Error: ', $e->getMessage();
}
