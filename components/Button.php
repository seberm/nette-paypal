<?php
/**
 * @class Button (Nette 2.0 Component)
 * @author Otto Sabart <seberm[at]gmail[dot]com> (www.seberm.com)
 */

namespace PayPal\Components;


use PayPal\API\API;

use Nette\Application\UI\Form;


abstract class Button extends Control
{

	/**
	 * PayPal's button image file
	 */
	const PAYPAL_BUTTON_IMAGE = 'https://www.paypalobjects.com/en_US/i/btn/btn_xpressCheckout.gif';


	// Handlers
	// Here it's possible to add some special handlers
    // ...


	public function renderBuy()
	{
		$this->template
			 ->setFile(__DIR__ . '/templates/buy.latte')
			 ->render();
	}


	/**
	 * If some submodule wants to create new button,
	 * it should call this function for its creation.
	 *
	 * @return Nette\Application\UI\Form $button
	 */
	public function createComponentButton()
	{
		$form = new Form;

		if ($this->translator) {
			$form->setTranslator($this->translator);
		}

		$form->addImage('paypalCheckOutButton', self::PAYPAL_BUTTON_IMAGE, 'Check out with PayPal');
		$form->onSuccess[] = callback($this, 'initPayment');

		return $form;
	}


	/**
	 * This function is called when user click on PayPal button.
	 * The action shoud be implemented by user.
	 */
	abstract public function initPayment(Form $button);

}
