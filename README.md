# PayPal Component

##Usage

###Config

####Using Extension (Nette 2.1+)
```yml
paypal:
	api:
		username: 'seberm_1332081338_biz_api1.gmail.com'
		password: '1332081363'
		signature: 'AWiH1IO0zFZrEQbbn0JwDZHbWukIAebmYjpOylRCqBGGgztea2bku.N4'
	sandbox: true # default is false 

extensions:
	paypal: Flame\DI\PayPalExtension
```

####or manually:
```yml
parameters:
	paypal:
		api:
			username: 'seberm_1332081338_biz_api1.gmail.com'
			password: '1332081363'
			signature: 'AWiH1IO0zFZrEQbbn0JwDZHbWukIAebmYjpOylRCqBGGgztea2bku.N4'
		sandbox: true

factories:
	paypalOrderButton:
		implement: Flame\Components\PayPal\Buttons\IOrderFactory
		setup:
			- setCredentials(%paypal.api%)
			- setSandBox(%paypal.sandbox%)
```

###Presenter

```php

/**
 * @var \Flame\Components\PayPal\Buttons\IOrderFactory $orderFactory
 */
private $orderFactory;

/**
 * @var \Flame\Components\PayPal\Buttons\Order
 */
private $orderButton;


/**
 * @param \Flame\Components\PayPal\Buttons\IOrderFactory $orderFactory
 */
public function injectOrderFactory(\Flame\Components\PayPal\Buttons\IOrderFactory $orderFactory)
{
	$this->orderFactory = $orderFactory;
}

public function startup()
{
	parent::startup();

	$this->orderButton = $this->orderFactory->create();
	$this->orderButton->setSessionSection($this->session->getSection('paypal'));
	$this->orderButton->onSuccessPayment[] = \Nette\Callback::create($this, 'processPayment');
}

/**
 * @return Flame\Components\PayPal\Buttons\Order
 */
protected function createComponentPaypalButton()
{

	$control = $this->orderButton;
	$control->setCurrencyCode(\Flame\PayPal\API\API::CURRENCY_EURO);
	$control->onConfirmation[] = \Nette\Callback::create($this, 'confirmOrder');
	$control->onError[] = \Nette\Callback::create($this, 'errorOccurred');

	//$tourModel is instance of PRODUCT
	$control->addItemToCart(
		$tourModel['name'], \Nette\Utils\Strings::substring($tourModel['desc'], 0, 25), $tourModel['price']
	);

	return $control;
}

```
