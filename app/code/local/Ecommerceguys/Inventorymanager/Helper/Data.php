<?php

class Ecommerceguys_Inventorymanager_Helper_Data extends Mage_Core_Helper_Abstract
{	
	public function getProductInfoFieldTitles(){
		return array(
			"description" => $this->__("Description"),
			"cost"	=> $this->__("Cost"),
			"length" => $this->__("Length"),
			"width"	=> $this->__("Width"),
			"height" => $this->__("Height"),
			"fun_spec" => $this->__("Fun Specs"),
			"material" => $this->__("Material"),
			"lighting" => $this->__("Lighting"),
			"files" => $this->__("Drawings"),
			"main_image" => $this->__("Main Image"),
			"upc" => $this->__("UPC"),
			"weight" => $this->__("Weight"),
			"box_weight" => $this->__("Box Weight"),
		);
	}
	
	public function getSerial($orderId=0){
		 $characters = '0123456789';
		 $random_string_length = 4;
		 $string = '';
		 for ($i = 0; $i < $random_string_length; $i++) {
		      $string .= $characters[rand(0, strlen($characters) - 1)];
		 }
		 
		 $serialKey = date('Y-m')."-".$orderId."-".$string;
		 
		 return $serialKey;
	}
	
	public function getOrderedProductStatusArray(){
		$status = Mage::getResourceModel('inventorymanager/label')->getStatuses();
		$statusArray = array_map(array($this, formatStatusArray), $status);
		return $statusArray;
	}
	
	public function formatStatusArray($value){
		if(isset($value['status']))
			return $value['status'];
		return "";
	}
	
	public function getStaticStatusOptions(){
		return array(
			$this->__("Ready to Ship"),
			$this->__("Processing"),
			$this->__("Shipped"),
			$this->__("Arrived"),
			$this->__("Ready to Sell"),
			$this->__("Sold")
		);
	}
	
	public function getVendorMaterials(){
		$vendor = Mage::getSingleton('core/session')->getVendor();
		$materials = $vendor->getMaterial();
		$materialArray = array_map(array($this, 'formatMaterialArray'), $materials);
		return $materialArray;
	}
	
	public function formatMaterialArray($value){
		if(isset($value['material']))
			return $value['material'];
		return "";
	}
	
	public function getVendorLighting(){
		$vendor = Mage::getSingleton('core/session')->getVendor();
		$lighting = $vendor->getLighting();
		$lightingArray = array_map(array($this, 'formatLightingArray'), $lighting);
		return $lightingArray;
	}
	
	public function formatLightingArray($value){
		if(isset($value['lighting']))
			return $value['lighting'];
		return "";
	}
	
	public function resizeImage($_file_name, $width = 139, $height = 139, $linkpath = ""){
		if(substr($linkpath,0,1) == "/"){
			$linkpath = substr($linkpath,1);
		}
		if(strlen($linkpath) > 0 && substr($linkpath,strlen($linkpath)-1) != "/"){
			$linkpath.="/";
		}
		$dirPath = str_replace("/",DS,$linkpath);
		$_media_dir = Mage::getBaseDir('media') . DS . $dirPath;
        $cache_dir = $_media_dir . 'resize' . DS; // Here i create a resize folder. for upload new category image
		if (!file_exists($cache_dir . $_file_name) && file_exists($_media_dir . $_file_name)) {
			if (!is_dir($cache_dir)) {
				mkdir($cache_dir);
            }
            try {
	            $_image = new Varien_Image($_media_dir . $_file_name);
	            $_image->constrainOnly(true);
	            $_image->keepAspectRatio(true);
	            $_image->keepFrame(false);
	            $_image->keepTransparency(true);
	            $_image->resize($width, $height); // change image height, width
	            $_image->save($cache_dir . $_file_name);
            }catch (Exception $e){
            	return $e->getMessage();
            }
        }
        $catImg =Mage::getBaseUrl('media') .  $linkpath ."resize/" . $_file_name;
		return  $catImg ; 
	}
	
	public function getLocations(){
		$locations = Mage::getModel('inventorymanager/label')->getLocations();
		$locationArray = array_map(array($this, formatLocationArray), $locations);
		return $locationArray;
	}
	
	public function formatLocationArray($value){
		if(isset($value['location'])){
			return $value['location'];
		}
		return "";
	}
	
	public function getVendorFromRequest(){
		$request = Mage::app()->getRequest()->getParams();
		if(isset($request['serial_key'])){
			$serialKey = $request['serial_key'];
			$labelObject = Mage::getModel('inventorymanager/label')->load($serialKey, 'serial');
			if($labelObject && $labelObject->getId()){
				$orderId = $labelObject->getOrderId();
				$porchaseorder = Mage::getModel('inventorymanager/purchaseorder')->load($orderId);
				if($porchaseorder && $porchaseorder->getId())
					return $porchaseorder->getVendorId();
			}
		}
	}
	
	public function sendNewOrderEmail($orderId){
		$order = Mage::getModel('inventorymanager/purchaseorder')->load($orderId);
		
		if($order && $order->getId()){
			
			
			
			$vendor = Mage::getModel('inventorymanager/vendor')->load($order->getVendorId());
			$emailTemplate  = Mage::getModel('core/email_template')->loadDefault('purchaseorder_email');
			$variables = array();
			$variables['username'] = $vendor->getUsername();
			$variables['login_link'] = Mage::getUrl('inventorymanager/vendor/login');
			$processedTemplate = $emailTemplate->getProcessedTemplate($variables);
			
			$senderEmail = Mage::getStoreConfig('trans_email/ident_general/email');
			$senderName = Mage::getStoreConfig('trans_email/ident_general/name');
			
			$emailTemplate->setSenderName($senderName);
			$emailTemplate->setSenderEmail($senderEmail);
			$emailTemplate->setTemplateSubject($this->__("New Purchase Order"));
			
			try {
				$emailTemplate->send($vendor->getEmail(),$vendor->getFirstname() . " " . $vendor->getlastname(), $variables);
			}catch (Exception $e){
				echo $e->getMessage();
			}
		}
	}
	
	public function getAgentLocations(){
		$locations = Mage::getResourceModel('inventorymanager/label')->getLocationsForAgent();
		$agentLocations = array();
		foreach ($locations as $location){
			if(isset($location['location'])){
				$agentLocations[] = $location['location'];
			}
		}
		$agentLocations = array_filter($agentLocations);
		return $agentLocations;
	}
	
	public function wsdlPath(){
		return Mage::getBaseUrl( Mage_Core_Model_Store::URL_TYPE_WEB, true ) . "fedex/";
	}
}