# PayPal Component

### Installation
Simplest installation is using `composer`:
```
$ composer require seberm/paypal-component
```

or manually you can edit `composer.json`:
```
"require": {
        "seberm/paypal-component": "1.0.*"
}
```

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

    // Is possible to set shipping
    $button->shipping = 4.3;

    // or set a tax
    $button->tax = 3.1;

    $price = 56; // In Euro in this example

    $control->addItemToCart('Product A', 'A - Product description', $price));
    $control->addItemToCart('Product B', 'B - Product description', 123));

    return $control;
}
```

##### Don't forget to define callback methods
This method is called after successful confirmation. It has one argument `$data`.

```php
public function successPayment($data) {
    /**
     * Here you can proccess information about user. For example save him to the
     * database...
     */

     $payerID = $data->payerID;
     $firstName = $data->firstName;
     $lastName = $data->lastName;
     $email = $data->email;

     // See dump($data);
}
```

Following method is called if some error occures (for example error in
communication). It receives an array of errors.
```php
public function errorOccurred($errors)  { ... }
```

```php
public function confirmOrder($data)   { ... } // Is called If payment inicialization succeeds
public function canceled($data)       { ... } // Called if user cancels his order
```


### Adding PayPal button to a template
Add following control macro where you want to have your PayPal button.

```
{control paypalButton}
```
