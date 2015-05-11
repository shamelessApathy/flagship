<?php 
require_once(Mage::getBaseDir().'/tcpdf/tcpdf.php');
class Ecommerceguys_Inventorymanager_Adminhtml_PurchaseorderController extends Mage_Adminhtml_Controller_action
{
	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('inventorymanager/purchaseorder')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Purchase Order'), Mage::helper('adminhtml')->__('Purchase Order'));
		
		return $this;
	}
	
	public function indexAction(){
		$this->_initAction();
		$this->renderLayout();
	}
	
	public function newAction() {
		$this->_forward('orderedit');
	}
	
	public function editAction() {
		$this->_forward('orderedit');
//		$id     = $this->getRequest()->getParam('id');
//		$model  = Mage::getModel('inventorymanager/purchaseorder')->load($id);
//
//		if ($model->getId() || $id == 0) {
//			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
//			if (!empty($data)) {
//				$model->setData($data);
//			}
//
//			Mage::register('purchaseorder_data', $model);
//
//			$this->loadLayout();
//			$this->_setActiveMenu('inventorymanager/purchaseorder');
//
//			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
//			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));
//
//			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
//
//			$editBlock = $this->getLayout()->createBlock('inventorymanager/adminhtml_purchaseorder_edit');
//			/*$scriptBlock = $this->getLayout()->createBlock('inventorymanager/adminhtml_purchaseorder_purchaseorder')
//				->setTemplate("inventorymanager/purchaseorder.phtml");
//			$editBlock->append($scriptBlock);*/
//			
//			$this->_addContent($editBlock)
//				->_addLeft($this->getLayout()->createBlock('inventorymanager/adminhtml_purchaseorder_edit_tabs'));
//
//			$this->renderLayout();
//		} else {
//			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('inventorymanager')->__('Item does not exist'));
//			$this->_redirect('*/*/');
//		}
	}
	
	public function ordereditAction(){
		$this->loadLayout();
		$this->renderLayout();
	}
	
	public function saveAction() {
		if ($data = $this->getRequest()->getPost()) {
			//print_r($data); exit;
			
			$model = Mage::getModel('inventorymanager/purchaseorder');		
			$model->setData($data)
				->setId($this->getRequest()->getParam('id'));
			try {
				$model->save();
				$productData['po_id'] = $model->getId();
				$tatalQty = 0;
				foreach ($data['qty'] as $productId => $qty){
					$tatalQty+=$qty;
					$productData['qty'] = $qty;
					$productData['main_product_id'] = $productId;
					$productData['price'] = $data['product_value'][$productId];
					$productData['total'] = $productData['qty'] * $productData['price'];
					$orderProduct = Mage::getModel('inventorymanager/product');
					$orderProduct->setData($productData)->save();
				}
				$model->setOrderQty($tatalQty)->save();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('inventorymanager')->__('Order was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);

				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('id' => $model->getId()));
					return;
				}
				$this->_redirect('*/*/');
				return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('inventorymanager')->__('Unable to find order to save'));
        $this->_redirect('*/*/');
	}
	
	public function gridAction(){
		//$this->loadLayout();
       // $this->renderLayout();
       
       /*
       $this->loadLayout()
            ->getLayout()
            ->getBlock('inventorymanager/adminhtml_purchaseorder_edit_tab_product')
            ->setSelectedProducts($this->getRequest()->getPost('products', null));
 
        $this->renderLayout();*/
        $productGridBlock = $this->getLayout()->createBlock('inventorymanager/adminhtml_purchaseorder_edit_tab_product', 'category.product.grid');
        $productGridBlock->setSelectedProducts($this->getRequest()->getPost('products', null));
        
        $this->getResponse()->setBody($productGridBlock->toHtml());
        
        
        /*$this->getResponse()->setBody(
            $this->getLayout()->createBlock('inventorymanager/adminhtml_purchaseorder_edit_tab_product', 'category.product.grid')
                ->toHtml()
        );*/
	}
	
	public function findproductAction(){
		$this->loadLayout();
       	$this->renderLayout();
	}
	
	public function getproductinfoAction(){
		$this->loadLayout();
       	$this->renderLayout();
	}
	
	public function deleteAction(){
		
	}
	
	public function massDeleteAction(){
		$purchaseOrderIds = $this->getRequest()->getParam('inventorymanager');
		if(sizeof($purchaseOrderIds) > 0){
			foreach ($purchaseOrderIds as $purchaseOrderId){
				$purchaseorder = Mage::getModel('inventorymanager/purchaseorder')->load($purchaseOrderId);
				if($purchaseorder && $purchaseorder->getId()){
					$purchaseorder->delete();
				}
			}
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('inventorymanager')->__('Selected order(s) deleted'));
		}
		$this->_redirect('*/*/');
	}
	
	public function generatePdfAction(){
		$pdf = new Tcpdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$id = $this->getRequest()->getParam('id');
		$purchaseOrder = Mage::getModel('inventorymanager/purchaseorder')->load($id);
		
		$content = $this->getLayout()->createBlock('inventorymanager/adminhtml_purchaseorder_orderedit')
		->setTemplate('inventorymanager/pdfcontent.phtml')->toHtml();
		
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Inventory Manager');
		$pdf->SetTitle('Inventory Manager');
		$pdf->SetSubject('');
		$pdf->SetKeywords('TCPDF, PDF, example, test, guide');
		
		// set default header data
		$pdf->SetHeaderData('/../skin/frontend/default/theme279/images/prohoods_logo_sm.png', 0, '', '');
		
		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		
		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		
		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		
		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		
		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		
		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
			require_once(dirname(__FILE__).'/lang/eng.php');
			$pdf->setLanguageArray($l);
		}
		
		// ---------------------------------------------------------
		
		// set font
		
		
		// add a page
		$pdf->AddPage();
		$pdf->SetFont('helvetica', '', 8);
		
		$pdf->writeHTML($content, true, false, false, false, '');
		$pdf->lastPage();

		// ---------------------------------------------------------
		
		//Close and output PDF document
		$pdf->Output('inventorymanager.pdf', 'D');
	}
}