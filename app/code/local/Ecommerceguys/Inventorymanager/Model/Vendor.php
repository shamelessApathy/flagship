<?php

class Ecommerceguys_Inventorymanager_Model_Vendor extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('inventorymanager/vendor');
    }
}