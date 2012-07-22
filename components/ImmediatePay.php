<?php

namespace PayPal\Components;

use \PayPal,
    PayPal\API;
use \Nette,
    Nette\Application\UI\Form;
# FastPay = pay amount in one step 
class ImmediatePay extends Nette\Application\UI\Control {

    public $currencyCode = API::CURRENCY_EURO;
    public $paymentType = 'Sale';
    public $amount;

    /**
     * @var API
     */
    protected $api = NULL;
    protected $session;
    
    // Handlers
    public $onSuccessPay;
    public $onCancel;
    public $onError;

    public function __construct($parent = NULL, $name = NULL) {
        parent::__construct($parent, $name);
        $this->api = new API;
    }
    
    protected function attached($presenter) {
        if ($presenter instanceof \Nette\Application\UI\Presenter) {
            $this->session = $this->presenter->session->getSection('paypal');
        }
        parent::attached($presenter);
    }

    public function getErrors() {
        return $this->api->errors;
    }

    public function setCredentials(array $params) {
        $this->api->setData($params);
        return $this;
    }

    public function setSandBox($stat = TRUE) {
        $this->api->setSandbox($stat);
        return $this;
    }

    public function initPayment($amount, $description = null) {
        $response = $this->api->doExpressCheckout($amount, $description, $this->currencyCode, $this->paymentType, $this->buildUrl('processPay'), $this->buildUrl('cancel'), $this->session);
        if ($response->error) {
            $this->onError($response->errors);
            return;
        }
        $this->redirectToPaypal(true);
    } 
 
    public function handleProcessPay() {
        $response = $this->getShippingDetails();
        if ($response->error) {
            $this->onError($response->errors);
            return;
        }
        
        $response = $this->api->doPayment( $this->paymentType, $this->session);
        if ($response->error) {
            $this->onError($response->errors);
            return;
        }
   
        $this->onSuccessPayment($response->responseData);
    }

    public function handleCancel() {
        $response = $this->getShippingDetails();
        if ($response->error) {
            $this->onError($response->errors);
            return;
        }
        $this->onCancel($response->responseData);
    }

    public function getShippingDetails() {
        return $this->api->getShippingDetails($this->session);
    }

    protected function redirectToPaypal($commit = false) {
        $url = $this->api->getUrl($commit);
        $this->presenter->redirectUrl($url);
    }

    protected function buildUrl($signal) {
        $url = $this->presenter->link($this->name . ":${signal}!");
        return (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $url;
    }

    public function setAmount($amount) {
        $this->amount = $amount;
        return $this;
    }

    public function setCurrencyCode($currency) {
        $this->currencyCode = $currency;
        return $this;
    }

    public function setPaymentType($type) {
        $this->paymentType = $type;
        return $this;
    }

}
