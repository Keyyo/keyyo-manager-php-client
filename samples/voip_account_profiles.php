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

	// Retrieve all services from the authenticated customer

	$services = $keyyo_manager->services();

	// Loop over their services

	foreach ($services as $service) {
		// ...
	}

	// Retrieve a specific service based on its CSI

	$service = $keyyo_manager->services('33123456789');

	// Retrieve a service's default profile and display its properties

	$default_profile = $service->profiles('default');

	// Retrieve a regular profile

	$profile = $service->profiles(4);

	// Everything can also be chained

	$profile = $keyyo_manager->services('33123456789')->profiles(4);

	// Update a profile's default forward number

	$profile->forward_unconditionally_number = '33198765432';

	// Update a profile (all at once)

	$profile->update(array(
		'forward_unconditionally_number' => '33111223344',
		'forward_on_no_answer_number' => '33199887766',
		'forward_delay' => 10,
		'forward_use_account_callerid_presentation' => true,
		'forward_to_voicemail' => false,
		'backup_number' => '33123456789'
	));

	// Force a profile

	$profile->forced = true;

	// Create a new profile (and force it)

	$new_profile = $service->profiles()->create(array(
		'forward_unconditionally_number' => '33155667788',
		'forward_on_no_answer_number' => '33155443322',
		'forward_delay' => 15,
		'forced' => true
	));

	// Delete a profile

	$new_profile->delete();

	// Delete all profiles (except default, which cannot be deleted)

	$service->profiles()->delete();

	// Attempt to delete the default profile (will raise an exception)

	$default_profile->delete();

} catch (\Keyyo\Manager\Exception\Exception $e) {
	echo 'Error: ', $e->getMessage();
}