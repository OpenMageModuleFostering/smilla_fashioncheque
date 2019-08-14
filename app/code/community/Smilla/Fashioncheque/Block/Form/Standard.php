<?php
class Smilla_Fashioncheque_Block_Form_Standard extends Mage_Payment_Block_Form {

	protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('fashioncheque/form/standard.phtml');
    }
}
?>