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
    // ACTION: Retrieve the value for a given name
    protected function Value($sName) {
	if (!is_string($sName) && !is_integer($sName)) {
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
    /*----
      HISTORY:
	2018-05-07 Was forcing $val to (string) inside is_string(), but then it returns TRUE for blanks.
	  Removed "(string)".
	  Might need to just test for is_null() or !='' so integers are ok.
    */
    public function GetString($sName,$vDefault=NULL) {
	$val = $this->Value($sName);
	if (is_string($val)) {
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
    /*----
      HISTORY:
	2018-04-29 This was just returning form data raw, which (when it comes to checkbox input)
	  isn't what the caller is expecting -- so now we're converting it. If there's a situation
	  where this *shouldn't* be happening, that needs to be documented.
	  
	  Note that we're still only returning checked boxes; unchecked boxes are ignored.
    */
    public function GetArray($sName,$vDefault=array()) {
	$vRaw = $this->Value($sName);
	if (is_array($vRaw)) {
	    $ar = array();
	    foreach ($vRaw as $key => $val) {
		$ar[$key] = ($val == 'on');
	    }
	    return $ar;
	} else {
	    return $vDefault;
	}
    }
    public function GetDateTime($sName) : DateTime {
	$val = $this->Value($sName);
	return new DateTime($val);
    }
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
