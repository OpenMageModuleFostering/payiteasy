<?php

class Payiteasy_Onlinepayment_Model_System_Config_Source_Order_Action {

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'preauthorization', 'label'=>Mage::helper('onlinepayment')->__('Preauthorization')),
            array('value' => 'authorization', 'label'=>Mage::helper('onlinepayment')->__('Authorization')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'preauthorization' => Mage::helper('onlinepayment')->__('Preauthorization'),
            'authorization' => Mage::helper('onlinepayment')->__('Authorization'),
        );
    }

}
