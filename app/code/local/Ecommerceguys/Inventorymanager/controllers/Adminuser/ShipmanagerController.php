<?php
require_once(Mage::getBaseDir().'/tcpdf/tcpdf.php');
//require_once('FPDI/fpdf.php');
//require_once('FPDI/fpdi.php');
class Ecommerceguys_Inventorymanager_Adminuser_ShipmanagerController extends Mage_Core_Controller_Front_Action
{
	public function indexAction(){

		$this->loadLayout();
		$this->renderLayout();
	}

	public function saveAction(){
		$boxLength = $boxWidth = $boxHeight = '';
		$data = $this->getRequest()->getParams();
		$realOrderId = $data['order_id'];
		if($data['service_type'] != "FEDEX_FREIGHT_ECONOMY" AND $data['service_type'] != "FEDEX_FREIGHT_PRIORITY"){
			$fedexApi = Mage::getResourceModel('inventorymanager/api_fedexground');
			//$orderObject = Mage::getModel('sales/order')->load($realOrderId, "increment_id");
			$receiverstate = $this->_regioncode($data['receiver_state_id']);
			$senderAddress = array();
			$senderAddress['Contact']['ContactId'] = "ground1";
			$senderAddress['Contact']['PersonName'] = $data['contact_name'];
			$senderAddress['Contact']['Title'] = $data['contact_name'];
			$senderAddress['Contact']['CompanyName'] = $data['company'];
			$senderAddress['Contact']['PhoneNumber'] = $data['phone'];
			$senderAddress['Contact']['email'] = $data['email'];

			$senderAddress['Address']['StreetLines'][0] = $data['address'];
			$senderAddress['Address']['StreetLines'][1] = "-";
			$senderAddress['Address']['City'] = $data['city'];
			$senderAddress['Address']['StateOrProvinceCode'] = $data['state'];
			$senderAddress['Address']['CountryCode'] = $data['country_id'];
			$senderAddress['Address']['PostalCode'] = $data['postalcode'];
			
			$receiverAddress = array();
			$receiverAddress['Contact']['PersonName'] = $data['receiver']['contact_name'];
			$receiverAddress['Contact']['CompanyName'] = $data['receiver']['company'];
			$receiverAddress['Contact']['PhoneNumber'] = $data['receiver']['phone'];
			
			$receiverAddress['Address']['StreetLines'][0] = $data['receiver']['address'];
			$receiverAddress['Address']['StreetLines'][1] = "";
			$receiverAddress['Address']['City'] = $data['receiver']['city'];
			$receiverAddress['Address']['StateOrProvinceCode'] = $receiverstate;//$data['receiver']['state'];
			$receiverAddress['Address']['PostalCode'] = $data['receiver']['postalcode'];
			$receiverAddress['Address']['CountryCode'] = $data['receiver']['country_id'];
			
			/*
			echo "<pre>";
			print_r($fedexApi->getProperty('freightbilling'));
			print_r($senderAddress);
			exit;
			*/
			
			$client = new SoapClient($fedexApi->path_to_wsdl, array('trace' => 1));

			$request['WebAuthenticationDetail'] = array(
				'ParentCredential' => array(
					'Key' => $fedexApi->getProperty('parentkey'), 
					'Password' => $fedexApi->getProperty('parentpassword')
				),
				'UserCredential' => array(
					'Key' => $fedexApi->getProperty('key'), 
					'Password' => $fedexApi->getProperty('password')
				)
			);

			$request['ClientDetail'] = array(
				'AccountNumber' => $fedexApi->getProperty('shipaccount'), 
				'MeterNumber' => $fedexApi->getProperty('meter')
			);
			$request['TransactionDetail'] = array('CustomerTransactionId' => '*** Ground Shipping Request ***');
			$request['Version'] = array(
				'ServiceId' => 'ship', 
				'Major' => '17', 
				'Intermediate' => '0', 
				'Minor' => '0'
			);
			
			$totalWeight = 0;
			$serialCount = 0;
			foreach ($data['serial_key'] as $key => $serialKey){


				$serialObject = Mage::getModel('inventorymanager/label')->load($serialKey, "serial");
				
				$serialCount++;
				
			
				$shippingLabel = Mage::getBaseDir().'/media/fedex/shippinglabels/'.$serialKey.'-ShippingLabel.png';
				//$bol = Mage::getBaseDir().'/media/fedex/billoflanding/'.$serialKey.'-BillOfLading.pdf';
				
				$productName = "proline item";
				$productPrice = 0;
				
				if($serialObject && $serialObject->getId()){
					
					
					$serialId = $serialObject->getId();
				
					$shippingLabel = Mage::getBaseDir().'/media/fedex/shippinglabels/'.$serialId.'-ShippingLabel.png';
					//$bol = Mage::getBaseDir().'/media/fedex/billoflanding/'.$serialId.'-BillOfLading.pdf';
					
					$orderProduct = Mage::getModel('inventorymanager/product')->load($serialObject->getProductId());
					$catalogproduct = Mage::getModel('catalog/product')->load($orderProduct->getMainProductId());
					$purchaseorder = Mage::getModel('inventorymanager/purchaseorder')->load($serialObject->getOrderId());
					$productInfoCollection = Mage::getModel('inventorymanager/vendor_productinfo')->getCollection();
					$productInfoCollection->addFieldToFilter('vendor_id', $purchaseorder->getVendorId());
					$productInfoCollection->addFieldToFilter('product_id', $orderProduct->getMainProductId());
					if($productInfoCollection && $productInfoCollection->count() > 0){
						$productInfoObject = $productInfoCollection->getFirstItem();
						if($productInfoObject && $productInfoObject->getId()){
							
							
							$productPrice = $catalogproduct->getFinalPrice();
							$productName = $catalogproduct->getName();
							
							$length	= $productInfoObject->getLength();
							$width = $productInfoObject->getWidth();
							$height = $productInfoObject->getHeight();
							$weight	= $productInfoObject->getWeight();
							
							$boxLength = $productInfoObject->getBoxLength();
							$boxWidth = $productInfoObject->getBoxWidth();
							$boxHeight = $productInfoObject->getBoxHeight();
							$boxWeight = $productInfoObject->getBoxWeight();
						}
					}
					
					if($catalogproduct->getIsInStock() == 1){
						$stock_item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($catalogproduct->getId());
						if (!$stock_item->getId()) {
							$productQty = $stock_item->getQty();
							$productQty -= 1;
						    $stock_item->setData('product_id', $catalogproduct->getId());
						    //$stock_item->setData('stock_id', 1); 
						    $isInStock = 1;
						    if($productQty < 1){
						    	$isInStock = 0;
						    }
						    $stock_item->setData('is_in_stock', $isInStock);
						    $stock_item->setData('qty', $productQty);
						    $stock_item->save();
						}
						
					}
				}
		
				
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
				
				if(isset($data['price'][$key]) && $data['price'][$key] > 0){
					$productPrice = $data['price'][$key];
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
				
				$request['RequestedShipment'] = array(
				'ShipTimestamp' => date('c'),
				'DropoffType' => 'REGULAR_PICKUP', // valid values REGULAR_PICKUP, REQUEST_COURIER, DROP_BOX, BUSINESS_SERVICE_CENTER and STATION
				'ServiceType' => 'FEDEX_GROUND', // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
				'PackagingType' => 'YOUR_PACKAGING', // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
				'Shipper' => $senderAddress,
				'Recipient' => $receiverAddress,
				'ShippingChargesPayment' => $fedexApi->addShippingChargesPayment(),
				'LabelSpecification' => $fedexApi->addLabelSpecification(), 
				/* Thermal Label */
				/*
				'LabelSpecification' => array(
					'LabelFormatType' => 'COMMON2D', // valid values COMMON2D, LABEL_DATA_ONLY
					'ImageType' => 'EPL2', // valid values DPL, EPL2, PDF, ZPLII and PNG
					'LabelStockType' => 'STOCK_4X6.75_LEADING_DOC_TAB',
					'LabelPrintingOrientation' => 'TOP_EDGE_OF_TEXT_FIRST'
				),
				*/
				'PackageCount' => 1,
				'PackageDetail' => 'INDIVIDUAL_PACKAGES',                                        
				'RequestedPackageLineItems' => array(
					'0' => $fedexApi->addPackageLineItem1($data['po']))
				);

				try {

					if($fedexApi->setEndpoint('changeEndpoint')){
						$newLocation = $client->__setLocation($fedexApi->setEndpoint('endpoint'));
					}
					$response = $client->processShipment($request); // FedEx web service invocation



						$historyObject = Mage::getModel('inventorymanager/shipmanager');
						$historyItem = Mage::getModel('inventorymanager/shipmanager_item');
						$historySender = Mage::getModel('inventorymanager/shipmanager_sender');
						$historyReceiver = Mage::getModel('inventorymanager/shipmanager_receiver');
						
						
						
						$quotedVal = 0;
						if(isset($response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->FreightRateDetail->BaseCharges->ExtendedAmount->Amount)){
							$quotedVal = $response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->FreightRateDetail->BaseCharges->ExtendedAmount->Amount;
						}
						
						$historyData = array(
							'transaction_detail'	=>	isset($response->TransactionDetail->CustomerTransactionId)?$response->TransactionDetail->CustomerTransactionId:"",
							'job_id'				=>	isset($response->JobId)?$response->JobId:"",
							'tracking_number'		=>	isset($response->CompletedShipmentDetail->CarrierCode)?$response->CompletedShipmentDetail->CarrierCode:"",
							'careercode' 			=>	isset($response->CompletedShipmentDetail->MasterTrackingId->TrackingNumber)?$response->CompletedShipmentDetail->MasterTrackingId->TrackingNumber:"",
							'service_type'			=>	isset($response->CompletedShipmentDetail->MasterTrackingId->TrackingIdType)?$response->CompletedShipmentDetail->MasterTrackingId->TrackingIdType:"",
							'weight'				=>	$weight,
							'quoted_value'			=>	$quotedVal,
							'shipping_date'			=>	date('Y-m-d'),
							'created_time'			=>	date('Y-m-d'),
						);
						try{
							
							
							
							$historyObject->setData($historyData)->save();
							
							//print_r(get_class($historyObject)); exit;
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
							//'address2'			=>	$data['address2'],
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
						//	'address2'			=>	$data['receiver']['address2'],
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
				        if($data["order_from"] != 2){ 
							$shipmentModel = Mage::getModel("inventorymanager/shipmanager_shipment");
							$shipmentModel->completeShipment($realOrderId,$response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds->TrackingNumber,$response->CompletedShipmentDetail->CarrierCode,$shipmentCarrierTitle='');
				    		
				    	}else{
				    		$shipmentModel = Mage::getModel("inventorymanager/shipmanager_shipment");
				    		$zencartShipmentStatus = $shipmentModel->getzencartOrderShippedStatus($realOrderId);
							//$zencartShipmentStatus = 0;
				    		if($zencartShipmentStatus == 0){
				    			$zencartOrderData = $shipmentModel->getzencartOrderData($realOrderId);
				    			$trackingNumber = $response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds->TrackingNumber;
				    			$shipmentModel->zencartUpdateOrderStatus($zencartOrderData['customers_name'],$zencartOrderData['customers_email_address'],$realOrderId,$trackingNumber);
				    		}else{
				    			//echo Mage::helper('inventorymanager')->__("This order has been already shipped");
				    			Mage::getSingleton('core/session')->addError(Mage::helper('inventorymanager')->__("This order has been already shipped"));
				    			$this->_redirect('*/*/');
								return;
				    		}
				    	}		
				        if($response->CompletedShipmentDetail->CompletedPackageDetails->Label->Type == "OUTBOUND_LABEL")
				    			{
				    				$fp = fopen($shippingLabel, 'wb');
				    				fwrite($fp,$response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image);
				        			fclose($fp);
				        		}
				        	
				    }else{
					        $fedexApi->printError($client, $response);
					    }
						Mage::log($client,null, "fedex.log");    // Write to log file
				} catch (SoapFault $exception) {
						
					    $fedexApi->printFault($exception, $client);
					   echo Mage::helper('inventorymanager')->__("Something went wrong. Please try again with right information");
				}
			}
			
			if($serialCount == 0){
				echo Mage::helper('inventorymanager')->__("No valid serials found");
			}
		}else{

			$fedexApi = Mage::getResourceModel('inventorymanager/api_fedex');
			$senderAddress = array();
			$senderAddress['Contact']['ContactId'] = "fright1";
			$senderAddress['Contact']['PersonName'] = $data['contact_name'];
			$senderAddress['Contact']['Title'] = $data['contact_name'];
			$senderAddress['Contact']['CompanyName'] = $data['company'];
			$senderAddress['Contact']['PhoneNumber'] = $data['phone'];
			$senderAddress['Contact']['email'] = $data['email'];

			$senderAddress['Address']['StreetLines'][0] = $data['address'];
			$senderAddress['Address']['StreetLines'][1] = "-";
			$senderAddress['Address']['City'] = $data['city'];
			$senderAddress['Address']['StateOrProvinceCode'] = $data['state'];
			$senderAddress['Address']['CountryCode'] = $data['country_id'];
			$senderAddress['Address']['PostalCode'] = $data['postalcode'];
			
			$receiverAddress = array();
			$receiverAddress['Contact']['PersonName'] = $data['receiver']['contact_name'];
			$receiverAddress['Contact']['CompanyName'] = $data['receiver']['company'];
			$receiverAddress['Contact']['PhoneNumber'] = $data['receiver']['phone'];
			
			$receiverAddress['Address']['StreetLines'][0] = $data['receiver']['address'];
			$receiverAddress['Address']['StreetLines'][1] = "";
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
			$totalWeight = 0;
			$serialCount = 0;
			foreach ($data['serial_key'] as $key => $serialKey){
				$serialObject = Mage::getModel('inventorymanager/label')->load($serialKey, "serial");
				$serialCount++;
				$shippingLabel = Mage::getBaseDir().'/media/fedex/shippinglabels/'.$serialKey.'-ShippingLabel.png';
				$bol = Mage::getBaseDir().'/media/fedex/billoflanding/'.$serialKey.'-BillOfLading.pdf';			
				$productName = "proline item";
				$productPrice = 0;
				if($serialObject && $serialObject->getId()){				
					$serialId = $serialObject->getId();
					$shippingLabel = Mage::getBaseDir().'/media/fedex/shippinglabels/'.$serialId.'-ShippingLabel.png';
					$bol = Mage::getBaseDir().'/media/fedex/billoflanding/'.$serialId.'-BillOfLading.pdf';
					$orderProduct = Mage::getModel('inventorymanager/product')->load($serialObject->getProductId());
					$catalogproduct = Mage::getModel('catalog/product')->load($orderProduct->getMainProductId());
					$purchaseorder = Mage::getModel('inventorymanager/purchaseorder')->load($serialObject->getOrderId());
					$productInfoCollection = Mage::getModel('inventorymanager/vendor_productinfo')->getCollection();
					$productInfoCollection->addFieldToFilter('vendor_id', $purchaseorder->getVendorId());
					$productInfoCollection->addFieldToFilter('product_id', $orderProduct->getMainProductId());
					if($productInfoCollection && $productInfoCollection->count() > 0){
						$productInfoObject = $productInfoCollection->getFirstItem();
						if($productInfoObject && $productInfoObject->getId()){
							$productPrice = $catalogproduct->getFinalPrice();
							$productName = $catalogproduct->getName();
							$length	= $productInfoObject->getLength();
							$width = $productInfoObject->getWidth();
							$height = $productInfoObject->getHeight();
							$weight	= $productInfoObject->getWeight();
							$boxLength = $productInfoObject->getBoxLength();
							$boxWidth = $productInfoObject->getBoxWidth();
							$boxHeight = $productInfoObject->getBoxHeight();
							$boxWeight = $productInfoObject->getBoxWeight();
						}
					}
					if($catalogproduct->getIsInStock() == 1){
						$stock_item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($catalogproduct->getId());
						if (!$stock_item->getId()) {
							$productQty = $stock_item->getQty();
							$productQty -= 1;
						    $stock_item->setData('product_id', $catalogproduct->getId());
						    //$stock_item->setData('stock_id', 1); 
						    $isInStock = 1;
						    if($productQty < 1){
						    	$isInStock = 0;
						    }
						    $stock_item->setData('is_in_stock', $isInStock);
						    $stock_item->setData('qty', $productQty);
						    $stock_item->save();
						}				
					}
				}
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
				if(isset($data['price'][$key]) && $data['price'][$key] > 0){
					$productPrice = $data['price'][$key];
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
				$request['RequestedShipment'] = array(
					'ShipTimestamp' => date('c'),
					'DropoffType' => 'REGULAR_PICKUP', 
					'ServiceType' => isset($data['service_type'])?$data['service_type']:'FEDEX_FREIGHT_ECONOMY', 
					'PackagingType' => 'YOUR_PACKAGING', 
					//'Shipper' => $fedexApi->getProperty('freightbilling'),
					'Shipper' => $senderAddress,
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
							'Amount' => $productPrice
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
							'PurchaseOrderNumber' => $data['po'],
							'Description' => $productName,
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
				try {
					if($fedexApi->setEndpoint('changeEndpoint')){
						$newLocation = $client->__setLocation(setEndpoint('endpoint'));
					}
					$response = $client->processShipment($request); // FedEx web service invocation  
					$historyObject = Mage::getModel('inventorymanager/shipmanager');
					$historyItem = Mage::getModel('inventorymanager/shipmanager_item');
					$historySender = Mage::getModel('inventorymanager/shipmanager_sender');
					$historyReceiver = Mage::getModel('inventorymanager/shipmanager_receiver');
					$quotedVal = 0;
					if(isset($response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->FreightRateDetail->BaseCharges->ExtendedAmount->Amount)){
						$quotedVal = $response->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->FreightRateDetail->BaseCharges->ExtendedAmount->Amount;
					}
					$historyData = array(
						'transaction_detail'	=>	isset($response->TransactionDetail->CustomerTransactionId)?$response->TransactionDetail->CustomerTransactionId:"",
						'job_id'				=>	isset($response->JobId)?$response->JobId:"",
						'tracking_number'		=>	isset($response->CompletedShipmentDetail->CarrierCode)?$response->CompletedShipmentDetail->CarrierCode:"",
						'careercode' 			=>	isset($response->CompletedShipmentDetail->MasterTrackingId->TrackingNumber)?$response->CompletedShipmentDetail->MasterTrackingId->TrackingNumber:"",
						'service_type'			=>	isset($response->CompletedShipmentDetail->MasterTrackingId->TrackingIdType)?$response->CompletedShipmentDetail->MasterTrackingId->TrackingIdType:"",
						'weight'				=>	$weight,
						'quoted_value'			=>	$quotedVal,
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
						//'address2'			=>	$data['address2'],
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
					//	'address2'			=>	$data['receiver']['address2'],
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
						$shipmentModel = Mage::getModel("inventorymanager/shipmanager_shipment");
				    	if($data["order_from"] !=2){ 
							$shipmentModel->completeShipment($realOrderId,$response->CompletedShipmentDetail->MasterTrackingId->TrackingNumber,$response->CompletedShipmentDetail->CarrierCode,$shipmentCarrierTitle='');
				    	}else{
				    		$zencartShipmentStatus = $shipmentModel->getzencartOrderShippedStatus($realOrderId);
				    		//$zencartShipmentStatus = 0;
				    		if($zencartShipmentStatus == 0){
				    			/*echo $realOrderId;
				    			echo "<pre>";
				    			print_r($response->CompletedShipmentDetail->MasterTrackingId->TrackingNumber);
				    			exit;*/
				    			$zencartOrderData = $shipmentModel->getzencartOrderData($realOrderId);
				    			$trackingNumber = $response->CompletedShipmentDetail->MasterTrackingId->TrackingNumber;
				    			$shipmentModel->zencartUpdateOrderStatus($zencartOrderData['customers_name'],$zencartOrderData['customers_email_address'],$realOrderId,$trackingNumber);
				    		}else{
				    			//echo Mage::helper('inventorymanager')->__("This order has been already shipped");
				    			Mage::getSingleton('core/session')->addError(Mage::helper('inventorymanager')->__("This order has been already shipped"));
				    			$this->_redirect('*/*/');
								return;
				    		}
				    	}		
				    	$shippingDocuments = $response->CompletedShipmentDetail->ShipmentDocuments;
				    	foreach($shippingDocuments as $key => $value){
				    		$type = $value->Type;
				    		if($type == "OUTBOUND_LABEL"){
				    			$bolImage =$value->Parts->Image;
				    			
				    			$fp = fopen($bol, 'wb');
				    			fwrite($fp, $bolImage);
				        		fclose($fp);
				    		}else if($type == "FREIGHT_ADDRESS_LABEL"){
				    			$addressLabel = $value->Parts->Image;
				    			$fp1 = fopen($shippingLabel, 'wb');   
				        		fwrite($fp1, $addressLabel);
				        		fclose($fp1);
				    		}
				    	} 	
				    }else{
				        $fedexApi->printError($client, $response);
				    }
					Mage::log($client,null, "fedex.log");    // Write to log file
				} catch (SoapFault $exception) {					
				    $fedexApi->printFault($exception, $client);
				   echo Mage::helper('inventorymanager')->__("Something went wrong. Please try again with right information");
				}
			}		
			if($serialCount == 0){
				echo Mage::helper('inventorymanager')->__("No valid serials found");
			}
		}
		$this->loadLayout();
        $this->renderLayout();
	}
		
	public function historyAction(){
		$this->loadLayout();
		$this->renderLayout();
	}
	
	public function waitingshipmentAction(){
		$this->loadLayout();
		$this->renderLayout();
	}
	
	public function downloadAction(){

			if($this->getRequest()->getParam('area') == "bol"){			
			header("Content-Type: application/octet-stream");					
			$area = $this->getRequest()->getParam('area');
			$fileName = $this->getRequest()->getParam('filename');
			$fileN = $fileName;
			if($area == "bol"){
				$fileName = 'billoflanding/'. $fileName;
			}else{
				$fileName = 'shippinglabels/'. $fileName;
			}
				
			$file = Mage::getBaseDir().'/media/fedex/' . $fileName;
			//$file = str_replace(".pdf",".png",$file);

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
			
			}else{
			$area = $this->getRequest()->getParam('area');
			$fileName1 = $fileName = $this->getRequest()->getParam('filename');
			$fileN = $fileName;
			
					$fileName = 'shippinglabels/'. $fileName;
					$file = Mage::getBaseDir().'/media/fedex/' . $fileName;
					//$fileurl = "http://dev.prolinestores.com/media/fedex/shippinglabels/11231-ShippingLabel.png";
					$this->_saveaspdf($file);

			}	
	}
	
	public function settingAction(){
		$this->loadLayout();
		$this->renderLayout();
	}
	
	public function saveSettingsAction(){
		$data = $this->getRequest()->getParams();
		$address = json_encode($data['address']);
		//print_r($address); exit;
		
		$shipmanagerConfig = Mage::getModel('inventorymanager/shipmanager_config');
		
		try {
		$shipmanagerConfig->saveConfig('inventorymanager/fedex_config/shipaccount', $data['ship_account']);
		$shipmanagerConfig->saveConfig('inventorymanager/fedex_config/freightaccount', $data['fright_account']);
		$shipmanagerConfig->saveConfig('inventorymanager/fedex_config/meter_number', $data['meter_number']);
		$shipmanagerConfig->saveConfig('inventorymanager/fedex_config/key', $data['key']);
		$shipmanagerConfig->saveConfig('inventorymanager/fedex_config/password', $data['password']);
		$shipmanagerConfig->saveConfig('inventorymanager/fedex_config/shipper_address', $address);
		Mage::app()->getCacheInstance()->cleanType('config');
		$this->_redirect('inventorymanager/adminuser_shipmanager/setting');
		}catch (Exception $e){
			echo $e->getMessage();
			exit;
		}
	}
	
	protected function _saveaspdf($img){
		$pdf = new Tcpdf(PDF_PAGE_ORIENTATION, PDF_UNIT,array(114.3,165.1), true, 'UTF-8', false);
		//$id = $this->getRequest()->getParam('id');
		
		
		$pdf->SetCreator(PDF_CREATOR);
		//$pdf->SetAuthor('Inventory Manager');
		//$pdf->SetTitle('Inventory Manager');
		//$pdf->SetSubject('');
		//$pdf->SetKeywords('TCPDF, PDF, example, test, guide');
		// set header and footer fonts
		//$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		//$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		//$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		//$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		//$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		//$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		$pdf->SetMargins(0,10,0,0);
		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    			require_once(dirname(__FILE__).'/lang/eng.php');
    			$pdf->setLanguageArray($l);
		}

		// -------------------------------------------------------------------
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetAutoPageBreak(TRUE,-10);
		// add a page
		$orientation='P';
		$format='MAKE-P';
		$pdf->AddPage($orientation, ['format' => $format, 'Rotate' => -180]);

		/* Re-code */
			$url = $img;
			$src = imagecreatefrompng($url);
			/*$rotate = imagerotate($src,180, 0);
			file_put_contents($url,$rotate);
			*/
			$src_wide = imagesx($src);
			$src_high = imagesy($src);
			$clear = array('red'=>255,'green'=>255,'blue'=>255);

			// new image dimensions with right padding
			$dst_wide = $src_wide;
			$dst_high = $src_high;

			// New resource image at new size
			$dst = imagecreatetruecolor($dst_wide, $dst_high);

			// fill the image with the white padding color
			$clear = imagecolorallocate( $dst, $clear["red"], $clear["green"], $clear["blue"]);
			imagefill($dst, 0,0, $clear);

			// copy the original image on top of the new one
			imagecopymerge($dst,$src,0,0,0,0,$src_wide,$src_high, 100);

			// store the new image in tmp directory
			//$pth = '/Applications/MAMP/htdocs/prolinehoods/media/fedex/shippinglabels/checker.png';
			imagejpeg($dst,$url,100);

			// free resources
			imagedestroy($src);
			imagedestroy($dst); 
		/* Re-code  */

		//file_put_contents("myNEWimage.jpg",$rotate);
		//echo $pdffile  = str_replace(".png",".pdf",$img);exit;
		$pdf->Image($img, '','',0,0, '', '', '', false, 300,1, false, false,false, false, false, false);
		$pdf->Output();
		//$pdf->Output();
			
	}
	public function findorderAction(){


		$orderId = $this->getRequest()->getParam('order_id');
		$from = $this->getRequest()->getParam('from');
		
		if($from == "2"){
			$resource	= Mage::getSingleton('core/resource');
			$conn 		= $resource->getConnection('oscomm_read');
			$results 	= $conn->query('SELECT * FROM orders WHERE orders_id = ' . $orderId);
			// 2012965
			$row = $results->fetch();
			//echo "<pre>";
			//print_r($row);
			//	exit;	
			$countryId = '';
			$countryCollection = Mage::getModel('directory/country')->getCollection();
			foreach ($countryCollection as $country) {
			    if ($row['delivery_country'] == $country->getName()) {
			        $countryId = $country->getCountryId();
			        break;
			    }
			}
			
			$regionCode = "";
			if($countryId != ""){
				$regionCollection = Mage::getModel('directory/region_api')->items($countryId);
	    		foreach ($regionCollection as $regionObject){
	    			//print_r($regionObject); exit;
	    			if($regionObject['name'] == $row['delivery_state']){
	    				$regionCode = $regionObject['region_id'];
	    			}
	    		}
			}				
			$data = array();
			$data['name'] = $row['customers_name']!=""?$row['customers_name']:"";
			$data['email'] = $row['customers_email_address']!=""?$row['customers_email_address']:"";
			$data['phone'] = $row['customers_telephone']!=""?$row['customers_telephone']:"";
			$data['address'] = $row['delivery_street_address']!=""?$row['delivery_street_address']:"";
			$data['city'] = $row['delivery_city']!=""?$row['delivery_city']:"";
			$data['zipcode'] = $row['delivery_postcode']!=""?$row['delivery_postcode']:"";
			$data['country'] = $countryId;
			$data['region'] = $row['delivery_state']!=""?$row['delivery_state']:"";
			$data['region_id'] = $regionCode;
			echo Mage::helper('core')->jsonEncode($data);
			exit;		
		}
		
		$orderObject =  Mage::getModel('sales/order')->loadByIncrementId($orderId);
		
		if($orderObject && $orderObject->getId()){
			$address = $orderObject->getShippingAddress();
			$data = array();
			$data['name'] = $address->getName();
			$data['email'] = $orderObject->getCustomerEmail();
			$data['phone'] = $address->getTelephone();
			$data['address'] = $address->getStreet();
			$data['city'] = $address->getCity();
			$data['zipcode'] = $address->getPostcode();
			$data['country'] = $address->getCountryId();
			$data['region'] = $address->getRegion();
			$data['region_id'] = $address->getRegionId();
			echo Mage::helper('core')->jsonEncode($data);
		}
	}
	

	protected function _regioncode($receiver_state_id){
		$region = Mage::getModel('directory/region')->load($receiver_state_id);
		return $regionCode = $region->getCode();
	}

	public function rateAction(){
		
		$data = $this->getRequest()->getParams();
		/*echo "<pre>";
		print_r($data);
		exit;
		*/
		$region = Mage::getModel('directory/region')->load($data['receiver_state_id']);
		$regionCode = $region->getCode();
		$fedexApi = Mage::getResourceModel('inventorymanager/api_fedex');
		$realOrderId = $data['order_id'];
		$orderObject = Mage::getModel('sales/order')->load($realOrderId, "increment_id");
		$request = array();
		$request['WebAuthenticationDetail'] = array(
			'UserCredential'	=>	array(
				'Key'	=>	$fedexApi->getProperty('key'),
				'Password'	=>	$fedexApi->getProperty('password')
			)
		);
		$request['ClientDetail'] = array(
			'AccountNumber'	=>	$fedexApi->getProperty('shipaccount'),
			'MeterNumber'	=>	$fedexApi->getProperty('meter')
		);

		if($data['service_type'] == 'FEDEX_FREIGHT_ECONOMY' OR $data['service_type'] == 'FEDEX_FREIGHT_PRIORITY'){
			$request['TransactionDetail']	= array('CustomerTransactionId'	=>	'FRIGHT_RATE');
		}else{
			$request['TransactionDetail']	= array('CustomerTransactionId'	=>	'GROUND');
		}
		$request['Version'] = array(
			'ServiceId'	=>	'crs',
			'Major'	=>	'18',
			'Intermediate'	=>	'0',
			'Minor'	=>	'0'
		);
		$shipmentRequest = array();
		$shipmentRequest['ShipTimestamp']		=	date('c');
		$shipmentRequest['DropoffType']			=	'REGULAR_PICKUP';
		$shipmentRequest['PackagingType']		=	'YOUR_PACKAGING';
		$shipmentRequest['PreferredCurrency']	=	'USD';
		$shipmentRequest['ServiceTypes']	=	$data['service_type'];
		$shipmentRequest['Shipper']	=	array(
			'Contact'	=>	array(
				'CompanyName'	=>	$data['contact_name'],
				'PhoneNumber'	=>	$data['phone']
			),
			'Address'	=>	array(
				'StreetLines'	=>	$data['address'],
				'StreetLines'	=>	'Do Not Delete - Test Account',
				'City'	=>	$data['city'],
				'StateOrProvinceCode'	=>	$data['state'],
				'PostalCode'	=>	$data['postalcode'],
				'CountryCode'	=>	$data['country_id']
			)
		);
		$shipmentRequest['Recipient'] = array(
			'Contact'	=>	array(
				'PersonName'	=>	$data['receiver']['contact_name'],
				'PhoneNumber'	=>	$data['receiver']['phone']
			),
			'Address'	=>	array(
				'StreetLines'	=>	$data['receiver']['address'],
				'City'	=>	$data['receiver']['city'],
				'StateOrProvinceCode'	=>	$regionCode,
				'PostalCode'	=>	$data['receiver']['postalcode'],
				'CountryCode'	=>	$data['receiver']['country_id']
			)
		);
		$shipmentRequest['ShippingChargesPayment']	=	array(
			'PaymentType'	=>	'SENDER',
			'Payor'	=> array(
				'ResponsibleParty'	=>	array(
					'AccountNumber'	=>	$fedexApi->getProperty('freightaccount')
				)
			)
		);
		/*$shipmentRequest['SpecialServicesRequested']	= array(
			'SpecialServiceTypes'	=>	'EXTREME_LENGTH'
		);*/
		
		echo "<table class='rate-response-table'>";
		$totalCharge = 0;
		foreach ($data['serial_key'] as $key => $serialKey){
			$weight = 0;
			$length = 0;
			$width = 0;
			$height = 0;
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
			
			if(isset($data['price'][$key]) && $data['price'][$key] > 0){
				$productPrice = $data['price'][$key];
			} 
			if($data['service_type'] == 'FEDEX_FREIGHT_ECONOMY' OR $data['service_type'] == 'FEDEX_FREIGHT_PRIORITY'){
			$shipmentRequest['FreightShipmentDetail'] = array(
				'FedExFreightAccountNumber'	=>	$fedexApi->getProperty('freightaccount'),
				'FedExFreightBillingContactAndAddress'	=>	array(
					'Address'	=>	array(
						'StreetLines'	=>	$data['address'],
						'StreetLines'	=>	'Do Not Delete - Test Account',
						'City'	=>	$data['city'],
						'StateOrProvinceCode'	=>	$data['state'],
						'PostalCode'	=>	$data['postalcode'],
						'CountryCode'	=>	$data['country_id']
					)
				),
				'Role'	=>	'SHIPPER',
				'CollectTermsType'	=>	'STANDARD',
				'Coupons'	=>	'',
				'ClientDiscountPercent'	=>	'0',
				'PalletWeight'	=>	array(
					'Units'	=>	'LB',
					'Value'	=>	'10.0'
				),
				'ShipmentDimensions'	=>	array(
					'Length'	=>	$length,
					'Height'	=>	$height,
					'Width'	=>	$width,
					'Units'	=>	'IN'
				),
				'Comment'	=>	'ESBD2600 (FXF - QA-B) - PRODUCTION - 2011-02-01T12:47:00-06:00',
				'LineItems'	=>	array(
					'FreightClass'	=>	'CLASS_050',
					'Packaging'	=>	'BAG',
					'Description'	=>	'LineItemsDescription',
					'Weight'	=>	array(
						'Units'	=>	'LB',
						'Value'	=>	$weight
					),
					'Dimensions'	=>	array(
						'Length'	=>	$length,
						'Width'		=>	$width,
						'Height'	=>	$height,
						'Units'	=>	'IN'
					),
					'Volume' =>	array(
						'Units'	=>	'CUBIC_FT',
						'Value'	=>	(($length * $width * $height) / 12)
					)
				)
			);
			}
			$shipmentRequest['RateRequestTypes']	=	'LIST';
			$shipmentRequest['PackageCount']	=	'1';
			if($data['service_type'] != 'FEDEX_FREIGHT_ECONOMY' AND $data['service_type'] != 'FEDEX_FREIGHT_PRIORITY'){
				$shipmentRequest['RequestedPackageLineItems'] = array(
							'SequenceNumber' => 1,
							'GroupNumber' => 1,
							'GroupPackageCount' => 1,
							'Weight' => array(
								'Units' => 'LB',
								'Value' => $weight
							),
							'Dimensions' => array(
								'Length' =>	$length,
								'Width'	 =>	$width,
								'Height' =>	$height,
								'Units'  => 'IN'
							),
							'SpecialServicesRequested' => array(
								'SpecialServiceTypes' => 'APPOINTMENT_DELIVERY'
							),
							'ContentRecords' => array(
								'PartNumber' => "0",
								'ItemNumber' => $data['order_id'],
								'ReceivedQuantity' => "1",
								'Description' => "Purchase Order"
							)

				);
			}
			$request['RequestedShipment']	= $shipmentRequest;
			$path_to_wsdl = Mage::helper('inventorymanager')->wsdlPath() . "RateService_v18.wsdl";
			$client = new SoapClient($path_to_wsdl, array('trace' => 1));
			
			try {
				if($fedexApi->setEndpoint('changeEndpoint')){
					$newLocation = $client->__setLocation(setEndpoint('endpoint'));
				}
				
				$response = $client->getRates($request);
			  	if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){  	
			    	$rateReply = $response -> RateReplyDetails;
			    	
			    	
			    	foreach ($rateReply as $rate){
			    		$type = $rate->ServiceType;
			    		$shipmentDetails = $rate->RatedShipmentDetails;
			    		$netAmount = 0;
			    		foreach ($shipmentDetails as $detail){
			    			$rateDetail = $detail->ShipmentRateDetail;
			    			$netCharges = $rateDetail->TotalNetCharge->Amount;
			    		}
			    		if($type == $data['service_type'] OR $type == 'FIRST_OVERNIGHT'){
				    		$totalCharge += $netCharges;			    		
			    		}
			    	}
			    }else{
			        echo $this->__("Something went wrong. Please try again.");
			    } 
			   // $fedexApi->writeToLog($client);    // Write to log file   
			}catch (SoapFault $exception) {
				echo $this->__("Something went wrong. Please try again.");
				//$fedexApi->printFault($exception, $client);        
			}
		
		}
		echo "<tr><td>".$this->__("Calculated Price")."</td></tr>";
		echo "<tr><td>$".$totalCharge .  "</td></tr>";
		echo "</table>";
	}
}
