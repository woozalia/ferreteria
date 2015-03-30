<?php
/*
  PURPOSE: Database dispenser class
  PART OF: db* database library
  HISTORY:
    2015-03-13 created
*/

class fcDBOFactory {
    static $arTypes;

    /*----
      ACTION: create a fully-initialized database Connection object from a spec string
      RULES: all db Connection specs start with "<type>:", which determines which class to use
	After that, details are up to the Connection.
    */
    static public function GetConn($sSpec) {
	$arSplit = clsString::SplitFirst($sSpec,':');
	$sType = $arSplit(KS_BEFORE);
	$sSubSpec = $arSplit(KS_AFTER);
	if (array_key_exists($sSpec,self::$arTypes)) {
	    $sClass = self::$arTypes[$sType];
	    $oConn = new $sClass;		// make the Engine object
	    $oConn->Setup_spec($sSubSpec);
	    return $oConn;
	} else {
	    // unknown db type
	    return NULL;
	}
    }
    static public function RegisterType($sType,$sClassName) {
	self::$arTypes[$sType] = $sClassName;
    }
}