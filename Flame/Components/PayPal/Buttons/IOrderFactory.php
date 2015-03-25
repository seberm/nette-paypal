<?php
/**
 * IOrderFactory.php
 *
 * @author  Jiří Šifalda <sifalda.jiri@gmail.com>
 * @package Flame
 *
 * @date    05.12.12
 */

namespace Flame\Components\PayPal\Buttons;

interface IOrderFactory
{

	/**
	 * @return \Flame\Components\PayPal\Buttons\Order
	 */
	public function create();

}
