<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  Copyright (c) 2009 Maison du Logiciel (http://www.maisondulogiciel.com)
 * @author : Olivier ZIMMERMANN
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MDN_AdvancedStock_Helper_Router extends Mage_Core_Helper_Abstract {
    //router mode constants
    const kModeFavoriteStockDefault = 1;
    const kModeStockFavoriteDefault = 2;
    const kModeStockDefault = 3;
    const kModeFavoriteDefault = 4;
    const kModeDefault = 5;

    /**
     * Return all modes available to control router
     */
    public function getAllModes() {
        $retour = array();

        $retour[] = array('value' => self::kModeFavoriteStockDefault,
            'label' => $this->__('Affect to favorite stock, Affect to warehouse having stock, affect to default warehouse'));

        $retour[] = array('value' => self::kModeStockFavoriteDefault,
            'label' => $this->__('Affect to warehouse having stock, affect to favorite stock, affect to default warehouse'));

        $retour[] = array('value' => self::kModeStockDefault,
            'label' => $this->__('Affect to warehouse having stock, affect to default warehouse'));

        $retour[] = array('value' => self::kModeFavoriteDefault,
            'label' => $this->__('Affect to favorite stock, affect to default warehouse'));

        $retour[] = array('value' => self::kModeDefault,
            'label' => $this->__('Affect to default warehouse'));

        return $retour;
    }

    /**
     * Affect warehouse to order item
     */
    public function affectWarehouseToOrderItem($params) {
        $orderItemId = $params['order_item_id'];
        $warehouseId = $params['warehouse_id'];

        $item = mage::getModel('sales/order_item')->load($orderItemId);
        $item->setpreparation_warehouse($warehouseId)->save();

        Mage::dispatchEvent('advancedstock_order_item_preparation_warehouse_changed', array('order_item' => $item));
    }

    /**
     * This method return warehouse to affect to orderitem for preparation / reservations purposes
     */
    public function getWarehouseForOrderItem($orderItem, $order) {
        //if product doesnt manage stock, return null
        $productId = $orderItem->getproduct_id();
        $stockItem = mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
        if ((!$stockItem) || (!$stockItem->ManageStock()))
            return null;

        //get warehouses
        $storeId = $order->getStore();
        $favoriteWarehouseId = $this->getFavoriteWarehouseId($orderItem, $order);
        $warehouseWithStockId = $this->getWarehouseWithStockId($orderItem, $order);
        $defaultWarehouseId = mage::getStoreConfig('advancedstock/router/default_warehouse', $storeId);

        //apply mode
        $mode = mage::getStoreConfig('advancedstock/router/priority', $storeId);
        switch ($mode) {
            case self::kModeFavoriteStockDefault :
                if ($favoriteWarehouseId > 0)
                    return $favoriteWarehouseId;
                else
                    return ( $warehouseWithStockId > 0 ? $warehouseWithStockId : $defaultWarehouseId);
                break;
            case self::kModeStockFavoriteDefault :
                if ($warehouseWithStockId > 0)
                    return $warehouseWithStockId;
                else
                    return ( $favoriteWarehouseId > 0 ? $favoriteWarehouseId : $defaultWarehouseId);
                break;
            case self::kModeStockDefault :
                return ( $warehouseWithStockId > 0 ? $warehouseWithStockId : $defaultWarehouseId);
                break;
            case self::kModeFavoriteDefault :
                return ( $favoriteWarehouseId > 0 ? $favoriteWarehouseId : $defaultWarehouseId);
                break;
            case self::kModeDefault :
                return $defaultWarehouseId;
                break;
        }

        //if we go until here, return default warehouse
        return $defaultWarehouseId;
    }

    /**
     * Return favorite warehouse for product (check that warehouse has order_preparation assignment for order website
     */
    protected function getFavoriteWarehouseId($orderItem, $order) {
        $productId = $orderItem->getproduct_id();
        $websiteId = $order->getStore()->getWebsiteId();

        //todo : move sql in resource models
        $prefix = Mage::getConfig()->getTablePrefix();
        $sql = "
				select 
					stock_id 
				from 
					" . $prefix . "cataloginventory_stock_item ,
					" . $prefix . "cataloginventory_stock_assignment
				where 
					product_id = " . $productId . "
					and is_favorite_warehouse = 1
					and csa_stock_id = stock_id
					and csa_assignment = 'order_preparation'
					
				";
        $warehouseId = mage::getResourceModel('cataloginventory/stock_item_collection')->getConnection()->fetchOne($sql);
        return $warehouseId;
    }

    /**
     * Return first warehouse having product in stock and having order_preparation assignment for order's website
     */
    protected function getWarehouseWithStockId($orderItem, $order) {
        $productId = $orderItem->getproduct_id();
        $websiteId = $order->getStore()->getWebsiteId();

        //todo : move sql in resource models
        $prefix = Mage::getConfig()->getTablePrefix();
        $sql = "
				select 
					stock_id 
				from 
					" . $prefix . "cataloginventory_stock_item,
					" . $prefix . "cataloginventory_stock_assignment
				where 
					product_id = " . $productId . "
					and (qty - stock_ordered_qty) > 0
					and csa_stock_id = stock_id
					and csa_assignment = 'order_preparation'
					
				";

        $warehouseId = mage::getResourceModel('cataloginventory/stock_item_collection')->getConnection()->fetchOne($sql);
        return $warehouseId;
    }

}