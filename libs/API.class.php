<?php
/**
 * @class API
 * @author Otto Sabart <seberm[at]gmail[dot]com> (www.seberm.com)
 */

namespace PayPal\API;

use Nette\Utils\Arrays,
	Nette\Object,
	Nette\Http\SessionSection,
	Nette\Diagnostics\Debugger,
	Nette\Http\Url;


class API extends Object
{

	/**
	 * Tells which version of PayPal API we want use
	 */
	const VERSION = '72.0';

	// PayPal SandBox URLs
	const SANDBOX_END_POINT = 'https://api-3t.sandbox.paypal.com/nvp';
	const SANDBOX_PAYPAL_URL = 'https://www.sandbox.paypal.com/webscr';

	// Direct PayPal URLs
	const END_POINT = 'https://api-3t.paypal.com/nvp';
	const PAYPAL_URL = 'https://www.paypal.com/cgi-bin/webscr';

	/**
	 * @todo Add more currencies
	 */
	const CURRENCY_CROUND = 'CZK';
	const CURRENCY_EURO = 'EUR';

	/** @deprecated ? */
	private $cart = array();

	// Options
	private $data = array(
		'proxyHost' => '127.0.0.1',
		'proxyPort' => '808',
		'username'  => '',
		'password'  => '',
		'signature' => '',
	);

	private $sandbox = false;
	private $useProxy = false;
	private $token;



	/**
	 * @param array $opts
	 */
	public function __construct($opts = array())
	{
		if (count($opts)) {
			$this->setData($opts);
		}
	}



	/**
	 * Sets object data
	 * @var string|array $opts
	 * @var mixed $val
	 * @return API (supports fluent interface)
	 */
	public function setData($opts = array(), $val = NULL)
	{
		if (is_string($opts)) {
			$this->data[$opts] = $val;
		} elseif (is_array($opts)) {
			$this->data = array_merge($this->data, $opts);
		}

		return $this;
	}



	/**
	 * @param string $key
	 *
	 * @return array|NULL
	 */
	public function getData($key = NULL)
	{
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
	public function addItem($name, $description, $price, $quantity)
	{
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
	public function setExpressCheckout($shipping, $tax, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $ses)
	{
		$data = array(
			'paymentAction' => $paymentType,
			'returnUrl' => $returnURL,
			'cancelUrl' => $cancelURL,
			'currencyCode' => $currencyCodeType,
		);

		$request = new Request($data);

		/** @todo It's necessary to solve quantity! */
		$id = 0;
		foreach ($this->cart as $item) {
			foreach ($item as $key => $val)
				$request->addQuery(array($key . $id => $val));

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



	public function doExpressCheckout($paymentAmount, $description, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $ses)
	{
		$query = array(
			'amount' => $paymentAmount,
			'description' => $description,
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
	public function confirmExpressCheckout(SessionSection $ses)
	{
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

		if ($response->success) {
			$ses->remove();
		}

		return $response;
	}



	/**
	 * @param object $ses
	 *
	 * @return Response
	 */
	public function getShippingDetails($ses)
	{
		$query = array(
			'token' => $ses->token,
			'method' => 'GetExpressCheckoutDetails', // Same as $request->setMethod('...');
		);

		$response = $this->call(new Request($query));

		if ($response->success) {
			$ses->payerID = $response->responseData->payerID;
		}

		return $response;
	}



	/**
	 * @return Response
	 */
	public function doPayment($paymentType, $ses)
	{
		/** @todo This communication is not necessary - change implementation! */
		$responseDetails = $this->getShippingDetails($ses);

		$query = array(
			'paymentAction' => $paymentType,
			'payerID' => $responseDetails->responseData->payerID,
			'token' => $ses->token,
			'amount' => $responseDetails->responseData->amount,
			'currencyCode' => $responseDetails->responseData->currencyCode,
			'method' => 'DoExpressCheckoutPayment',
		);

		return $this->call(new Request($query));
	}



	/**
	 * @return string
	 */
	public function getEndPoint()
	{
		return $this->sandbox ? self::SANDBOX_END_POINT : self::END_POINT;
	}



	/**
	 * @param Request $request
	 *
	 * @return Response
	 */
	private function call(Request $request)
	{
		Debugger::firelog($request);

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
		curl_setopt($ch, CURLOPT_POSTFIELDS, (string)$request);

		// Execute
		$responseNVP = curl_exec($ch);

		if (curl_errno($ch)) {
			$this->err(curl_error($ch));
		}

		curl_close($ch);

		$response = new Response($responseNVP);
		Debugger::firelog($response);

		return $response;
	}



	/**
	 * Generates URL to PayPal for redirection.
	 *
	 * @param bool $commit determines whether buyers complete their purchases on PayPal or on your website
	 *
	 * @return Nette\Http\Url
	 */
	public function getUrl($commit = false)
	{
		$url = new Url($this->sandbox ? self::SANDBOX_PAYPAL_URL : self::PAYPAL_URL);

		$query = array(
			'cmd' => '_express-checkout',
			'token' => $this->token,
		);

		if ($commit) {
			$query['useraction'] = 'commit';
		}

		$url->setQuery($query);

		return $url;
	}



	/**
	 * @param $signature
	 *
	 * @return API
	 */
	public function setSignature($signature)
	{
		return $this->setData('signature', (string)$signature);
	}



	/**
	 * @return array|null
	 */
	public function getSignature()
	{
		return $this->getData('signature');
	}



	/**
	 * @param $password
	 *
	 * @return API
	 */
	public function setPassword($password)
	{
		return $this->setData('password', (string)$password);
	}



	/**
	 * @return array|NULL
	 */
	public function getPassword()
	{
		return $this->getData('password');
	}



	/**
	 * @return array|NULL
	 */
	public function getUsername()
	{
		return $this->getData('username');
	}



	/**
	 * @param $username
	 *
	 * @return API
	 */
	public function setUsername($username)
	{
		return $this->setData('username', (string)$username);
	}



	/**
	 * @return array|NULL
	 */
	public function getProxyPort()
	{
		return $this->getData('proxyPort');
	}



	/**
	 * @param $proxyPort
	 *
	 * @return API
	 */
	public function setPort($proxyPort)
	{
		$this->data['proxyPort'] = (int)$proxyPort;
		return $this;
	}



	/**
	 * @return array|NULL
	 */
	public function getProxyHost()
	{
		return $this->getData('proxyHost');
	}



	/**
	 * @param $proxyHost
	 *
	 * @return API
	 */
	public function setHost($proxyHost)
	{
		return $this->setData('proxyHost', (string)$proxyHost);
	}



	/**
	 * @param bool $opt
	 *
	 * @return API
	 */
	public function setSandBox($opt = TRUE)
	{
		$this->sandbox = (bool)$opt;
		return $this;
	}
}
