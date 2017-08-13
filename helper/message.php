<?php

namespace ka;

class Message
{

	public $code;
	public $data;

	/**
	 * Message constructor.
	 * @param null $code
	 * @param null $data
	 */
	public function __construct($code = null, $data = null)
	{
		$this->code = $code;
		$this->data = $data;
	}

	/**
	 * @param null $code
	 * @param null $data
	 */
	public function set($code = null, $data = null)
	{
		$this->code = $code;
		$this->data = $data;
	}

	/**
	 * @return array
	 */
	public function get()
	{
		return array($this->code, $this->data);
	}

	/**
	 * @return bool
	 */
	public function ifSuccess()
	{
		return $this->code === StatusCodes::HTTP_OK;
	}

}