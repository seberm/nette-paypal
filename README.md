# PayPal Component

## Usage

### Configuration

#### A) Using DI Extension (Nette 2.1+)
Add following lines into your `config.neon` file.

```yml
parameters: ...

---------------------------
paypal:
    api:
       username: 'seberm_1332081338_biz_api1.gmail.com'
       password: '1332081363'
       signature: 'AWiH1IO0zFZrEQbbn0JwDZHbWukIAebmYjpOylRCqBGGgztea2bku.N4'
    sandbox: true # default is false

extensions:
    paypal: Seberm\DI\PayPalExtension
---------------------------

php:
    date.timezone: Europe/Prague
    ...
```

More about DI container extensions you can find here: https://doc.nette.org/en/2.3/di-extensions

#### B) Manually throught factories
Alternatively you can configure component via factories.

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
        implement: Seberm\Components\PayPal\Buttons\IOrderFactory
        setup:
            - setCredentials(%paypal.api%)
            - setSandBox(%paypal.sandbox%)
```

### Example of a Presenter
Firstly, you have to get IOrderFactory object.

#### Getting IOrderFactory using DI extensions (method A)
```php
/** @var \Seberm\Components\PayPal\Buttons\IOrderFactory @inject */
public $factory;

```

#### Getting IOrderFactory using nette factories (method B)
```php
/** @var \Seberm\Components\PayPal\Buttons\IOrderFactory $factory */
public $factory;

/**
 * @param \Seberm\Components\PayPal\Buttons\IOrderFactory $factory
 */
public function injectOrderFactory(\Seberm\Components\PayPal\Buttons\IOrderFactory $factory)
{
    $this->factory = $factory;
}
```

Following code will be the same for both methods.

```php
/** @var \Seberm\Components\PayPal\Buttons\Order */
private $orderButton;

public function startup()
{
    parent::startup();

    $this->orderButton = $this->factory->create();
    $this->orderButton->setSessionSection($this->session->getSection('paypal'));
    $this->orderButton->onSuccessPayment[] = array($this, 'successPayment');
}

/**
 * Creates new button control. After that you can load this control in template
 * via {control paypalButton}.
 * @return Seberm\Components\PayPal\Buttons\Order
 */
protected function createComponentPaypalButton()
{
    $control = $this->orderButton;
    $control->setCurrencyCode(\Seberm\PayPal\API\API::CURRENCY_EURO);
    $control->onConfirmation[] = array($this, 'confirmOrder');
    $control->onError[] = array($this, 'errorOccurred');
    $control->onCancel[] = array($this, 'canceled');

    $price = 56; // In Euro in this example

    $control->addItemToCart(
    	'Name of a product', 'Product description', $price)
    );

    return $control;
}
```

##### Don't forget to define callback methods
```php
public function successPayment() { ... }
public function confirmOrder()   { ... }
public function errorOccurred()  { ... }
public function canceled()       { ... }
```


### Adding PayPal button to a template
Add following control macro where you want to have your PayPal button.

```
{control paypalButton}
```
