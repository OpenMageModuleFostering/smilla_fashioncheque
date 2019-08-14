<?php
class Smilla_Fashioncheque_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	protected $_code = 'fashioncheque';
	protected $_formBlockType = 'fashioncheque/form_standard';
    protected $_infoBlockType = 'fashioncheque/info';
	protected $_canAuthorize = false;
	protected $_canCapture = true;
    protected $_canFetchTransactionInfo = true;
    protected $_canRefund = true;
    protected $_canCancel = true;
    protected $_canUseInternal = true;
	
	public function capture(Varien_Object $payment, $amount)
    {
        Mage::log('Start Capturing: '. $amount, 7);

        $api = Mage::helper('fashioncheque/api');
        
        // Capture API Call
        try{
            $transactionId = $api->withdraw($payment->getCardNo(), $payment->getVerificationcode(), $amount, $payment->getOrder()->getIncrementId());
            $payment->setTransactionId($transactionId);
            $payment->setIsTransactionClosed(0);
            //$payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,array('key1'=>'value1','key2'=>'value2'));
        }catch(Exception $e){
            // Capture failed
            $errorCode = substr($e->getMessage(), 0, 4);
            switch($errorCode){
                // Card Number not Found
                case '0150':
                    $message = 'Verification Code invalid.';
                    break;
                default:
                    $message = substr($e->getMessage(), 6);
            }
            Mage::throwException($this->_getHelper()->__($message));
        }

        return $this;
    }

	public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();
        $info->setCardNo($data->getCardNo())
        ->setVerificationcode($data->getVerificationcode());
        return $this;
    }

	/**
	 * Check entered Card Number / Verfication Code
	 */
    public function validate()
    {
        parent::validate();

        $paymentInfo = $this->getInfoInstance();
 		$api = Mage::helper('fashioncheque/api');

        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            $grandTotal = $paymentInfo->getOrder()->getGrandTotal();
        } else {
            $grandTotal = $paymentInfo->getQuote()->getGrandTotal();
        }

        try{
            //$checkResult = $api->check($paymentInfo->getCardNo(), $paymentInfo->getVerificationcode(), $grandTotal);
            $cardInfo = $api->cardInfo($paymentInfo->getCardNo());
        } catch(Exception $e){
            // Output API Error (Excluding Code)
            Mage::throwException($this->_getHelper()->__(substr($e->getMessage(), 6)));
        }

        // Check Credit
        if($cardInfo->Card->Credit < ($grandTotal * 100.00)){
            Mage::throwException($this->_getHelper()->__('The fashioncheque card has not enough Credit (Current Balance: %s %s)', $cardInfo->Card->Credit / 100,  $cardInfo->Card->Currency));
        }

        return $this;
    }

    public function cancel(Varien_Object $payment) {
        Mage::Log('Order cancel', 7);

        return $this;
    }

    // Refund Order
    public function refund(Varien_Object $payment, $amount) {
        Mage::Log('Ordeer refund', 7);
        Mage::Log('Amount:'.$amount, 7);
        Mage::Log('Refund Transaction:'.$payment->getRefundTransactionId(), 7);

        $api = Mage::helper('fashioncheque/api');

        // Refund API Call
        try{
            $api->refund($payment->getCardNo(), $payment->getVerificationcode(), $amount, $payment->getOrder()->getIncrementId(), $payment->getRefundTransactionId());
        }catch(Exception $e){
            // Refund failed
            Mage::throwException($this->_getHelper()->__($e->getMessage()));
        }
        return $this;
    }



}
?>