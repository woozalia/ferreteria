<?php
/*
  PURPOSE: Database dispenser class (database object factory)
  PART OF: db* database library
  HISTORY:
    2015-03-13 created
*/

// register classes for connection types
fcDBOFactory::RegisterType('mysql','fcDataConn_MySQL');

class fcDBOFactory {

    /*----
      ACTION: create a fully-initialized database Connection object from a spec string
      RULES: all db Connection specs start with "<type>:", which determines which class to use
	After that, details are up to the Connection.
    */
    static public function GetConn($sSpec,$bAllowFail) {
	$arSplit = fcString::SplitFirst($sSpec,':');
	$sType = $arSplit[KS_BEFORE];
	$sSubSpec = $arSplit[KS_AFTER];
	if (self::TypeExists($sType)) {
	    $sClass = self::GetTypeClass($sType,$bAllowFail);
	    $oConn = new $sClass;		// make the Engine object
	    $oConn->Setup_spec($sSubSpec);
	    return $oConn;
	} else {
	    if ($bAllowFail) {
		return NULL;
	    } else {
		throw new exception("Ferreteria config error: Connection type [$sType] is not registered.");
	    }
	}
    }
    
    // ++ CONNECTION TYPES ++ //

    static $arTypes;
    static public function RegisterType($sType,$sClassName) {
	self::$arTypes[$sType] = $sClassName;
    }
    static protected function TypeExists($sType) {
	return fcArray::Exists(self::$arTypes,$sType);
    }
    static protected function GetTypeClass($sType,$bAllowFail) {
	if (self::TypeExists($sType)) {
	    $sClass = self::$arTypes[$sType];
	    return $sClass;
	} else {
	    $sErrSfx = 'so cannot get a wrapper for protocol "'.$sType.'".';
	    if (is_array(self::$arTypes)) {
		if (count(self::$arTypes) > 0) {
		    // unknown db type
		    return NULL;
		} else {
		    throw new exception("Ferreteria config error: no database types have been defined, $sErrSfx");
		}
	    } else {
		throw new exception("Ferreteria config error: Database type error has not been initialized, $sErrSfx");
	    }
	}
    }
    
    // -- CONNECTION TYPES -- //

}