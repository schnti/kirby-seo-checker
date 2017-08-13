<?php

namespace ka;

class HTTP
{

	private static function isJson($string)
	{
		json_decode($string);
		return (json_last_error() === JSON_ERROR_NONE);
	}

	private static function getHeaderArray($headerString)
	{
		$headers = array();

		// "" are needed for \r\n !!
		foreach (explode("\r\n", $headerString) as $i => $line)
			if ($i === 0)
				$headers['http_code'] = $line;
			else {
				list ($key, $value) = explode(': ', $line);

				if (!empty($key))
					$headers[$key] = $value;
			}

		return $headers;
	}

	public static function get($url, $header = array(), $getHeader = false)
	{
		$message = new Message();

		$curl = curl_init($url);

		// Transfer als String zurückzuliefern, anstatt ihn direkt auszugeben.
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		// follow a redirects
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		if ($getHeader) {
			curl_setopt($curl, CURLOPT_VERBOSE, true);
			curl_setopt($curl, CURLOPT_HEADER, true);
		}

		// header
		//		$httpHeader = array('Content-Length: 0');
		$httpHeader = array();
		$httpHeader = array_merge($httpHeader, $header);

		curl_setopt($curl, CURLOPT_HTTPHEADER, $httpHeader);

		// no valide ssl cert
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($curl);
		$header = null;

		if ($getHeader) {
			$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
			$headerString = substr($response, 0, $header_size);
			$header = self::getHeaderArray($headerString);

			$body = substr($response, $header_size);
		} else {
			$body = $response;
		}

		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$results = (self::isJson($body)) ? json_decode($body, true) : $body;

		$message->code = $httpCode;
		$message->data = array(
			'header' => $header,
			'data' => $results,
		);

		curl_close($curl);

		return $message;
	}

	public static function post($url, $postData, $header = array())
	{

		$message = new Message();

		$curl = curl_init($url);

		// POST
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData));

		// Transfer als String zurückzuliefern, anstatt ihn direkt auszugeben.
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		// follow a redirects
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		// header
		// $httpHeader = array('Content-Length: 0');
		$httpHeader = array();
		$httpHeader = array_merge($httpHeader, $header);

		curl_setopt($curl, CURLOPT_HTTPHEADER, $httpHeader);

		// no valide ssl cert
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($curl);

		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$results = (self::isJson($response)) ? json_decode($response, true) : $response;

		$message->code = $httpCode;
		$message->data = array(
			'data' => $results,
		);

		curl_close($curl);

		return $message;
	}
}