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
class MDN_Purchase_Model_Observer {

    /**
     * Update supply needs for product
     *
     * @param Varien_Event_Observer $observer
     */
    public function purchase_update_supply_needs_for_product(Varien_Event_Observer $observer) {
        $productId = $observer->getEvent()->getproduct_id();
        $from = $observer->getEvent()->getfrom();
        $title = 'Update supply needs for product #' . $productId;
        if ($from != '')
            $title .= ' (' . $from . ')';
        mage::helper('BackgroundTask')->AddTask($title,
                'purchase',
                'updateSupplyNeedsForProduct',
                $productId
        );
    }

    /**
     * called on product duplication
     * @param Varien_Event_Observer $observer
     */
    public function catalog_model_product_duplicate(Varien_Event_Observer $observer) {
        $newProduct = $observer->getEvent()->getnew_product();

        //reset out of stock period
        $newProduct->setsupply_date();
        $newProduct->setwaiting_for_delivery_qty(0);
        $newProduct->setmanual_supply_need_date();
        $newProduct->setmanual_supply_need_comments('');
        $newProduct->setmanual_supply_need_qty(0);

    }

}

