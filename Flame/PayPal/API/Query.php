<?php

/**
 * @class Query
 * @author Otto Sabart <seberm[at]seberm[dot]com> (www.seberm.com)
 */

namespace Flame\PayPal\API;

use Flame\PayPal\Utils;
use Nette\Object;
use Nette\ArrayHash;


class Query extends Object
{

	private $query = '';
   
    /**
     * @var $translationTable It's possible to specify translation table for query keys
     */
    public $translationTable = array();


	public function __construct(array $query)
	{
		$this->query = $query;
	}



	public function has($key)
	{
		return array_key_exists($key, $this->query);
	}



	public function getData($key = NULL, $default = NULL)
	{
		if (func_num_args() === 0) {
			return ArrayHash::from($this->query);
		}

		if ($this->has($key)) {
			return $this->query[$key];
		}

		return $default;
	}



	public function getItemsAmount()
	{
		$prices = Utils::array_keys_by_ereg($this->query, '/^L_PAYMENTREQUEST_0_AMT[0-9]+$/');
		$qtys = Utils::array_keys_by_ereg($this->query, '/^L_PAYMENTREQUEST_0_QTY[0-9]+$/');

		$itemsAmount = 0.0;
		foreach ($prices as $key => $price) {
			$itemsAmount += (float)$price * (float)$qtys[str_replace('0_AMT', '0_QTY', $key)];
		}

		return $itemsAmount;
	}



	public function getAmount()
	{
		return $this->itemsAmount + $this->getData('taxAmount') + $this->getData('shippingAmount');
	}



	public function appendQuery($query, $val = NULL)
	{
		if ($query instanceof Query) {
			$this->query = array_merge($query->getData(), $this->query);
		}

		if (isset($val)) {
			$this->query[$query] = $val;

		} elseif (is_array($query)) {
			$this->query = array_merge($query, $this->query);
		}

		return $this;
	}



	/**
     * Builds basic query to paypal.
     *
	 * @return string query
	 */
	public function build()
	{
        $query = Utils::translateKeys($this->query, $this->translationTable, 'strtoupper');

		return http_build_query($query, '', '&');
	}



	public function __toString()
	{
		return $this->build();
	}

}
