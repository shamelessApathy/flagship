<?php
/**
 * GoMage.com
 *
 * GoMage Feed Pro
 *
 * @category     Extension
 * @copyright    Copyright (c) 2010-2013 GoMage.com (http://www.gomage.com)
 * @author       GoMage.com
 * @license      http://www.gomage.com/licensing  Single domain license
 * @terms of use http://www.gomage.com/terms-of-use
 * @version      Release: 3.2
 * @since        Class available since Release 1.0
 */

class GoMage_Feed_Model_Item_Block_Category extends GoMage_Feed_Model_Item_Block {
	
	public function setVars($content = null, $dataObject = null, $remove_empty = false) {
		
		$category = Mage::getModel('catalog/category');
		$parent = $this->getFeed()->getRootCategory()->getId();
		$recursionLevel  = max(0, (int) $this->getFeed()->getStore()->getConfig('catalog/navigation/max_depth'));		
		$categories = $category->getCategories($parent, $recursionLevel, true, true, true);
				
		$contents = array();
		
		foreach ($categories as $category) {			
			if ($category->getLevel() > 1) {				
				$contents[] = parent::setVars($content, $category);			
			}		
		}
		
		return implode("\r\n", $contents);
	
	}

}