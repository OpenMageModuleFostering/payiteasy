<?php
/**
 * Created by JetBrains PhpStorm.
 * User: radu
 * Date: 13.06.2013
 * Time: 12:40
 * To change this template use File | Settings | File Templates.
 */

class Payiteasy_Onlinepayment_Model_ApiRequest_Base extends ArrayObject
{
    public $id = 'x1';
    public $version = '1.0';

    protected function _prepareAmount($amount)
    {
        return number_format($amount, 2) * 100;
    }
}