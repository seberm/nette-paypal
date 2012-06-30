<?php

/**
 * @class PayPal\Response
 * @author Otto Sabart <seberm[at]gmail[dot]com> (www.seberm.com)
 */

namespace PayPal;

use PayPal\Utils;

use \Nette;
use Nette\Object,
    Nette\ArrayHash;


class Query extends Object {

    private $query;

    private $translationTable = array(
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


    public function __construct(array $query) {

        $this->query = $query; //Utils::translateKeys($query, $this->translationTable);
    }


    public function has($key) {

        return array_key_exists($key, $this->query);
    }


    public function getData($key = NULL, $default = NULL) {

        if (func_num_args() === 0)
            return ArrayHash::from($this->query);

        if ($this->has($key))
            return $this->query[$key];

        return $default;
    }


    public function getItemsAmount() {

        $prices = Utils::array_keys_by_ereg($this->query, '/^L_PAYMENTREQUEST_0_AMT[0-9]+$/');

        $itemsAmount = 0.0;
        foreach ($prices as $price)
            $itemsAmount += (float) $price;

        return $itemsAmount;
    }


    public function getAmount() {

        return $this->itemsAmount + $this->getData('taxAmount') + $this->getData('shippingAmount');
    }


    public function appendQuery($query, $val = NULL) {

        /*
        if ($query instanceof Query) {

        }
        */
        if (isset($val))
            $this->query[$query] = $val;
        elseif (is_array($query))
            $this->query = array_merge($query, $this->query);
    }


    /**
     * Builds basic query to paypal.
     * @return string query
     */
    public function build() {

        //foreach ($data as $key => $value)
            //$data[$key] = urlencode($value);

        return http_build_query(Utils::translateKeys($this->query, $this->translationTable, 'strtoupper'), '', '&');
    }


    public function __toString() {

        return $this->build();
    }

}
