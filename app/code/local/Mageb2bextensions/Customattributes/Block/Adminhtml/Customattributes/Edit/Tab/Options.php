<?php
/**
 * Options.php
 * MageB2BExtensions @ InterSEC Solutions LLC.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageb2bextensions.com/LICENSE-M1.txt
 *
 * @package    Mageb2bextensions_Customattributes
 * @copyright  Copyright (c) 2003-2009 MageB2BExtensions @ InterSEC Solutions LLC. (http://www.mageb2bextensions.com)
 * @license    http://www.mageb2bextensions.com/LICENSE-M1.txt
 */

class Mageb2bextensions_Customattributes_Block_Adminhtml_Customattributes_Edit_Tab_Options extends Mage_Adminhtml_Block_Widget
{

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('customattributes/options.phtml');
    }

    protected function _prepareLayout()
    {
        $this->setChild('delete_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('catalog')->__('Delete'),
                    'class' => 'delete delete-option'
                )));

        $this->setChild('add_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('catalog')->__('Add Option'),
                    'class' => 'add',
                    'id'    => 'add_new_option_button'
                )));
        return parent::_prepareLayout();
    }

    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_button');
    }

    public function getAddNewButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }

    public function getStores()
    {
        $stores = $this->getData('stores');
        if (is_null($stores)) {
            $stores = Mage::getModel('core/store')
                ->getResourceCollection()
                ->setLoadDefault(true)
                ->load();
            $this->setData('stores', $stores);
        }
        return $stores;
    }

    public function getOptionValues()
    {
        $attributeType = $this->getAttributeObject()->getFrontendInput();
        $defaultValues = $this->getAttributeObject()->getDefaultValue();
        if ($attributeType == 'select' || $attributeType == 'multiselect' || $attributeType == 'checkbox' || $attributeType == 'radio') {
            $defaultValues = explode(',', $defaultValues);
        } else {
            $defaultValues = array();
        }

        switch ($attributeType) {
            case 'select':
            case 'radio':
                $inputType = 'radio';
                break;
            case 'multiselect':
            case 'checkbox':
                $inputType = 'checkbox';
                break;
            default:
                $inputType = '';
                break;
        }

        $values = $this->getData('option_values');
        
        if (is_null($values)) {
            $values = array();
            $optionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                ->setAttributeFilter($this->getAttributeObject()->getId())
                ->setPositionOrder('desc', true)
                ->load();

            foreach ($optionCollection as $option) {
                $value = array();
                if (in_array($option->getId(), $defaultValues)) {
                    $value['checked'] = 'checked="checked"';
                } else {
                    $value['checked'] = '';
                }

                $value['intype'] = $inputType;
                $value['id'] = $option->getId();
                $value['sort_order'] = $option->getSortOrder();
                foreach ($this->getStores() as $store) {
                    $storeValues = $this->getStoreOptionValues($store->getId());
                    if (isset($storeValues[$option->getId()])) {
                        $value['store'.$store->getId()] = htmlspecialchars($storeValues[$option->getId()]);
                    }
                    else {
                        $value['store'.$store->getId()] = '';
                    }
                }
                $values[] = new Varien_Object($value);
            }
            $this->setData('option_values', $values);
        }

        return $values;
    }

    public function getLabelValues()
    {
        $values = array();
        
        #$frontendLabel = false;
        $frontendLabel = $this->getAttributeObject()->getFrontend()->getLabel();
        
        if ($frontendLabel)
        {
            if (is_array($frontendLabel)) {
                $values = $frontendLabel;
                $frontendLabel = array_shift($frontendLabel);
            }
            else 
            {
                $values[0] = $frontendLabel;
            }
        }
        else 
        {
            $values[0] = '';
        }
        
        $translations = Mage::getModel('core/translate_string')
           ->load(Mage_Catalog_Model_Entity_Attribute::MODULE_NAME.Mage_Core_Model_Translate::SCOPE_SEPARATOR.$frontendLabel)
           ->getStoreTranslations();
        foreach ($this->getStores() as $store) {
            if ($store->getId() != 0 AND !isset($values[$store->getId()])) {
                $values[$store->getId()] = isset($translations[$store->getId()]) ? $translations[$store->getId()] : '';
            }
        }
        
        return $values;
    }

    public function getDescValues()
    {
        $values = array();
        
		$translations = Mage::getModel('customattributes/customattributes')->getAttributeDescription($this->getAttributeObject()->getId());
        
        foreach ($this->getStores() as $store) {
            if ($store->getId() != 0 AND !isset($values[$store->getId()])) {
                $values[$store->getId()] = isset($translations[$store->getId()]) ? $translations[$store->getId()] : '';
            }
        }
        
        return $values;
    }

    public function getNeedSelectValues()
    {
        $values = array();
        
		$translations = Mage::getModel('customattributes/customattributes')->getAttributeNeedSelect($this->getAttributeObject()->getId());
        
        foreach ($this->getStores() as $store) {
            if ($store->getId() != 0 AND !isset($values[$store->getId()])) {
                $values[$store->getId()] = isset($translations[$store->getId()]) ? $translations[$store->getId()] : '';
            }
        }
        
        return $values;
    }

    public function getStoreOptionValues($storeId)
    {
        $values = $this->getData('store_option_values_'.$storeId);
        if (is_null($values)) {
            $values = array();
            $valuesCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                ->setAttributeFilter($this->getAttributeObject()->getId())
                ->setStoreFilter($storeId, false)
                ->load();
            foreach ($valuesCollection as $item) {
                $values[$item->getId()] = $item->getValue();
            }
            $this->setData('store_option_values_'.$storeId, $values);
        }
        return $values;
    }

    public function getAttributeObject()
    {
        return Mage::registry('customattributes_data');
    }

}
