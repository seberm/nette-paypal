# PayPal Component

## Usage

### Configuration

#### Using Extension (Nette 2.1+)
```yml
paypal:
    api:
       username: 'seberm_1332081338_biz_api1.gmail.com'
       password: '1332081363'
       signature: 'AWiH1IO0zFZrEQbbn0JwDZHbWukIAebmYjpOylRCqBGGgztea2bku.N4'
    sandbox: true # default is false

extensions:
    paypal: Seberm\DI\PayPalExtension
```

####or manually:
```yml
    paypal:
       api:
           username: 'seberm_1332081338_biz_api1.gmail.com'
           password: '1332081363'
           signature: 'AWiH1IO0zFZrEQbbn0JwDZHbWukIAebmYjpOylRCqBGGgztea2bku.N4'
       sandbox: true

factories:
    paypalOrderButton:
        implement: Seberm\Components\PayPal\Buttons\IOrderFactory
        setup:
            - setCredentials(%paypal.api%)
            - setSandBox(%paypal.sandbox%)
```

###Presenter

```php

/**
 * @var \Seberm\Components\PayPal\Buttons\IOrderFactory $orderFactory
 */
private $orderFactory;

/**
 * @var \Seberm\Components\PayPal\Buttons\Order
 */
private $orderButton;


/**
 * @param \Seberm\Components\PayPal\Buttons\IOrderFactory $orderFactory
 */
public function injectOrderFactory(\Seberm\Components\PayPal\Buttons\IOrderFactory $orderFactory)
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
 * @return Seberm\Components\PayPal\Buttons\Order
 */
protected function createComponentPaypalButton()
{
    $control = $this->orderButton;
    $control->setCurrencyCode(\Seberm\PayPal\API\API::CURRENCY_EURO);
    $control->onConfirmation[] = \Nette\Callback::create($this, 'confirmOrder');
    $control->onError[] = \Nette\Callback::create($this, 'errorOccurred');

    $price = 56; // In Euro in this example

    $control->addItemToCart(
    	'Name of a product', 'Product description', $price)
    );

    return $control;
}
```
