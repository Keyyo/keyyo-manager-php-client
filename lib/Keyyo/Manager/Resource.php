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
 * Represents a resource retrieved and manipulated through the Client
 *
 * @author Jérémy Touati
 */
class Resource implements \Iterator, \ArrayAccess, \Countable
{
	/**
	 * A reference to the client
	 * @var \Keyyo\Manager\Client
	 */
	protected $client;

	/**
	 * The absolute URL of this resource
	 * @var string
	 */
	protected $url;

	/**
	 * Whether or not this resource represents a collection or an individual resource
	 * @var boolean
	 */
	protected $is_collection;

	/**
	 * An associative array containing all of the resource contents
	 * @var array
	 */
	protected $contents;

	/**
	 * An associative array containing all the resource properties (contents except metadata)
	 * @var array
	 */
	protected $properties;

	/**
	 * Constructs a resource
	 * @param \Keyyo\Manager\Client $client A reference to the client
	 * @param string $url The absolute URL of this resource
	 * @param boolean $is_collection Whether this resource is a collection (as opposed to an individial resource)
	 * @param string $resource_contents An optional array containing the contents to load this resource from
	 */
	public function __construct(\Keyyo\Manager\Client $client, $url, $is_collection, $resource_contents = null)
	{
		$this->client = $client;
		$this->is_collection = $is_collection;
		$this->url = $url;

		if (is_array($resource_contents))
			$this->initialize_from_resource_contents($resource_contents);
	}

	/**
	 * Creates a resource by adding an URL segment to the current one
	 * @param string $method_name The name of the URL segment
	 * @param array $parameters If this array contains one parameter, it will be added as a segment too
	 * @return \Keyyo\Manager\Resource The new resource
	 */
	public function __call($method_name, array $parameters)
	{
		$url = $this->url . '/' . $method_name;
		$is_collection = true;

		if (isset($parameters[0]))
		{
			if (is_array($parameters[0]))
				$url .= '/?' . http_build_query(array('filters' => $parameters[0]));
			else
			{
				$url .= '/' . $parameters[0];
				$is_collection = false;
			}
		}

		return new Resource($this->client, $url, $is_collection);
	}

	/**
	 * Retrieves a property from the resource
	 * @param string $property The name of the property
	 * @throws \Keyyo\Manager\Exception\NoSuchPropertyException
	 * @return string The value of this property, if it does exist
	 */
	public function __get($property)
	{
		$this->fetch();

		if (!isset($this->properties[$property]))
			throw new \Keyyo\Manager\Exception\NoSuchPropertyException('No such property: ' . $property);

		return $this->properties[$property];
	}

	/**
	 * Updates a property on the resource
	 * @param string $property The name of the property
	 * @param string $value The value to set
	 * @throws \Keyyo\Manager\Exception\Exception
	 */
	public function __set($property, $value)
	{
		$this->update(array($property => $value));
	}

	/**
	 * Creates a sub-resource on a collection resource
	 * @param array $properties An associative array of values to set on the created subresource
	 * @return \Keyyo\Manager\Resource
	 * @throws \Keyyo\Manager\Exception\Exception
	 */
	public function create(array $properties = array())
	{
		$resource_contents = $this->client->query('POST', $this->url, $properties);
		return new Resource($this->client, $resource_contents['_links']['self']['href'], false, $resource_contents);
	}

	/**
	 * Updates one or several properties on the resource
	 * @param array $properties The array of properties to set
	 */
	public function update(array $properties = array())
	{
		$resource_contents = $this->client->query($this->is_collection ? 'PUT' : 'POST', $this->url, $properties);
		$this->initialize_from_resource_contents($resource_contents);
	}

	/**
	 * Deletes the current resource
	 * @throws \Keyyo\Manager\Exception\Exception
	 */
	public function delete()
	{
		$resource_contents = $this->client->query('DELETE', $this->url);
		$this->initialize_from_resource_contents($resource_contents);
	}

	/**
	 * (non-PHPdoc)
	 * @see Iterator::rewind()
	 */
	public function rewind()
	{
		$this->fetch();
		return reset($this->contents);
	}

	/**
	 * (non-PHPdoc)
	 * @see Iterator::current()
	 */
	public function current()
	{
		$this->fetch();
		$value = current($this->contents);

		if (!($value instanceof Resource))
			$value = $this->contents[$this->key()] = new Resource($this->client, $value['_links']['self']['href'], false, $value);

		return $value;
	}

	/**
	 * (non-PHPdoc)
	 * @see Iterator::key()
	 */
	public function key()
	{
		$this->fetch();
		return key($this->contents);
	}

	/**
	 * (non-PHPdoc)
	 * @see Iterator::next()
	 */
	public function next()
	{
		$this->fetch();
		return next($this->contents);
	}

	/**
	 * (non-PHPdoc)
	 * @see Iterator::valid()
	 */
	public function valid()
	{
		$this->fetch();
		return isset($this->contents[$this->key()]);
	}

	/**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetExists()
	 */
	public function offsetExists($offset)
	{
		$this->fetch();
		return isset($this->contents[$offset]);
	}

	/**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetGet()
	 */
	public function offsetGet($offset)
	{
		$this->fetch();
		return $this->contents[$offset];
	}

	/**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetSet()
	 */
	public function offsetSet($offset, $value)
	{
		$this->fetch();
		$this->contents[$offset] = $value;
	}

	/**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetUnset()
	 */
	public function offsetUnset($offset)
	{
		$this->fetch();
		unset($this->contents[$offset]);
	}

	/**
	 * (non-PHPdoc)
	 * @see Countable::count()
	 */
	public function count()
	{
		$this->fetch();
		return count($this->contents);
	}

	/**
	 * @return array The contents of this resource
	 */
	public function get_contents()
	{
		$this->fetch();
		return $this->contents;
	}

	/**
	 * @return array The properties (contents except meta data) of this resource
	 */
	public function get_properties()
	{
		$this->fetch();
		return $this->properties;
	}

	/**
	 * @return string The type of this resource, or null if there is none
	 */
	public function get_type()
	{
		return isset($this->contents['_resource_type']) ? $this->contents['_resource_type'] : null;
	}

	/**
	 * Fetches the contents of this resource from the webservice, and caches it into $contents
	 */
	protected function fetch()
	{
		if (isset($this->contents))
			return;

		$resource_contents = $this->client->query('GET', $this->url);

		$this->initialize_from_resource_contents($resource_contents);
	}

	/**
	 * Initializes the current resource from resource contents returned by the webservice
	 * @param array $resource_contents
	 */
	private function initialize_from_resource_contents($resource_contents)
	{
		if ($this->is_collection)
		{
			$this->contents = array();
			if (isset($resource_contents['_embedded']))
			{
				foreach ($resource_contents['_embedded'] as $sub_resources_contents)
				{
					foreach ($sub_resources_contents as $sub_resource_contents)
						$this->contents[] = $sub_resource_contents;
				}
			}
			$this->properties = null;
		}
		else
		{
			$this->contents = $resource_contents;
			$this->properties = $this->filter_properties($this->contents);
		}
	}

	/**
	 * Filters contents to retrieve only its properties (non meta-data)
	 * @param array $contents
	 * @return array
	 */
	private function filter_properties($contents)
	{
		$properties = array();

		if (is_array($contents))
		{
			foreach ($contents as $key => $value)
				if (strpos($key, '_') !== 0)
					$properties[$key] = $value;
		}

		return $properties;
	}
}