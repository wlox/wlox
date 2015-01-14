<?php
/*
EasyBitcoin-PHP

A simple class for making calls to Bitcoin's API using PHP.
https://github.com/aceat64/EasyBitcoin-PHP

====================

The MIT License (MIT)

Copyright (c) 2013 Andrew LeCody

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

====================

// Initialize Bitcoin connection/object
$bitcoin = new Bitcoin('username','password');

// Optionally, you can specify a host, port and protocol (HTTP and HTTPS).
$bitcoin = new Bitcoin('username','password','host','port','http');
// Defaults are:
//	host = localhost
//	port = 8332
//	proto = http

// Make calls to bitcoind as methods for your object. Responses are returned as an array.
// Examples:
$bitcoin->getinfo();
$bitcoin->getrawtransaction('0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098',1);
$bitcoin->getblock('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f');

// The full response (not usually needed) is stored in $this->response while the raw JSON is stored in $this->raw_response

// When a call fails for any reason, it will return false and put the error message in $this->error
// Example:
echo $bitcoin->error;

// The HTTP status code can be found in $this->status and will either be a valid HTTP status code or will be 0 if cURL was unable to connect.
// Example:
echo $bitcoin->status;

*/

class Bitcoin {
	// Configuration options
	public $username;
	public $password;
	public $proto;
	public $host;
	public $port;
	public $url;

	// Information and debugging
	public $status;
	public $error;
	public $raw_response;
	public $response;
	private $id = 0;

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $host
	 * @param int $port
	 * @param string $proto
	 * @param string $url
	 */
	function __construct($username, $password, $host = 'localhost', $port = 8332, $proto = 'http', $url = null) {
		$this->username = $username;
		$this->password = $password;
		$this->proto = $proto;
		$this->host = $host;
		$this->port = $port;
		$this->url = $url;
	}

	function __call($method, $params) {
		$this->status = null;
		$this->error = null;
		$this->raw_response = null;
		$this->response = null;

		// If no parameters are passed, this will be an empty array
		$params = array_values($params);

		// The ID should be unique for each call
		$this->id++;

		// Build the request, it's ok that params might have any empty array
		$request = json_encode(array(
			'method' => $method,
			'params' => $params,
			'id' => $this->id,
		));

		// Build the cURL session
		$curl = curl_init("{$this->proto}://{$this->username}:{$this->password}@{$this->host}:{$this->port}/{$this->url}");
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_HTTPHEADER => array('Content-type: application/json'),
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $request,
		);
		curl_setopt_array($curl, $options);

		// Execute the request and decode to an array
		$this->raw_response = curl_exec($curl);
		$this->response = json_decode($this->raw_response,true);

		// If the status is not 200, something is wrong
		$this->status = curl_getinfo($curl,CURLINFO_HTTP_CODE);

		// If there was no error, this will be an empty string
		$curl_error = curl_error($curl);

		curl_close($curl);

		if (!empty($curl_error)) {
			$this->error = $curl_error;
		}

		if ($this->response['error']) {
			// If bitcoind returned an error, put that in $this->error
			$this->error = $this->response['error']['message'];
		} elseif($this->status != 200) {
			// If bitcoind didn't return a nice error message, we need to make our own
			switch($this->status) {
				case 400:
					$this->error = 'HTTP_BAD_REQUEST';
					break;
				case 401:
					$this->error = 'HTTP_UNAUTHORIZED';
					break;
				case 403:
					$this->error = 'HTTP_FORBIDDEN';
					break;
				case 404:
					$this->error = 'HTTP_NOT_FOUND';
					break;
			}
		}

		if ($this->error) {
			trigger_error($this->error,E_USER_WARNING);
			return false;
		} else {
			return $this->response['result'];
		}
	}
}
