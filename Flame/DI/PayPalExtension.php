<?php

namespace Flame\DI;

use Nette;

class PayPalExtension extends Nette\DI\CompilerExtension
{
	public $defaults = array(
		'sandbox' => FALSE,
		'redirectToConfirm' => TRUE
	);

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$builder->addDefinition($this->prefix('orderButton'))
				->setImplement('Flame\Components\PayPal\Buttons\IOrderFactory')
				->addSetup('setCredentials', array($config['api']))
				->addSetup('setSandbox', array($config['sandbox']))
				->addSetup('setRedirectToConfirm', array($config['redirectToConfirm']));
	}

	/**
	 * Helper method
	 * @param Nette\Configurator $configurator
	 */
	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('paypal', new PayPalExtension());
		};
	}
}
