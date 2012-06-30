<?php
/**
 * @class PayPal\API
 * @author Otto Sabart <seberm[at]gmail[dot]com> (www.seberm.com)
 */

namespace PayPal;

use PayPal\Response,
    PayPal\Request;

use \Nette;
use Nette\Utils\Arrays,
    Nette\Object,
    Nette\Http\SessionSection,
    Nette\Http\Url;


class API extends Object {

    /**
     * Tells which version of PayPay API we want use
     */
    const VERSION = '72.0';

    // PayPal SandBox URLs
    const SANDBOX_END_POINT = 'https://api-3t.sandbox.paypal.com/nvp';

    const SANDBOX_PAYPAL_URL = 'https://www.sandbox.paypal.com/webscr';

    // Direct PayPal URLs
    const END_POINT = 'https://api-3t.paypal.com/nvp';

    const CURRENCY_CROUND = 'CZK';
    const CURRENCY_EURO = 'EUR';

    /** @deprecated */
    private $cart = array();

    // Options
    private $data = array(
                          'proxyHost' => '127.0.0.1',
                          'proxyPort' => '808',
                          'username'  => '',
                          'password'  => '',
                          'signature' => '',
                         );

    const PAYPAL_URL = 'https://www.paypal.com/cgi-bin/webscr';

    private $sandbox = false;

    private $useProxy = false;

    private $token;
    //public $invoiceValue = NULL;


    public function __construct($opts = array()) {

        if (count($opts))
            $this->setData($opts);
    }


    /**
     * Sets object data
     * @var string|array $opts
     * @var mixed $val
     * @return PayPal\API (supports fluent interface)
     */
    public function setData($opts = array(), $val = NULL) {

        if (is_string($opts)) {
            $this->data[$opts] = $val;
        } elseif (is_array($opts)) {
            $this->data = array_merge($this->data, $opts);
        }

        return $this;
    }


    public function getData($key = NULL) {

        if (is_string($key)) {

            if (array_key_exists($key, $this->data)) {
                return $this->data[$key];
            } else {
                return NULL;
            }
        }

        return $this->data;
    }


    /**
     * Adds new item to PayPals Cart
     */
    public function addItem($name, $description, $price, $quantity) {

        $this->cart[] = array(
                                'L_PAYMENTREQUEST_0_NAME' => $name,
                                'L_PAYMENTREQUEST_0_DESC' => $description,
                                'L_PAYMENTREQUEST_0_AMT' => $price,
                                'L_PAYMENTREQUEST_0_QTY' => $quantity,
                            );
    }


    /**
     * Prepares the parameters for the SetExpressCheckout API Call.
     */
    public function setExpressCheckout($shipping, $tax, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $ses) { 

        $data = array(
                       'paymentAction' => $paymentType,
                       'returnUrl' => $returnURL,
                       'cancelUrl' => $cancelURL,
                       'currencyCode' => $currencyCodeType,
                     );

        $request = new Request($data);

        /** @todo It's neccessary to solve quantity! */
        $id = 0;
        foreach ($this->cart as $item) {
            foreach ($item as $key => $val)
                $request->addQuery(array($key.$id => $val));

            $id++;
        }

        $request->addQuery(array(
            'itemsAmount' => $request->query->itemsAmount,
            'taxAmount' => $tax,
            'shippingAmount' => $shipping,
        ));

        $request->addQuery(array('amount' => $request->query->amount));
        $request->setMethod('SetExpressCheckout');

        $response = $this->call($request);

        if ($response->success) {

            $ses->token = $response->token;
            $ses->paymentType = $paymentType;
            $ses->currencyCodeType = $currencyCodeType;
            $ses->amount = $request->query->amount;

            $this->token = $ses->token;

        } 

        return $response;
    }



    /**
     * Prepares the parameters for the SetExpressCheckout API Call.
     */
    public function doExpressCheckout($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $ses)
    {


        $query = array(
            'amount' => $paymentAmount,
            'paymentAction' => $paymentType,
            'returnUrl' => $returnURL,
            'cancelUrl' => $cancelURL,
            'currencyCode' => $currencyCodeType,
            'allowedPaymentMethod' => 'InstantPaymentOnly',
            'method' => 'SetExpressCheckout',
        );

        $response = $this->call(new Request($query));

        if ($response->success) {
            $ses->token = $response->token;
            $this->token = $ses->token;

        } 

        return $response;
    }


    /**
     * Confirmation of paypal payment
     */
    public function confirmExpressCheckout(SessionSection $ses) { 

        $query = array(
                        'amount' => $ses->amount,
                        'payerID' => $ses->payerID,
                        'token' => $ses->token,
                        'paymentAction' => $ses->paymentType,
                        'currencyCode' => $ses->currencyCodeType,
                        'ipAdress' => urlencode($_SERVER['SERVER_NAME']),
                     );

        $request = new Request($query);
        $request->setMethod('DoExpressCheckoutPayment'); // Same as $request->addQuery(array('method', '...'));

        $response = $this->call($request);

        if ($response->success)
            $ses->remove();
           
        return $response;
    }


    public function getShippingDetails($ses) {

        $query = array(
            'token' => $ses->token,
            'method' => 'GetExpressCheckoutDetails', // Same as $request->setMethod('...');
            );

        $response = $this->call(new Request($query));

        if ($response->success)
            $ses->payerID = $response->responseData->payerID;

        return $response;
    }
    

    public function doPayment($paymentType, $ses) {

        /** @todo This communication is not neccessary! */
        $responseDetails = $this->getShippingDetails($ses);

        $query = array(
            'paymentAction' => $paymentType,
            'payerID' => $responseDetails->responseData->payerID,
            'token' => $ses->token,
            'amount' => $responseDetails->responseData->amount,
            'currencyCode' => $responseDetails->responseData->currencyCode,
            'method' => 'DoExpressCheckoutPayment',
            //'PAYMENTREQUEST_0_ALLOWEDPAYMENTMETHOD' => 'InstantPaymentOnly'
        );

        return $this->call(new Request($query));
    }


    public function getEndPoint() {

        return $this->sandbox ? self::SANDBOX_END_POINT : self::END_POINT;
    }


    private function call(Request $request) {

        $ch = curl_init($this->endPoint);

        // Set up verbose mode
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        //turning off the server and peer verification(TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // We should check if paypal has valid certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Just do normal POST
        curl_setopt($ch, CURLOPT_POST, true);

        if ($this->useProxy) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxyHost . ':' . $this->proxyPort);
        }

        $request->addQuery(array(
                            'version' => self::VERSION,
                            'pwd' => $this->password,
                            'user' => $this->username,
                            'signature' => $this->signature,
                    ));

        // POST data
        // Conversion to string is important!
        // It's because it's called __toString magic method which calls
        // in real $request->query->build();
        curl_setopt($ch, CURLOPT_POSTFIELDS, (string) $request); 

        // Execute
        $responseNVP = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->err(curl_error($ch));
        }

        curl_close($ch);

        return new Response($responseNVP);
    }


    /**
     * Generates URL to PayPal for redirection.
     * @return Nette\Http\Url
     */
    public function getUrl()
    {
        $url = new Url($this->sandbox ? self::SANDBOX_PAYPAL_URL : self::PAYPAL_URL);

        $query = array(
            'cmd' => '_express-checkout',
            'token' => $this->token,
        );

        $url->setQuery($query);

        return $url;
    }




    public function setSignature($signature)
    {
        return $this->setData('signature', (string)$signature);
    }


    public function getSignature()
    {
        return $this->getData('signature');
    }


    public function setPassword($password)
    {
        return $this->setData('password', (string)$password);
    }


    public function getPassword()
    {
        return $this->getData('password');
    }


    public function getUsername()
    {
        return $this->getData('username');
    }


    public function setUsername($username)
    {
        return $this->setData('username', (string)$username);
    }


    public function getProxyPort()
    {
        return $this->getData('proxyPort');
    }


    public function setPort($proxyPort)
    {
        $this->data['proxyPort'] = (int)$proxyPort;
        return $this;
    }

    public function getProxyHost()
    {
        return $this->getData('proxyHost');
    }


    public function setHost($proxyHost)
    {
        return $this->setData('proxyHost', (string)$proxyHost);
    }


    public function setSandBox($opt = TRUE)
    {
        $this->sandbox = (bool)$opt;
        return $this;
    }
}
