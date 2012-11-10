<?php
/**
 * OrderFactory.php
 *
 * @author  Jiří Šifalda <sifalda.jiri@gmail.com>
 * @package Flame
 *
 * @date    10.11.12
 */

namespace Flame\Components\PayPal\Buttons;

class OrderFactory extends \Flame\Application\ControlFactory
{

	/**
	 * @var array
	 */
	private $credentials;

	/**
	 * @var bool
	 */
	private $sandBoxMode;

	/**
	 * @param array $params
	 */
	public function setCredentials(array $params)
	{
		$this->credentials = $params;
	}

	/**
	 * @param $mode
	 */
	public function setSandBoxMode($mode)
	{
		$this->sandBoxMode = (bool) $mode;
	}

	/**
	 * @param null $data
	 * @return Order
	 */
	public function create($data = null)
	{
		$control = new Order();
		$control->setSandBox($this->sandBoxMode);
		$control->setCredentials($this->credentials);
		return $control;
	}

}
