<?php

/**
 * @class Request
 * @author Otto Sabart <seberm[at]gmail[dot]com> (www.seberm.com)
 */

namespace PayPal\API;

use Nette\Object;


class Request extends Object
{

	private $query = NULL;

	public $translationTable = array(
		'paymentAction' => 'PAYMENTREQUEST_0_PAYMENTACTION',
		'returnUrl' => 'RETURNURL',
		'cancelUrl' => 'CANCELURL',
		'currencyCode' => 'PAYMENTREQUEST_0_CURRENCYCODE',
		'itemsAmount' => 'PAYMENTREQUEST_0_ITEMAMT',
		'taxAmount' => 'PAYMENTREQUEST_0_TAXAMT',
		'shippingAmount' => 'PAYMENTREQUEST_0_SHIPPINGAMT',
		'amount' => 'PAYMENTREQUEST_0_AMT',
		'password' => 'PWD',
		'payerID' => 'PAYERID',
		'ipAdress' => 'IPADDRESS',
		'allowedPaymentMethod' => 'PAYMENTREQUEST_0_ALLOWEDPAYMENTMETHOD',
	);


	public function __construct($query = NULL)
	{
		$this->setQuery($query);
	}



	public function setQuery($query)
	{
		if ($query instanceof Query) {
			$this->query = $query;

		} else {
			if (is_array($query)) {
				$this->query = new Query($query);

			} else {
				$this->query = new Query((array)$query);
			}
		}

        $this->query->translationTable = $this->translationTable;

		return $this;
	}



	public function getQuery($key = NULL, $default = NULL)
	{
		if (func_num_args() === 0) {
			return $this->query;
		}

		if ($this->query->has($key)) {
			return $this->query->data->$key;
		}

		return $default;
	}



	public function addQuery($query)
	{
		$this->query->appendQuery((array)$query);
		return $this;
	}



	public function setMethod($method)
	{
		$this->addQuery(array(
			'method' => $method,
		));
		return $this;
	}



	public function __toString()
	{
		return $this->query->build();
	}
}
