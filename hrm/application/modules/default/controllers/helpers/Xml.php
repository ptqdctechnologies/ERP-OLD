<?php
class Zend_Controller_Action_Helper_Xml extends Zend_Controller_Action_Helper_Abstract
{
    function  __construct() {

    }   

    function getXML($obj)
    {
    	$doc = new DOMDocument();
		$doc->formatOutput = true;
		$root_element = $doc->createElement("records");
		$doc->appendChild($root_element);

    	foreach($obj as $var => $value) {
        	$statusElement = $doc->createElement("record");
			if (!is_array($value)) {
				$statusElement->appendChild($doc->createTextNode($value));
				$root_element->appendChild($statusElement);
			} else {
				$this->_xmlHelper(&$doc, &$root_element, &$statusElement, &$value);
			}
		}
		return $doc->saveXML();
    }
	function _xmlHelper(&$doc, &$root_element, &$statusElement, &$value) {
		if (is_array($value)) {
			foreach ($value as $key => $val) {
				if (is_array($val)) {
					$this->_xmlHelper(&$doc, &$root_element, &$statusElement, $val);
				} else {
					$se = $doc->createElement($key);
					$se->appendChild($doc->createTextNode($val));
					$statusElement->appendChild($se);
                    $root_element->appendChild($statusElement);
				}
			}
			//print_r($value);
		} else {
			$statusElement->appendChild($doc->createTextNode($value));
			$root_element->appendChild($statusElement);
		}
	}
}
?>