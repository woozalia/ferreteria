<?php
/*
  PURPOSE: base classes for parsing parameter input into an array and querying it
  TODO: Really, this ought to just return a Field object, OSLT, which then could
    convert itself to various formats as needed.
  HISTORY:
    2016-12-05 split off from http.php
    2017-02-15 function GetDateTime()
*/
abstract class fcInputData {
    abstract protected function Values();
    abstract protected function Value($sName);
    abstract public function KeyExists($sName);
    abstract public function GetBool($sName);
    abstract public function GetString($sName,$vDefault=NULL);
    abstract public function GetInt($sName,$vDefault=NULL);
    abstract public function GetFloat($sName,$vDefault=NULL);
    abstract public function GetArray($sName,$vDefault=array());
    abstract public function GetDateTime($sName) : DateTime;
    public function DumpHTML() {
	return '<pre>'.print_r($this->Values(),TRUE).'</pre>';
    }
}
/*----
  IMPLEMENTS everything except Values(). We know we have all the data in an array,
    but we don't yet know how to access it.
*/
abstract class fcInputData_array extends fcInputData {
    protected function Value($sName) {
	if (!is_string($sName) || is_integer($sName)) {
	    throw new exception('Ferreteria usage error: Value() expects a string or integer, but received something else.');
	}
	$ar = $this->Values();
	if (array_key_exists($sName,$ar)) {
	    return $ar[$sName];
	} else {
	    return NULL;
	}
    }
    public function KeyExists($sName) {
	return array_key_exists($sName,$this->Values());
    }
    public function GetBool($sName) {
	return (boolean)$this->Value($sName);
    }
    public function GetText() {
	throw new exception('GetText() is deprecated; call GetString() instead.');
    }
    public function GetString($sName,$vDefault=NULL) {
	$val = $this->Value($sName);
	if (is_string((string)$val)) {
	    return $val;
	} else {
	    return $vDefault;
	}
    }
    public function GetInt($sName,$vDefault=NULL) {
	$val = $this->Value($sName);
	if (is_integer((int)$val)) {
	    return $val;
	} else {
	    return $vDefault;
	}
    }
    public function GetFloat($sName,$vDefault=NULL) {
	$val = $this->Value($sName);
	if (is_numeric($val)) {
	    return $val;
	} else {
	    return $vDefault;
	}
    }
    public function GetArray($sName,$vDefault=array()) {
	$val = $this->Value($sName);
	if (is_array($val)) {
	    return $val;
	} else {
	    return $vDefault;
	}
    }
    public function GetDateTime($sName) : DateTime {
	$val = $this->Value($sName);
	return new DateTime($val);
    }
    /* 2017-02-15 Seems like the wrong approach here.
    // NOTE: 2017-02-15 Not entirely sure these functions belong here as-is. Maybe return a date object?
    public function GetDateTime_asUnix($sName,$ndtBase=NULL,$vDefault=NULL) {
	$ndtBase = is_null($ndtBase)?time():$ndtBase;
	$val = $this->Value($sName);
	$ndtOut = strtotime($val);
	if ($ndtOut === FALSE) {
	    return $vDefault;
	} else {
	    return $ndtOut;
	}
    }
    public function GetDate_asString($sName,$sFormat='Y-m-d',$vDefault=NULL) {
	$val = $this->Value($sName);
	return fcDate::NzDate($val,$vDefault,$sFormat);
    } */
}
// PURPOSE: fcInputData_array whose data is stored internally
class fcInputData_array_local extends fcInputData_array {
    private $ar;	// raw form data
    public function __construct(array $ar) {
	$this->ar = $ar;
    }
    protected function Values() {
	return $this->ar;
    }
}
