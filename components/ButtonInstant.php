<?php
/**
 * @class ButtonInstant (Nette 2.0 Component)
 * @author Otto Sabart <seberm[at]gmail[dot]com> (www.seberm.com)
 */

namespace PayPal\Components;

use \Nette,
    Nette\Application\UI\Form;

class ButtonInstant extends PayPalButton
{

    public $onSuccessBuy;

	public $payImage = 'https://www.paypalobjects.com/en_US/i/btn/x-click-but3.gif';


	public function __construct(Nette\ComponentModel\IContainer $parent = NULL, $name = NULL)
	{
		parent::__construct($parent, $name);

        $this->paymentType = 'Sale';
	}


	public function renderPay()
	{
		$this->template->setFile(__DIR__ . '../templates/pay.latte')
			 ->render();
	}


	protected function createComponentPaypalBuyForm()
	{
		$form = new Form;

		if ($this->translator) {
			$form->setTranslator($this->translator);
		}

		$form->addImage('paypalCheckOut', self::PAYPAL_IMAGE, 'Check out with PayPal');

		$form->onSuccess[] = callback($this, 'initPayment');

		return $form;
	}


	public function initPayment(Form $paypalBuyForm)
	{
		$response = $this->api->doExpressCheckout($this->amount,
                                                $this->currencyCode,
                                                $this->paymentType,
                                                $this->buildUrl('processBuy'),
                                                $this->buildUrl('cancel'),
                                                $this->presenter->session->getSection('paypal'));

		if ($response->error) {

			$this->onError($response->errors);
			return;
		}

		$this->redirectToPaypal();
	}


	protected function createComponentPaypalPayForm()
	{
		$form = new Form;

		if ($this->translator) {
			$form->setTranslator($this->translator);
		}

		$form->addImage('paypalPay', $this->payImage, 'Pay with PayPal');

		$form->onSuccess[] = callback($this, 'processPayment');

		return $form;
	}


	public function processPayment(Form $form)
	{
		$response = $this->api->doPayment(
			$this->paymentType,
			$this->presenter->session->getSection('paypal')
		);


		if ($response->error) {
			$this->onError($response->errors);
			return;
		}

		// Callback
		$this->onSuccessPayment($response->responseData);
	}


	public function handleProcessBuy()
	{
		$response = $this->api->getShippingDetails($this->presenter->session->getSection('paypal'));

		if ($response->error) {
            $this->onError($response->errors);
			return;
		}

		// Callback
		$this->onSuccessBuy($response->responseData);
	}


	public function handleCancel()
	{
		$response = $this->api->getShippingDetails($this->presenter->session->getSection('paypal'));

		if ($response->error) {
            $this->onError($response->errors);
			return;
		}

		// Callback
		$this->onCancel($response->responseData);
	}

}
