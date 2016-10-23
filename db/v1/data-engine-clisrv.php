<?php
/*
  HISTORY:
    2016-07-13 split off from data.php
*/
/*%%%%%
  PURPOSE: clsDataEngine that is specific to client-server databases
    This type will always need host and schema names, username, and password.
*/
abstract class clsDataEngine_CliSrv extends clsDataEngine {
    protected $strType, $strHost, $strUser, $strPass;

    public function InitSpec($iSpec) {
	$ar = preg_split('/@/',$iSpec);
	if (array_key_exists(1,$ar)) {
	    list($part1,$part2) = preg_split('/@/',$iSpec);
	} else {
	    throw new exception('Connection string not formatted right: ['.$iSpec.']');
	}
	list($this->strType,$this->strUser,$this->strPass) = preg_split('/:/',$part1);
	list($this->strHost,$this->strName) = explode('/',$part2);
	$this->strType = strtolower($this->strType);	// make sure it is lowercased, for comparison
	$this->strErr = NULL;
    }
    public function Host() {
	return $this->strHost;
    }
    public function User() {
	return $this->strUser;
    }
}
