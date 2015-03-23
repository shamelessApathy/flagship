<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Sales
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class MDN_AdvancedStock_Model_Sales_Order_Creditmemo extends Mage_Sales_Model_Order_Creditmemo
{
	/**
     * Dispatch order and update planning
     *
     */
    protected function _afterSave()
    {
    	parent::_afterSave();
    	
    	$order = $this->getOrder();
    	
		//plan update for ordered and reserved qty for products
		foreach($this->getAllItems() as $item)
		{
			$productId = $item->getProductId();

			//unreserve products
			$orderItem = $item->getOrderItem();
			$oldReservedQty = $orderItem->getreserved_qty();
			$newReservedQty = $oldReservedQty - $item->getqty();
			if ($newReservedQty > 0)
				$newReservedQty = 0;
			$orderItem->setreserved_qty($newReservedQty)->save();
			
			//plan stock updates
			mage::helper('BackgroundTask')->AddTask('Update stock for product '.$productId.' (from credit memo aftersave)', 
				'AdvancedStock/Product_Base',
				'updateStocksFromProductId',
				$productId
				);	
		}
		
		//dispatch event to allow other extension to catch creditmemo after save event
		Mage::dispatchEvent('advancedstock_creditmemo_aftersave', array('creditmemo'=>$this));

    }
}