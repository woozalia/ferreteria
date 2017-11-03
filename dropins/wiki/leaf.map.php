<?php
/*
  PURPOSE: class for managing Leaf registration
  HISTORY:
    2017-10-01 created to make it easier to separate out the registry functions
      Eventually should maybe descend from a fcLinearRegistry class
*/
// PURPOSE: maps Leaf Value Types to Leaf Value Tables
class fcMapLeafTypes {
    static private $ar = array();
    static public function Register($sType,fctLeafValues $tValues) {
	self::$ar[$sType] = $tValues;
    }
    static protected function Exists($sType) {
	return array_key_exists($sType,self::$ar);
    }
    static public function GetTable($sType) {
	if (self::Exists($sType)) {
	    return self::$ar[$sType];
	} else {
	    if (count(self::$ar) > 0) {
		echo 'LEAF TYPES REGISTERED:';
		foreach (self::$ar as $s) {
		    echo ' '.$s;
		}
	    } else {
		echo 'NO leaf types have been registered.';
	    }
	    throw new exception('Attempt to access unregistered table type "'.$sType.'".');
	}
    }
}
// PURPOSE: maps Leaf Names to Leaf Types and Leaf Tables
class fcMapLeafNames {
    static private $ar = array();
    static public function Register($sName,$sClass) {
	if (!self::Exists($sName)) {
	    $t = fcApp::Me()->GetDatabase()->MakeTableWrapper($sClass);
	    $sType = $t->GetTypeString();
	    self::$ar[$sName] = $sType;
	    fcMapLeafTypes::Register($sType,$t);
	}
    }
    static protected function Exists($sName) {
	return array_key_exists($sName,self::$ar);
    }
    /*
    static public function GetClass($sName) {
	return self::$arClasses[$sName];
    }*/
    static protected function GetTypeCode($sName) {
	return self::$ar[$sName];
    }
    static public function GetTable($sName) {
	$sType = self::GetTypeCode($sName);
	return fcMapLeafTypes::GetTable($sType);
    }
}