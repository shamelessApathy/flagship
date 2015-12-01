<?php
class Ecommerceguys_Inventorymanager_Adminuser_ShipmanagerController extends Mage_Core_Controller_Front_Action
{
	public function indexAction(){
		$this->loadLayout();
		$this->renderLayout();
	}
	
	public function saveAction(){
		$data = $this->getRequest()->getParams();
		
		
		//echo "<pre>";
		//print_r($data);
		
		
		$realOrderId = $data['order_id'];
		$fedexApi = Mage::getResourceModel('inventorymanager/api_fedex');
		
		
		
		$orderObject = Mage::getModel('sales/order')->load($realOrderId, "increment_id");
		
		
		$senderAddress = array();
		$senderAddress['Contact']['ContactId'] = "fright1";
		$senderAddress['Contact']['PersonName'] = $data['contact_name'];
		$senderAddress['Contact']['Title'] = $data['contact_name'];
		$senderAddress['Contact']['CompanyName'] = $data['company'];
		$senderAddress['Contact']['PhoneNumber'] = $data['phone'];
		$senderAddress['Contact']['email'] = $data['email'];

		$senderAddress['Address']['StreetLines'][0] = $data['address'];
		$senderAddress['Address']['StreetLines'][1] = $data['address2'];
		$senderAddress['Address']['City'] = $data['city'];
		$senderAddress['Address']['StateOrProvinceCode'] = $data['state'];
		$senderAddress['Address']['CountryCode'] = $data['country_id'];
		
		$receiverAddress = array();
		$receiverAddress['Contact']['PersonName'] = $data['receiver']['contact_name'];
		$receiverAddress['Contact']['CompanyName'] = $data['receiver']['company'];
		$receiverAddress['Contact']['PhoneNumber'] = $data['receiver']['phone'];
		
		$receiverAddress['Address']['StreetLines'][0] = $data['receiver']['address'];
		$receiverAddress['Address']['StreetLines'][1] = $data['receiver']['address2'];
		$receiverAddress['Address']['City'] = $data['receiver']['city'];
		$receiverAddress['Address']['StateOrProvinceCode'] = $data['receiver']['state'];
		$receiverAddress['Address']['PostalCode'] = $data['receiver']['postalcode'];
		$receiverAddress['Address']['CountryCode'] = $data['receiver']['country_id'];
		
		
		
		
		$client = new SoapClient($fedexApi->path_to_wsdl, array('trace' => 1));
		
		
		$request['WebAuthenticationDetail'] = array(
				'UserCredential' => array(
				'Key' => $fedexApi->getProperty('key'), 
				'Password' => $fedexApi->getProperty('password')
			)
		);
		
		$request['ClientDetail'] = array(
			'AccountNumber' => $fedexApi->getProperty('shipaccount'), 
			'MeterNumber' => $fedexApi->getProperty('meter')
		);
		
		$request['TransactionDetail'] = array('CustomerTransactionId' => 'Freight Shipment Example');
		
		$request['Version'] = array(
			'ServiceId' => 'ship', 
			'Major' => '17', 
			'Intermediate' => '0', 
			'Minor' => '0'
		);
		
		/*$zip = new ZipArchive();
	    $zip_name = "zipfile.zip";
	    if($zip->open($zip_name, ZIPARCHIVE::CREATE)!==TRUE){
	        $error .= "* Sorry ZIP creation failed at this time";
	    }*/
		
		$totalWeight = 0;
		foreach ($data['serial_key'] as $key => $serialKey){
			$serialObject = Mage::getModel('inventorymanager/label')->load($serialKey, "serial");
			
			//$response = $fedexApi->getResponse($serialObject->getId(), $orderObject->getId());
			
			$serialId = $serialObject->getId();
			
			$shippingLabel = Mage::getBaseDir().'\\media\\fedex\\shippinglabels\\'.$serialId.'-ShippingLabel.pdf';
			$bol = Mage::getBaseDir().'\\media\\fedex\\billoflanding\\'.$serialId.'-BillOfLading.pdf';
		
			if($serialObject && $serialObject->getId()){
				$orderProduct = Mage::getModel('inventorymanager/product')->load($serialObject->getProductId());
				$purchaseorder = Mage::getModel('inventorymanager/purchaseorder')->load($serialObject->getOrderId());
				$productInfoCollection = Mage::getModel('inventorymanager/vendor_productinfo')->getCollection();
				$productInfoCollection->addFieldToFilter('vendor_id', $purchaseorder->getVendorId());
				$productInfoCollection->addFieldToFilter('product_id', $orderProduct->getMainProductId());
				if($productInfoCollection && $productInfoCollection->count() > 0){
					$productInfoObject = $productInfoCollection->getFirstItem();
					if($productInfoObject && $productInfoObject->getId()){
						
						$catalogproduct = Mage::getModel('catalog/product')->load($productInfoObject->getProductId());
						/*echo "<pre>";
						print_r($productInfoObject->getData());
						echo "</pre>";*/
						
						
						$length	= $productInfoObject->getLength();
						$width = $productInfoObject->getWidth();
						$height = $productInfoObject->getHeight();
						$weight	= $productInfoObject->getWeight();
						
						
						$boxLength = $productInfoObject->getBoxLength();
						$boxWidth = $productInfoObject->getBoxWidth();
						$boxHeight = $productInfoObject->getBoxHeight();
						$boxWeight = $productInfoObject->getBoxWeight();
						
						if(isset($data['weight'][$key])){
							$weight = $data['weight'][$key];
						}
						if(isset($data['length'][$key])){
							$length = $data['length'][$key];
						}
						if(isset($data['width'][$key])){
							$width = $data['width'][$key];
						}
						if(isset($data['height'][$key])){
							$height = $data['height'][$key];
						}
						
						if($boxLength == ""){
							$boxLength = $length;
						}
						
						if($boxWidth == ""){
							$boxWidth = $width;
						}
						
						if($boxHeight == ""){
							$boxHeight = $height;
						}
					}
				}
			}
			
			$request['RequestedShipment'] = array(
				'ShipTimestamp' => date('c'),
				'DropoffType' => 'REGULAR_PICKUP', 
				'ServiceType' => 'FEDEX_FREIGHT_ECONOMY', 
				'PackagingType' => 'YOUR_PACKAGING', 
				'Shipper' => $fedexApi->getProperty('freightbilling'),
				'Recipient' => $receiverAddress,
				'ShippingChargesPayment' => $fedexApi->addShippingChargesPayment(),
				'FreightShipmentDetail' => array(
					'FedExFreightAccountNumber' => $fedexApi->getProperty('freightaccount'),
					'FedExFreightBillingContactAndAddress' => $fedexApi->getProperty('freightbilling'),
					'PrintedReferences' => array(
						'Type' => 'SHIPPER_ID_NUMBER',
						'Value' => 'RBB1057'
					),
					'Role' => 'SHIPPER',
					'PaymentType' => 'PREPAID',
					'CollectTermsType' => 'STANDARD',
					'DeclaredValuePerUnit' => array(
						'Currency' => 'USD',
						'Amount' => $catalogproduct->getFinalPrice()
					),
					'LiabilityCoverageDetail' => array(
						'CoverageType' => 'NEW',
						'CoverageAmount' => array(
							'Currency' => 'USD',
							'Amount' => '50'
						)
					),
					'TotalHandlingUnits' => 1,
					'ClientDiscountPercent' => 0,
					'PalletWeight' => array(
						'Units' => 'LB',
						'Value' => 20
					),
					'ShipmentDimensions' => array(
						'Length' => $boxLength,
						'Width' => $boxWidth,
						'Height' => $boxHeight,
						'Units' => 'IN'
					),
					'LineItems' => array(
						'FreightClass' => 'CLASS_050',
						'ClassProvidedByCustomer' => false,
						'HandlingUnits' => 1,
						'Packaging' => 'PALLET',
						'Pieces' => 1,
						'BillOfLaddingNumber' => 'BOL_12345',
						'PurchaseOrderNumber' => 'PO_12345',
						'Description' => $catalogproduct->getName(),
						'Weight' => array(
							'Value' => $weight,
							'Units' => 'LB'
						),
						'Dimensions' => array(
							'Length' => $boxLength ,
							'Width' => $boxWidth ,
							'Height' => $boxHeight ,
							'Units' => 'IN'
						),
						'Volume' => array(
							'Units' => 'CUBIC_FT',
							'Value' => 30
						)
					)
				),	
				'LabelSpecification' => $fedexApi->addLabelSpecification(),
				'ShippingDocumentSpecification' => $fedexApi->addShippingDocumentSpecification(),
				'PackageCount' => 1,
				'PackageDetail' => 'INDIVIDUAL_PACKAGES'                                        
			);
			
			/*echo "<pre>";
			print_r($request); exit;*/
			
			try {
				if($fedexApi->setEndpoint('changeEndpoint')){
					$newLocation = $client->__setLocation(setEndpoint('endpoint'));
				}
				$response = $client->processShipment($request); // FedEx web service invocation  
				
				
				
				
				//echo "<pre>";
				
				//print_r($response); exit;
				
				
				
				$historyObject = Mage::getModel('inventorymanager/shipmanager');
				$historyItem = Mage::getModel('inventorymanager/shipmanager_item');
				$historySender = Mage::getModel('inventorymanager/shipmanager_sender');
				$historyReceiver = Mage::getModel('inventorymanager/shipmanager_receiver');
				
				
				$historyData = array(
					'transaction_detail'	=>	$response->TransactionDetail->CustomerTransactionId,
					'job_id'				=>	$response->JobId,
					'tracking_number'		=>	$response->CompletedShipmentDetail->CarrierCode,
					'careercode' 			=>	$response->CompletedShipmentDetail->MasterTrackingId->TrackingNumber,
					'service_type'			=>	$response->CompletedShipmentDetail->MasterTrackingId->TrackingIdType,
					'weight'				=>	$weight,
					'quoted_value'			=>	$response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->FreightRateDetail->BaseCharges->ExtendedAmount->Amount,
					'shipping_date'			=>	date('Y-m-d'),
					'created_time'			=>	date('Y-m-d'),
				);
				try{
					$historyObject->setData($historyData)->save();
				}catch (Exception $e){
					Mage::log($e->getMessage());
				}
				
				$historyItemData = array(
					'history_id'		=>	$historyObject->getId(),
					'serial'			=>	$serialKey,
					'width'				=>	$width,
					'height'			=>	$height,
					'length'			=>	$length,
					'created_time'			=>	date('Y-m-d'),
				);
				
				$senderAddressData = array(
					'history_id'		=>	$historyObject->getId(),
					'company'			=>	$data['company'],
					'phone'				=>	$data['phone'],
					'contact_name'		=>	$data['contact_name'],
					'address1'			=>	$data['address'],
					'address2'			=>	$data['address2'],
					'city'				=>	$data['city'],
					'postcode'			=>	$data['postalcode'],
					'state'				=>	$data['state'],
					'order_id'			=>	$data['order_id'],
					'country'			=>	$data['country_id'],
					'email'				=>	$data['email'],
					'created_time'		=>	date('Y-m-d'),
				);
				
				$receiverAddressData = array(
					'history_id'		=>	$historyObject->getId(),
					'company'			=>	$data['receiver']['company'],
					'phone'				=>	$data['receiver']['phone'],
					'contact_name'		=>	$data['receiver']['contact_name'],
					'address1'			=>	$data['receiver']['address'],
					'address2'			=>	$data['receiver']['address2'],
					'city'				=>	$data['receiver']['city'],
					'postcode'			=>	$data['receiver']['postalcode'],
					'state'				=>	$data['receiver']['state'],
					'country'			=>	$data['receiver']['country_id'],
					'email'				=>	$data['receiver']['email'],
				);
				
				try {
					$historyItem->setData($historyItemData)->save();
					$historySender->setData($senderAddressData)->save();
					$historyReceiver->setData($receiverAddressData)->save();
				}catch (Exception $e){
					Mage::log($e->getMessage());
				}
				
			    if ($response->HighestSeverity != 'FAILURE' && $response->HighestSeverity != 'ERROR'){
			    	//$this->printSuccess($client, $response);
			        // Create PNG or PDF label
			        // Set LabelSpecification.ImageType to 'PNG' for generating a PNG label
	
			    	$shippingDocuments = $response->CompletedShipmentDetail->ShipmentDocuments;
			    	foreach($shippingDocuments as $key => $value){
			    		$type = $value->Type;
			    		if($type == "OUTBOUND_LABEL"){
			    			$bolImage =$value->Parts->Image;
			    			
			    			$fp = fopen($bol, 'wb');
			    			fwrite($fp, $bolImage);
			        		fclose($fp);
			        		
			        	//	$zip->addFromString(basename($serialId.'-BillOfLading.pdf'),$bolImage);
			        		
			        		//echo '<a href="'.$this->bol.'">BILL OF LANDING</a> was generated.<br/>';
			    		}else if($type == "FREIGHT_ADDRESS_LABEL"){
			    			$addressLabel = $value->Parts->Image;
	
			    			$fp1 = fopen($shippingLabel, 'wb');   
			        		fwrite($fp1, $addressLabel);
			        		fclose($fp1);
			        		
			        		//$zip->addFromString(basename($serialId.'-ShippingLabel.pdf'),$addressLabel);
			        		//echo '<a href="'.$this->shippingLabel.'">Label</a> was generated.<br/>'; 
			    		}
			    	}
			    	
			    	
				    
			    	
			    	
			    }else{
			        $fedexApi->printError($client, $response);
			    }
				Mage::log($client,nill, "fedex.log");    // Write to log file
			} catch (SoapFault $exception) {
				
			    $fedexApi->printFault($exception, $client);
			}
			
		}
		
		//$zip->close();
				    
				        // force to download the zip
        /*header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false);
        header('Content-type: application/zip');
        header('Content-Disposition: attachment; filename="'.$zip_name.'"');
        readfile($zip_name);
        // remove zip file from temp path
        unlink($zip_name);*/
        
        $this->loadLayout();
        $this->renderLayout();
		
		
	}
	
	public function historyAction(){
		$this->loadLayout();
		$this->renderLayout();
	}
	
	public function downloadAction(){
			header("Content-Type: application/octet-stream");
			
			$area = $this->getRequest()->getParam('area');
			$fileName = $this->getRequest()->getParam('filename');
			$fileN = $fileName;
			if($area == "bol"){
				$fileName = 'billoflanding\\'. $fileName;
			}else{
				$fileName = 'shippinglabels\\'. $fileName;
			}
				
			$file = Mage::getBaseDir().'\\media\\fedex\\' . $fileName;
			header("Content-Disposition: attachment; filename=" . urlencode($fileN));   
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");
			header("Content-Description: File Transfer");            
			header("Content-Length: " . filesize($file));
			flush(); // this doesn't really matter.
			$fp = fopen($file, "r");
			while (!feof($fp))
			{
			    echo fread($fp, 65536);
			    flush(); // this is essential for large downloads
			} 
			fclose($fp); 
	}
	
	public function settingAction(){
		$this->loadLayout();
		$this->renderLayout();
	}
	
	public function saveSettingsAction(){
		$data = $this->getRequest()->getParams();
		
		$shipmanagerConfig = Mage::getModel('inventorymanager/shipmanager_config');
		
		try {
		$shipmanagerConfig->saveConfig('inventorymanager/fedex_config/shipaccount', $data['ship_account']);
		$shipmanagerConfig->saveConfig('inventorymanager/fedex_config/freightaccount', $data['fright_account']);
		$shipmanagerConfig->saveConfig('inventorymanager/fedex_config/meter_number', $data['meter_number']);
		$shipmanagerConfig->saveConfig('inventorymanager/fedex_config/key', $data['key']);
		$shipmanagerConfig->saveConfig('inventorymanager/fedex_config/password', $data['password']);
		$shipmanagerConfig->saveConfig('inventorymanager/fedex_config/shipper_address', $data['shipper_address']);
		$this->_redirect('inventorymanager/adminuser_shipmanager/setting');
		}catch (Exception $e){
			echo $e->getMessage();
			exit;
		}
	}
}