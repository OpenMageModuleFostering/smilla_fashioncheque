<?php
class Smilla_Fashioncheque_Block_Info extends Mage_Payment_Block_Info {
	
	protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('fashioncheque/paymentinfo.phtml');
    }


	public function toPdf()
    {
        $this->setTemplate('fashioncheque/paymentinfo_pdf.phtml');
        return $this->toHtml();
    }
}
?>