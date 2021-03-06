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

class MDN_Purchase_Block_Widget_Column_Renderer_OrderProduct_Name
	extends MDN_Purchase_Block_Widget_Column_Renderer_OrderProduct_Abstract 
{
    public function render(Varien_Object $row)
    {
		$html = $row->getpop_product_name();
		
		//add configurable attribute values
		if (mage::getStoreConfig('advancedstock/miscellaneous/display_configurable_attributes') == 1)
		{
			$productId = $row->getpop_product_id();
			$url = $this->getUrl('AdvancedStock/Products/Edit', array('product_id' => $row->getpop_product_id()));
			$description = mage::helper('AdvancedStock/Product_ConfigurableAttributes')->getDescription($productId);
			$html = '<a href="'.$url.'" target="_blank">' . $html . '</a> ' . $description;
		}
		
		return $html;
    }
    
}