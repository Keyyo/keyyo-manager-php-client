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

namespace Keyyo\Manager;

/**
 * Represents a client of the Manager webservice provided by Keyyo
 *
 * @author Jérémy Touati
 */
class Client extends Resource
{
	/**
	 * The OAuth2 access token used to authenticate against the webservice
	 * @var string
	 */
	protected $access_token;

	/**
	 * An array of options, e.g. the format to use for numbers, or any other Manager option
	 * @var array
	 */
	protected $options = array();

	/**
	 * Constructs an instance of a client that will connect to a specific version of the Manager
	 * @param string $version The version of the remote Manager to access (e.g. "1.0")
	 * @param string $access_token A valid OAuth2 access token
	 * @param array $options An array of options, e.g. the format to use for numbers, or any other Manager option
	 */
	public function __construct($version, $access_token, array $options = array())
	{
		parent::__construct($this, $this->get_webservice_url() . '/' . $version, false);
		$this->access_token = $access_token;

		$this->options['localized_numbers'] = !empty($options['localized_numbers']);
	}

	/**
	 * Calls a webservice and return its body as an associative array (this should not be called from outside this library)
	 * @param string $verb The HTTP verb to use (GET, POST or DELETE are supported)
	 * @param string $url The webservice URL to target
	 * @param array $data An optional data array (for POST queries)
	 * @throws \Keyyo\Manager\Exception\Exception
	 * @return array An associative array containing the result of the query
	 */
	public function query($verb, $url, array $data = array())
	{
		return $this->get_resource_contents($this->call_webservice($verb, $url, $data));
	}

	/**
	 * Calls a webservice and return HTTP status code, headers and body
	 * @param string $verb The HTTP verb to use (GET, POST or DELETE are supported)
	 * @param string $url The webservice URL to target
	 * @param array $data An optional data array (for POST queries)
	 * @throws \Keyyo\Manager\Exception\WebserviceQueryException
	 * @return array An array containing three values: HTTP status code (as integer), headers (as array), body (as string)
	 */
	protected function call_webservice($verb, $url, array $data = array())
	{
		// Initialize cURL

		if (!function_exists('curl_version'))
			throw new \Keyyo\Manager\Exception\WebserviceQueryException('cURL is not available');

		$curl_request = curl_init();
		if ($curl_request === false)
			throw new \Keyyo\Manager\Exception\WebserviceQueryException('Could not initialize cURL request');

		// Append "options" GET parameters to the URI

		$get_parameters = array();
		if ($this->test_option('localized_numbers', true))
			$get_parameters['localized_numbers'] = '1';

		if (count($get_parameters) > 0)
			$url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($get_parameters);

		// Data

		$data = array_map(function($value) {
			if (is_bool($value))
				$value = $value ? 1 : 0;

			return $value;
		}, $data);

		// Build cURL option array

		$curl_options = array(
			CURLOPT_HEADER => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => $url,
			CURLOPT_CUSTOMREQUEST => $verb,
			CURLOPT_POSTFIELDS => http_build_query($data),
			CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $this->access_token)
		);
		if (curl_setopt_array($curl_request, $curl_options) === false)
			throw new \Keyyo\Manager\Exception\WebserviceQueryException('Could not set cURL request options (' . curl_error($curl_request) . ')');

		// Execute cURL request and retrieve HTTP status code

		$response = curl_exec($curl_request);
		if ($response === false)
			throw new \Keyyo\Manager\Exception\WebserviceQueryException('Could not execute cURL request (' . curl_error($curl_request) . ')');

		if (($http_status = curl_getinfo($curl_request, CURLINFO_HTTP_CODE)) === false
				|| ($header_size = curl_getinfo($curl_request, CURLINFO_HEADER_SIZE)) === false)
			throw new \Keyyo\Manager\Exception\WebserviceQueryException('Could not retrieve http status code or header size (' . curl_error($curl_request) . ')');

		curl_close($curl_request);

		// Retrieve headers

		$header = substr($response, 0, $header_size);

		if (preg_match_all('/^([^:]+):\s+(.*)$/m', $header, $matches, PREG_SET_ORDER) === false)
			throw new \Keyyo\Manager\Exception\WebserviceQueryException('Could not parse http response headers');

		$headers = array();

		if (count($matches) > 1)
			foreach (array_slice($matches, 1) as $match)
				$headers[$match[1]] = isset($match[2]) ? $match[2] : null;

		// Retrieve body

		$body = substr($response, $header_size);

		return array($http_status, $headers, $body);
	}

	/**
	 *
	 * @param array $call_result An array containing three values: HTTP status code (as integer), headers (as array), body (as string)
	 * @throws \Keyyo\Manager\Exception\Exception
	 * @return array An associative array containing the result of the query
	 */
	protected function get_resource_contents($call_result)
	{
		list($http_status, $headers, $body) = $call_result;
		$status_reason = isset($headers['X-Status-Reason']) ? $headers['X-Status-Reason'] : null;

		switch ($http_status)
		{
			case 200:
				break;
			case 403:
				throw new \Keyyo\Manager\Exception\ForbiddenAccessException($status_reason);
			case 404:
				throw new \Keyyo\Manager\Exception\ResourceNotFoundException($status_reason);
			case 500:
			default:
				throw new \Keyyo\Manager\Exception\InternalServerErrorException($status_reason);
		}

		if (is_string($body) && strlen($body) > 0)
		{
			$resource_contents = json_decode($body, true);
			if (is_null($resource_contents))
				throw new \Keyyo\Manager\Exception\WebserviceQueryException('Could not parse the request\'s response body');
		}

		else
			$resource_contents = null;

		return $resource_contents;
	}

	/**
	 * @return string The absolute URL of the (root of the) webservice
	 */
	protected function get_webservice_url()
	{
		return 'https://api.keyyo.com/manager';
	}

	/**
	 * @param string $name An option name
	 * @param string $value An option value
	 * @return boolean Whether the option with the given name exists and has the given value
	 */
	private function test_option($name, $value = true)
	{
		return isset($this->options[$name]) && $this->options[$name] === $value;
	}
}
