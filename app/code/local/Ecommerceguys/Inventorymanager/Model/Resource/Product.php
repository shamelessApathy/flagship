<?php

class Ecommerceguys_Inventorymanager_Model_Resource_Product extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {    
        $this->_init('inventorymanager/product', 'product_id');
    }
}