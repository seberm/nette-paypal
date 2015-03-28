<?php
/**
 * IOrderFactory.php
 *
 * @author  Jiří Šifalda <sifalda.jiri@gmail.com>
 * @package Seberm
 *
 * @date    05.12.12
 */

namespace Seberm\Components\PayPal\Buttons;

interface IOrderFactory
{

	/**
	 * @return \Seberm\Components\PayPal\Buttons\Order
	 */
	public function create();

}
