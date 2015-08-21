<?php
/**
 * IOrderFactory.php
 *
 * @author  Jiří Šifalda <sifalda.jiri@gmail.com>
 */

namespace Seberm\Components\PayPal\Buttons;

interface IOrderFactory
{

	/**
	 * @return \Seberm\Components\PayPal\Buttons\Order
	 */
	public function create();

}
