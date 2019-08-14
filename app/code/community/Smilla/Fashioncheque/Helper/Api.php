<?php
class Smilla_Fashioncheque_Helper_Api extends Mage_Core_Helper_Abstract
{
	private $testApiUrl = 'https://wssec-uat.fashioncheque.nl/v3/giftcard.asmx?wsdl';
	private $prodApiUrl = 'https://wssec.fashioncheque.nl/v3/giftcard.asmx?wsdl';


	/**
	 * Validate Card / Verification Code
	 */
	public function cardInfo($cardNo){
		Mage::log('Validate Card-#: '.$cardNo, 7);
		$parameter = array(
			'CardInfo' => array(
				'RequestID' => $this->getRequestId(),
				'Cardnumber' => $cardNo, 
			)
		);

		$result = $this->apiCall('CardInfo', $parameter);

		if($result->Result == 'Success' && $result->Card->CardStatus == "Active"){
			return $result;
		} else {
			if($result->Result == 'Success'){
				// Exception depends on CardStatus
				switch($result->Card->CardStatus){
					case 'Deactivated':
						$message = 'Card is empty and inactive.';
						break;
					case 'Suspended':
						$message = 'Card is suspended.';
						break;
					case 'Registered':
						$message = 'Card is not active yet.';
						break;
					default:
						$message = 'Card is '.$result->Card->CardStatus;

				}
				throw new Exception('0000: '.$message);
			} else {
				// Return Remark
				throw new Exception($result->Remark);
			}
			
		}

	}


	public function check($cardNo, $verificationcode, $amount){
		Mage::log('CHECK Card-#: '.$cardNo, 7);
		Mage::log('Amount: '.$amount, 7);
 


		$parameter = array(
			'Check' => array(
				'RequestID' => $this->getRequestId(),
				'Cardnumber' => $cardNo, 
				'VerificationCode' => $verificationcode, 
				'Value' => array(
					'Currency' => Mage::app()->getStore()->getCurrentCurrencyCode(),
					'Amount' => $amount * 100
				), 
			)
		);

		$result = $this->apiCall('Check', $parameter);

		if(($result->Result == 'Success' || $result->Result == 'StatusError') && $result->Card->CardStatus == "Active"){
			return $result;
		} else {
			throw new Exception($result->Remark);
		}

	}


	public function withdraw($cardNo, $verificationcode, $amount, $receiptnumber){
		Mage::log('CHECK Card-#: '.$cardNo, 7);
		Mage::log('Amount: '.$amount, 7);


		$requestId = $this->getRequestId();

		$parameter = array(
			'Withdraw' => array(
				'RequestID' => $requestId,
				'Cardnumber' => $cardNo, 
				'VerificationCode' => $verificationcode, 
				'Value' => array(
					'Currency' => Mage::app()->getStore()->getCurrentCurrencyCode(),
					'Amount' => $amount * 100
				), 
				'Receiptnumber' => $receiptnumber
			)
		);

		$result = $this->apiCall('Withdraw', $parameter);

		if($result->Result == 'Success'){
			return $requestId;
		} else {
			throw new Exception($result->Remark);
		}

	}


	public function refund($cardNo, $verificationcode, $amount, $receiptnumber, $originalTransactionID){
		Mage::log('REFUND Card-#: '.$cardNo, 7);
		Mage::log('Amount: '.$amount, 7);


		$requestId = $this->getRequestId();

		$parameter = array(
			'Withdraw' => array(
				'RequestID' => $requestId,
				'Cardnumber' => $cardNo, 
				'VerificationCode' => $verificationcode, 
				'OriginalRequestID' => $originalTransactionID,
				'Value' => array(
					'Currency' => Mage::app()->getStore()->getCurrentCurrencyCode(),
					'Amount' => $amount * 100
				), 
				'Receiptnumber' => $receiptnumber
			)
		);

		$result = $this->apiCall('Refund', $parameter);

		if($result->Result != 'Success'){
			throw new Exception($result->Remark);
		}

		return $this;

	}

	private function getRequestId(){

		return strtoupper(uniqid());
	}


	private function apiCall($method, $parameter){
		try{

			if(Mage::getStoreConfig('payment/fashioncheque/test_mode') == 0){
				// Production API
				$apiUrl = $this->prodApiUrl;
			} else {
				// Test API
				$apiUrl = $this->testApiUrl;
			}
			Mage::log($apiUrl, 7);


			$api = new SoapClient($apiUrl, array(
				'trace' => true, 
			));
		

			// Set Authentication Header
		  	$headerbody = array(
				'PointOfSale' => array(
					'MerchantID'=> Mage::getStoreConfig('payment/fashioncheque/merchant_id')
                )
			); 

			$soapHeader = new SoapHeader('http://pos.fashioncheque.nl/wsdl/v3/giftcard', "Authentication", $headerbody); 
			$api->__setSoapHeaders($soapHeader);

			// Call API
			$result = $api->__soapCall($method, $parameter);

			Mage::log('Request Body: '.(string) $api->__getLastRequest(), 7);
			Mage::log('Result: '.json_encode($result), 7);

			return $result;

		} catch(SoapFault $e){
			throw new Exception('0000: '.$e->getMessage());
		}

	}
}