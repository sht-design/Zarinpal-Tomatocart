<?php


/*

P30vel Online Pay ( Bank Mellat )
By Om!d Samadbin in P30vel.ir
Contact me :
	info@P30vel.ir
Other My Service :
	Forum.p30vel.ir
	News.p30vel.ir
	www.iMafia.Org
*/

class nusoap_fault extends nusoap_base {
	/**
	 * The fault code (client|server)
	 * @var string
	 * @access private
	 */
	var $faultcode;
	/**
	 * The fault actor
	 * @var string
	 * @access private
	 */
	var $faultactor;
	/**
	 * The fault string, a description of the fault
	 * @var string
	 * @access private
	 */
	var $faultstring;
	/**
	 * The fault detail, typically a string or array of string
	 * @var mixed
	 * @access private
	 */
	var $faultdetail;

	/**
	* constructor
    *
    * @param string $faultcode (SOAP-ENV:Client | SOAP-ENV:Server)
    * @param string $faultactor only used when msg routed between multiple actors
    * @param string $faultstring human readable error message
    * @param mixed $faultdetail detail, typically a string or array of string
	*/
	function nusoap_fault($faultcode,$faultactor='',$faultstring='',$faultdetail=''){
		parent::nusoap_base();
		$this->faultcode = $faultcode;
		$this->faultactor = $faultactor;
		$this->faultstring = $faultstring;
		$this->faultdetail = $faultdetail;
	}

	/**
	* serialize a fault
	*
	* @return	string	The serialization of the fault instance.
	* @access   public
	*/
	function serialize(){
		$ns_string = '';
		foreach($this->namespaces as $k => $v){
			$ns_string .= "\n  xmlns:$k=\"$v\"";
		}
		$return_msg =
			'<?xml version="1.0" encoding="'.$this->soap_defencoding.'"?>'.
			'<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"'.$ns_string.">\n".
				'<SOAP-ENV:Body>'.
				'<SOAP-ENV:Fault>'.
					$this->serialize_val($this->faultcode, 'faultcode').
					$this->serialize_val($this->faultactor, 'faultactor').
					$this->serialize_val($this->faultstring, 'faultstring').
					$this->serialize_val($this->faultdetail, 'detail').
				'</SOAP-ENV:Fault>'.
				'</SOAP-ENV:Body>'.
			'</SOAP-ENV:Envelope>';
		return $return_msg;
	}
}


/*

P30vel Online Pay ( Bank Mellat )
By Om!d Samadbin in P30vel.ir
Contact me :
	info@P30vel.ir
Other My Service :
	Forum.p30vel.ir
	News.p30vel.ir
	www.iMafia.Org
*/
class soap_fault extends nusoap_fault {
}


?>