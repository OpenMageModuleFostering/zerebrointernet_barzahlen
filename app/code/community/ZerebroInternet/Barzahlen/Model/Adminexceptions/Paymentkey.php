<?php
/**
 * Barzahlen Payment Module for Magento
 *
 * @category    ZerebroInternet
 * @package     ZerebroInternet_Barzahlen
 * @copyright   Copyright (c) 2015 Cash Payment Solutions GmbH (https://www.barzahlen.de)
 * @author      Alexander Diebler
 * @author      Martin Seener
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL-3.0)
 */

class ZerebroInternet_Barzahlen_Model_Adminexceptions_Paymentkey extends Mage_Core_Model_Config_Data
{
    /**
     * Checks the entered value before saving it to the configuration. Setting to old value if string
     * length is lower than 1.
     *
     * @return Mage_Core_Model_Abstract
     */
    public function _beforeSave()
    {
        $paymentkey = $this->getValue();

        if (strlen($paymentkey) < 1) {
            $translateMessage = Mage::helper('barzahlen')->__('bz_adm_paymentkey_exception');
            Mage::getSingleton('adminhtml/session')->addError($translateMessage);
            Mage::helper('barzahlen')->bzLog('adminexceptions/paymentkey: Payment key too small. Setting last one.');
            $this->setValue(($this->getOldValue()));
        }
        return parent::_beforeSave();
    }
}
