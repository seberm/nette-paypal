<?php

/**
 * @class PayPal\Request
 * @author Otto Sabart <seberm[at]gmail[dot]com> (www.seberm.com)
 */

namespace PayPal;

use PayPal\Query;

use \Nette;
use Nette\Object,
    Nette\ArrayHash;


class Request extends Object {

    private $query = NULL;



    public function __construct($query = NULL) {

        $this->setQuery($query);
    }


    public function setQuery($query) {

        if ($query instanceof Query)
            $this->query = $query;
        else {

            if (is_array($query))
                $this->query = new Query($query);
            else $this->query = new Query((array) $query);
        }

        return $this;
    }


    public function getQuery($key = NULL, $default = NULL) {

        if (func_num_args() === 0)
            return $this->query;

        if ($this->query->has($key))
            return $this->query->data->$key;

        return $default;
    }


    public function addQuery($query) {

        $this->query->appendQuery((array) $query);

        return $this;
    }


    public function setMethod($method) {

        $this->addQuery(array(
            'method' => $method,
        ));

        return $this;
    }


    public function __toString() {

        return $this->query->build();
    }
}
