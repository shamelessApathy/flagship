<?php

class Ecommerceguys_Inventorymanager_Model_Resource_Vendor_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('vendor/vendor');
    }
}