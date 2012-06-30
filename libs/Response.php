<?php

/**
 * @class PayPal\Response
 * @author Otto Sabart <seberm[at]gmail[dot]com> (www.seberm.com)
 */

namespace PayPal;

use PayPal\Utils;

use \Nette;
use Nette\Object,
    Nette\Iterators\CachingIterator,
    Nette\ArrayHash;


class Response extends Object {

    private $responseData = NULL;


    // We have to check if all these keys exist in array 
    public $CART_ITEM_KEYS = array(
                'l_name',
                'l_qty',
                'l_taxamt',
                'l_amt',
                'l_desc',
                'l_itemweightvalue',
                'l_itemlengthvalue',
                'l_itemwidthvalue',
                'l_itemheightvalue',
            );


    // Contents only items which we want to normalize
    private $translationTable = array(
       'CHECKOUTSTATUS' => 'checkoutStatus', 
       'CORRELATIONID' => 'correlationID',
       'PAYERID' => 'payerID',
       'PAYERSTATUS' => 'payerStatus',
       'FIRSTNAME' => 'firstName',
       'LASTNAME' => 'lastName',
       'COUNTRYCODE' => 'countryCode',
       'SHIPTONAME' => 'shipToName',
       'SHIPTOSTREET' => 'shipToStreet',
       'SHIPTOCITY' => 'shipToCity',
       'SHIPTOSTATE' => 'shipToState',
       'SHIPTOZIP' => 'shipToZip',
       'SHIPTOCOUNTRYCODE' => 'shipToCountryCode',
       'SHIPTOCOUNTRYNAME' => 'shipToCountryName',
       'ADDRESSSTATUS' => 'addressStatus',
       'CURRENCYCODE' => 'currencyCode',
       'AMT' => 'amount',
       'SHIPPINGAMT' => 'shippingAmount',
       'HANDLINGAMT' => 'handlingAmount',
       'TAXAMT' => 'taxAmount',
       'INSURANCEAMT' => 'insuranceAmount',
       'SHIPDISCAMT' => 'shipDiscauntAmount',

       /** @todo Request */
        /*
       'PAYMENTREQUEST_0_CURRENCYCODE' => 'requestCurrencyCode',
       'PAYMENTREQUEST_0_AMT' => '',
       'PAYMENTREQUEST_0_SHIPPINGAMT' => 
       'PAYMENTREQUEST_0_HANDLINGAMT' => 
       'PAYMENTREQUEST_0_TAXAMT' => 
       'PAYMENTREQUEST_0_INSURANCEAMT' => 
       'PAYMENTREQUEST_0_SHIPDISCAMT' => 
       'PAYMENTREQUEST_0_INSURANCEOPTIONOFFERED' =>
       'PAYMENTREQUEST_0_SHIPTONAME' => 
       'PAYMENTREQUEST_0_SHIPTOSTREET' => 
       'PAYMENTREQUEST_0_SHIPTOCITY' => 
       'PAYMENTREQUEST_0_SHIPTOSTATE' => 
       'PAYMENTREQUEST_0_SHIPTOZIP' => 
       'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => 
       'PAYMENTREQUEST_0_SHIPTOCOUNTRYNAME' => 
       'PAYMENTREQUESTINFO_0_ERRORCODE' => 
        */
       'PAYMENTINFO_0_TRANSACTIONTYPE' => 'transactionType',
       'PAYMENTINFO_0_PAYMENTTYPE' => 'paymentType',
       /*
   insuranceoptionselected => "false" (5)
   shippingoptionisdefault => "false" (5)
   paymentinfo_0_transactionid => "34L30484PN295671J" (17)
   paymentinfo_0_transactiontype => "expresscheckout" (15)
   paymentinfo_0_paymenttype => "instant" (7)
   paymentinfo_0_ordertime => "2012-06-26T00:34:59Z" (20)
   paymentinfo_0_amt => "34.67" (5)
   paymentinfo_0_taxamt => "0.00" (4)
   paymentinfo_0_currencycode => "EUR" (3)
   paymentinfo_0_paymentstatus => "Pending" (7)
   paymentinfo_0_pendingreason => "multicurrency" (13)
   paymentinfo_0_reasoncode => "None" (4)
   paymentinfo_0_protectioneligibility => "Eligible" (8)
   paymentinfo_0_protectioneligibilitytype => "ItemNotReceivedEligible,UnauthorizedPaymentEligible" (51)
   paymentinfo_0_securemerchantaccountid => "3BQUMDNDV8FWW" (13)
   paymentinfo_0_errorcode => "0"
   paymentinfo_0_ack => "Success" (7)
        */
    );


    public function __construct($data) {

        $formattedData = $this->deformatNVP($data);
        $this->responseData = Utils::translateKeys($formattedData, $this->translationTable);
    }


    public function getResponseData($key = NULL) {

        if (isset($key))
            return array_key_exists($key, $this->responseData) ? $this->responseData[$key] : NULL;
        else
            return ArrayHash::from($this->responseData);
    }

    public function setResponseData($arr) {

        $this->responseData = $arr;
    }




    /**
     * Returns PayPal's cart items in Nette\ArrayHash or false if there are no items.
     *
     * @param $data Data from PayPal response
     * @return Nette\ArrayHash or boolean
     */
    public function getCartItems() {

        $patternKeys = '';
        $iterator = new CachingIterator($this->CART_ITEM_KEYS);
        for ($iterator->rewind(); $iterator->valid(); $iterator->next()) {

            if ($iterator->isFirst())
                $patternKeys .= '(';

            $patternKeys .= $iterator->current();

            if ($iterator->hasNext())
                $patternKeys .= '|';

            if ($iterator->isLast())
                $patternKeys .= ')';
        }

        $pattern = '/^' .$patternKeys. '[0-9]+$/';

        $itemsData = Utils::array_keys_by_ereg($this->responseData, $pattern);

        if (empty($itemsData))
            return false;

        $items = array();
        $itemsCount = count($itemsData) / count($this->CART_ITEM_KEYS);

        // We must control if the result of division is integer.
        // Because if not, it means the count of keys in PayPal cart item changed.
        assert(is_int($itemsCount));

        for ($i = 0; $i < $itemsCount; ++$i) {

            $keys = array();
            foreach ($this->CART_ITEM_KEYS as $key)
                $keys[] = $key . $i;

            if (Utils::array_keys_exist($itemsData, $keys)) {

                $items[] = array(
                    'name'          => $itemsData['l_name'            .$i],
                    'quantity'      => $itemsData['l_qty'             .$i],
                    'taxAmount'     => $itemsData['l_taxamt'          .$i],
                    'amount'        => $itemsData['l_amt'             .$i],
                    'description'   => $itemsData['l_desc'            .$i],
                    'weightValue'   => $itemsData['l_itemweightvalue' .$i],
                    'lengthValue'   => $itemsData['l_itemlengthvalue' .$i],
                    'widthValue'    => $itemsData['l_itemwidthvalue'  .$i],
                    'heightValue'   => $itemsData['l_itemheightvalue' .$i],
                );
            }
        }

        return ArrayHash::from($items);
    }


    public function getSuccess() {

        if (strcasecmp($this->getResponseData()->ack, 'success') === 0 ||
            strcasecmp($this->getResponseData()->ack, 'successwithwarning') === 0)
            return true;

        //if (strcmp($this->responseData['ACK'], 'success') === 0 ||
        //   strcmp($this->responseData['ACK'], 'successwithwarning') === 0)
        //    return true;

        return false;
    }


    public function getToken() {

        return $this->getResponseData()->token;
    }


    public function getErrors() {

        return array_values(Utils::array_keys_by_ereg($this->responseData, '/^l_longmessage[0-9]+/'));
    }


    /**
     * If some error, true is returned.
     * @return bool
     */
    public function isError() {

        return !empty($this->errors);
    }


    private function err($message) {

        $this->errors[] = $message;
    }


    private function deformatNVP($query) {

        parse_str($query, $data);
        return $data;
    }
}
