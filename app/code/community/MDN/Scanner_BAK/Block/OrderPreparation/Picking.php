<?php

class MDN_Scanner_Block_OrderPreparation_Picking extends Mage_Adminhtml_Block_Widget_Form {

	private $_products = null;
	private $_productsArray = null;

	/**
	 * Return products list
	 *
	 */
	public function getProducts() {
		//echo "running getProducts...<br>";
		// this needs to return products in the ERP > Order Prepration > Prepare Orders > Selected list
		// that have not already been picked (display_in_picking_list = 1)
		if ($this->_products == null) {
			//echo "this products is null<br>";
			// should the helper function fetch serials + locations, or should this function select them,
			// or should a separate function be used?
			$this->_products = mage::helper('Orderpreparation/PickingList')->GetProductsSummary();
			//echo "products count: ".count($this->_products)."<br>\n";
			// $serial_location = array();
			// foreach ($this->_products as $product) 
			// {
			// 	$serial_location = $this->getArvSerialAndLocation($product->getId());				
			// 	$product->setData('av_serials',$serial_location['serials']);
			// 	$product->setData('av_locations',$serial_location['locations']);
			// }
			return $this->_products;
		}
		// print_r($this->_products);
		return $this->_products;
	}

	/**
	 * Return product qty
	 *
	 * @param unknown_type $productId
	 * @return unknown
	 */
	public function getProductQty($productId) {
		$array = $this->getProductsArray();

		foreach ($array as $product) {
			if ($product->getId() == $productId)
				return $product->getqty_to_prepare();
		}

		return 0;
	}

	/**
	 * Return products array :
	 * Key = product_id
	 * Value = qty
	 *
	 * @return unknown
	 */
	protected function getProductsArray() {
		if ($this->_productsArray == null)
			$this->_productsArray = mage::helper('Orderpreparation/PickingList')->GetProductsSummary();

		return $this->_productsArray;
	}

	/**
	 * Return barcodes for 1 product
	 *
	 * @param unknown_type $productId
	 */
	public function getBarcodes($productId) {
		return mage::helper('AdvancedStock/Product_Barcode')->getBarcodesForProduct($productId);
	}

	/**
	 * Return Location and serial
	 *
	 */
	public function getArvSerialAndLocation($productId) {
		$serial_location = array();
		$serialsAndLocations = Mage::getModel('AdvancedStock/ProductSerial')
			->getCollection()
			->addFieldToFilter('pps_product_id', $productId);

		foreach ($serialsAndLocations as $serialAndLocation) {
			$serial_location['serials'][] = $serialAndLocation->getData('pps_serial');
			$serial_location['locations'][] = $serialAndLocation->getData('av_location');
		}

		return $serial_location;
	}

	public function getSubmitUrl() {
		return Mage::getUrl('Scanner/OrderPreparation/savePicking');
	}

	/**
	 * Author: Arunv
	 * Return oldest location for 1 product
	 *
	 * @param unknown_type $productId
	 */
	public function getLocation($productId) {
		$location = Mage::getModel('AdvancedStock/ProductSerial')
			->getCollection()
			->addFieldToFilter('pps_product_id', array('eq' => $productId))
			->setOrder('pps_id', 'ASC')
			->setPageSize(1)
			->getFirstItem()
			->getData('av_location');
		if ($location) {

			return $location;
		} else {

			return;
		}
	}

}
