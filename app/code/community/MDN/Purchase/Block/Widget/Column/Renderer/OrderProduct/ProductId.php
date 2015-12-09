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

class MDN_Purchase_Block_Widget_Column_Renderer_OrderProduct_ProductId
	extends MDN_Purchase_Block_Widget_Column_Renderer_OrderProduct_Abstract 
{
    public function render(Varien_Object $row)
    {
    	//echo $row->getPopProductId();
    	$value =  $row->getData($this->getColumn()->getIndex());
        return '<input type="text" readonly="1" name="productIdArr[]" size="7" value="'.$value.'"/>';
		
    }
    
}