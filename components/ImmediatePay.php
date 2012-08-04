<?php

namespace PayPal\Components;

use \PayPal,
    PayPal\Components\Control;

use \Nette,
    Nette\Application\UI\Form;

# FastPay = pay amount in one step 

class ImmediatePay extends Control {

    protected $session;
    
    
    /**
     * @author Martin Knor
     * @todo Is this necessary?
     */
    protected function attached($presenter) {

        if ($presenter instanceof \Nette\Application\UI\Presenter)
            $this->session = $this->presenter->session->getSection('paypal');

        parent::attached($presenter);
    }


    public function initPayment($amount, $description = NULL) {

        $response = $this->api->doExpressCheckout($amount, $description, $this->currencyCode, $this->paymentType, $this->buildUrl('processPay'), $this->buildUrl('cancel'), $this->session);

        if ($response->error) {

            $this->onError($response->errors);
            return;
        }

        // We want use the useraction == commit
        $this->redirectToPaypal(true);
    } 
 

    public function handleProcessPay() {

        $response = $this->getShippingDetails();

        if ($response->error) {

            $this->onError($response->errors);
            return;
        }
        
        $response = $this->api->doPayment($this->paymentType, $this->session);
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
}
