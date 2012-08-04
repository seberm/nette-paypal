<?php
/**
 * @class Control (Nette 2.0 Component)
 * @author Otto Sabart <seberm[at]gmail[dot]com> (www.seberm.com)
 */

namespace PayPal\Components;

use \PayPal,
    PayPal\API\API;

use \Nette,
    Nette\Application\UI\Form;

class Control extends Nette\Application\UI\Control {

    /**
     * Default currency
     * @var API::Currency
     */
    public $currencyCode = API::CURRENCY_EURO;

    /**
     * @todo Rename it to paymentAction
     */
	public $paymentType;

public $amount;
public $tax;
public $shipping;

	/**
	 * @var API
	 */
	protected $api = NULL;

	/**
	 * @var Nette\Localization\ITranslator
	 */
	protected $translator = NULL;

    /**
     * Basic handlers
     * Every component should have these three basic callbacks.
     * onSuccessPayment - is called if payment transaction is ok
     * onCancel - called when user cancels his payment
     * onError - called if some error during the transaction occoure
     */
	public $onSuccessPayment;
	public $onCancel;
	public $onError;


	public function __construct(Nette\ComponentModel\IContainer $parent = NULL, $name = NULL) {

		parent::__construct($parent, $name);

		$this->api = new API;
	}


	public function setTranslator(Nette\Localization\ITranslator $translator) {

		$this->translator = $translator;
	}


	final public function getTranslator() {

		return $this->translator;
	}


    public function getErrors() {

        return $this->api->errors;
    }


	public function setCredentials(array $params) {

		$this->api->setData($params);
		return $this;
	}


	public function setSandBox($stat = true) {

		$this->api->setSandbox($stat);
		return $this;
	}


    public function getShippingDetails(Nette\Http\SessionSection $section) {

        return $this->api->getShippingDetails($section);
    }


    /**
     * Redirects user to PayPal page
     *
     * @param $commit Setting this to true you can shorten your checkout flow to let buyers complete their purchases on PayPal. Then, you can skip the order confirmation page.
     */
	protected function redirectToPaypal($commit = false) {

		$url = $this->api->getUrl($commit);
		$this->presenter->redirectUrl($url);
	}


	protected function buildUrl($signal) {

		$url = $this->presenter->link($this->name . ":${signal}!");

		// Some better way to do it in Nette?
		return (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $url;
	}


    /**
     * @todo Should have every paypal component amount option?
     */
	public function setAmount($amount) {

		$this->amount = $amount;
		return $this;
	}


	public function setCurrencyCode($currency) {

		$this->currencyCode = $currency;
		return $this;
	}


    /**
     * @todo Rename to setPaymentAction
     */
	public function setPaymentType($type) {

		$this->paymentType = $type;
		return $this;
	}


    public function setShipping($shipping) {

        $this->shipping = (float) $shipping;
        return $this;
    }


    public function setTax($tax) {

        $this->tax = (float) $tax;
        return $this;
    }


    public function setInvoiceValue($value) {

        $this->api->invoiceValue = $value;
        return $this;
    }


    public function addItemToCart($name, $description, $price, $quantity = 1) {

        $this->api->addItem($name, $description, $price, $quantity);
    }

};
