<?php
/**
 * 	Angell EYE PayPal PayFlow Class
 *	An open source PHP library written to easily work with PayPal's API's
 *
 *  Copyright � 2014  Andrew K. Angell
 *	Email:  andrew@angelleye.com
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * @package			Angell_EYE_PayPal_PayFlow_Class_Library
 * @author			Andrew K. Angell
 * @copyright       Copyright � 2014 Angell EYE, LLC
 * @link			https://github.com/angelleye/PayPal-PHP-Library
 * @website			http://www.angelleye.com
 * @since			Version 1.52
 * @updated			01.14.2014
 * @filesource
 */

class Angelleye_PayPal_PayFlow extends Angelleye_PayPal_WC
{	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	array	config preferences
	 * @return	void
	 */
	function __construct($DataArray)
	{
		$DataArray = apply_filters( 'angelleye_paypal_payflow_construct_params', $DataArray );

		parent::__construct($DataArray);
		
		$this->APIVendor = isset($DataArray['APIVendor']) ? $DataArray['APIVendor'] : '';
		$this->APIPartner = isset($DataArray['APIPartner']) ? $DataArray['APIPartner'] : '';
		$this->Verbosity = isset($DataArray['Verbosity']) ? $DataArray['Verbosity'] : 'HIGH';
                $this->Force_tls_one_point_two = isset($DataArray['Force_tls_one_point_two']) ? $DataArray['Force_tls_one_point_two'] : 'no';
		
		if($this->Sandbox)
		{
			$this->APIEndPoint = apply_filters('aepfw_payments_pro_payflow_endpoint_sandbox', 'https://pilot-payflowpro.paypal.com');
		}
		else
		{
			$this->APIEndPoint = apply_filters('aepfw_payments_pro_payflow_endpoint', 'https://payflowpro.paypal.com');
		}
		
		$this->NVPCredentials = 'BUTTONSOURCE['.strlen($this->APIButtonSource).']='.$this->APIButtonSource.'&VERBOSITY['.strlen($this->Verbosity).']='.$this->Verbosity.'&USER['.strlen($this->APIUsername).']='.$this->APIUsername.'&VENDOR['.strlen($this->APIVendor).']='.$this->APIVendor.'&PARTNER['.strlen($this->APIPartner).']='.$this->APIPartner.'&PWD['.strlen($this->APIPassword).']='.$this->APIPassword;
		$this->NVPCredentials_masked = 'BUTTONSOURCE['.strlen($this->APIButtonSource).']='.$this->APIButtonSource.'&VERBOSITY['.strlen($this->Verbosity).']='.$this->Verbosity.'&USER['.strlen($this->APIUsername).']=*****&VENDOR['.strlen($this->APIVendor).']=*****&PARTNER['.strlen($this->APIPartner).']='.$this->APIPartner.'&PWD['.strlen($this->APIPassword).']='.'*****';

		$this->TransactionStateCodes = array(
				'1' => 'Error',
				'6' => 'Settlement Pending',
				'7' => 'Settlement in Progress',
				'8' => 'Settlement Completed Successfully',
				'11' => 'Settlement Failed',
				'14' => 'Settlement Incomplete'
		);
	}	
	
	/*
	 * GetTransactionStateCodeMessage
	 * 
	 * @access public
	 * @param number
	 * @return string
	 */
	function GetTransactionStateCodeMessage($Code)
	{
		return $this -> TransactionStateCodes[$Code];
	}
	
	/*
	 * CURLRequest
	 * 
	 * @access public
	 * @param string Request
	 * @return string
	 */
	function CURLRequest($Request = "", $APIName = "", $APIOperation = "", $PrintHeaders = false)
	{
	
		$unique_id = date('YmdGis').rand(1000,9999);
	
		$headers[] = "Content-Type: text/namevalue"; //or text/xml if using XMLPay.
		$headers[] = "Content-Length : " . strlen ($Request);  // Length of data to be passed
		$headers[] = "X-VPS-Timeout: 45";
		$headers[] = "X-VPS-Request-ID:" . $unique_id;
	
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 90);
		curl_setopt($curl, CURLOPT_URL, $this->APIEndPoint);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $Request);
                if( isset($this->Force_tls_one_point_two) && $this->Force_tls_one_point_two == 'yes') {
                    curl_setopt($curl, CURLOPT_SSLVERSION, 6);
                }
	
		// Try to submit the transaction up to 3 times with 5 second delay.  This can be used
		// in case of network issues.  The idea here is since you are posting via HTTPS there
		// could be general network issues, so try a few times before you tell customer there
		// is an issue.
                
                $Response = curl_exec($curl);
                if($Response === false) {
                    return array( 'CURL_ERROR' =>curl_error($curl) );
                } else {
                    $i=1;
                    while ($i++ <= 3) {
                        if($i != 2) {
                            $Response = curl_exec($curl);
                        }
                        $headers = curl_getinfo($curl);
                        if ($headers['http_code'] != 200) {
                            sleep(5);  // Let's wait 5 seconds to see if its a temporary network issue.
                        } else if ($headers['http_code'] == 200) {
                            // we got a good response, drop out of loop.
                            break;
                        }
                    }
                }
                curl_close($curl);
                
		return $Response;
	}
	
	
	/**
	 * Convert an NVP string to an array with URL decoded values
	 *
	 * @access	public
	 * @param	string	NVP string
	 * @return	array
	 */
	function NVPToArray($NVPString)
	{
		$proArray = array();
		parse_str($NVPString,$proArray);
		return $proArray;
	}
	
	/*
	 * ProcessTransaction
	 * 
	 * @access public
	 * @param array request parameters
	 * @return array
	 */
	function ProcessTransaction($DataArray)
	{
		$NVPRequest = $this->NVPCredentials;
		$NVPRequestmask = $this->NVPCredentials_masked;
		$star = '*****';
		
		foreach($DataArray as $DataArrayVar => $DataArrayVal)
		{
			if($DataArrayVal != '')
			{
				$NVPRequest .= '&'.strtoupper($DataArrayVar).'['.strlen($DataArrayVal).']='.$DataArrayVal;
                                if(strtoupper($DataArrayVar) == 'ACCT' || strtoupper($DataArrayVar) == 'EXPDATE' || strtoupper($DataArrayVar) == 'CVV2') {
                                    $NVPRequestmask .= '&'.strtoupper($DataArrayVar).'['.strlen($DataArrayVal).']='.'****';
                                } else {
                                    $NVPRequestmask .= '&'.strtoupper($DataArrayVar).'['.strlen($DataArrayVal).']='.$DataArrayVal;
                                } 
				
			}
		}
		
		$NVPResponse = $this->CURLRequest($NVPRequest);
                if( isset( $NVPResponse ) && is_array( $NVPResponse ) && !empty( $NVPResponse['CURL_ERROR'] ) ){
                    return $NVPResponse;
                }
		$NVPResponse = strstr($NVPResponse,"RESULT");
		$NVPRequestMod =$NVPRequest.'&merchant_id='.$this->APIVendor;
        do_action('angelleye_paypal_response_data', $NVPResponse, $NVPRequestMod, '1', $this->Sandbox, true, 'paypal_payflow');
                
		$NVPResponseArray = $this->NVPToArray($NVPResponse);

		$NVPResponseArray['RAWREQUEST'] = $NVPRequestmask;
		$NVPResponseArray['RAWRESPONSE'] = $NVPResponse;
		
		return $NVPResponseArray;
	}
}