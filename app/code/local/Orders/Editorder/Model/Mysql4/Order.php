<?php
class Orders_Editorder_Model_Mysql4_Order extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('editorder/order', 'entity_id');
    }
}
?>
