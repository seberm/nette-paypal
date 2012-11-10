<?php
/**
 * @class Instant (Nette 2.0 Component)
 * @author Otto Sabart <seberm[at]gmail[dot]com> (www.seberm.com)
 */

namespace Flame\Components\PayPal\Buttons;

use Nette\Application\UI\Form;

class Instant extends \Flame\Components\PayPal\Button
{

	public $onSuccessBuy;

	public $payImage = 'https://www.paypalobjects.com/en_US/i/btn/x-click-but3.gif';


	public function renderPay()
	{
		$this->template
			->setFile(__DIR__ . '/../templates/pay.latte')
			->render();
	}



	public function initPayment(Form $paypalBuyForm)
	{
		$response = $this->api->doExpressCheckout($this->amount,
			NULL,
			$this->currencyCode,
			$this->paymentType,
			$this->buildUrl('processBuy'),
			$this->buildUrl('cancel'),
			$this->session);

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



	public function processPayment(/*Form $form*/)
	{
		$response = $this->api->doPayment(
			$this->paymentType,
			$this->session
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
		$response = $this->api->getShippingDetails($this->session);

		if ($response->error) {
			$this->onError($response->errors);
			return;
		}

		// Callback
		$this->onSuccessBuy($response->responseData);
	}



	public function handleCancel()
	{
		$response = $this->api->getShippingDetails($this->session);

		if ($response->error) {
			$this->onError($response->errors);
			return;
		}

		// Callback
		$this->onCancel($response->responseData);
	}

}
