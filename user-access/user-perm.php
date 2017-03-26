<?php
/*
  PURPOSE: user access control classes: available security permissions
  HISTORY:
    2013-12-29 started
    2017-01-04 This will need some updating to work with Ferreteria revisions.
*/
class fctUserPerms extends fcTable_keyed_single_standard {

    // ++ SETUP ++ //
    
    protected function TableName() {
	return KS_TABLE_USER_PERMISSION;
    }
    protected function SingularName() {
	return KS_CLASS_USER_PERMISSION;
    }

    // -- SETUP -- //
    // ++ STATUS ++ //

    private $arNeeded = NULL;
    public function SetNeededPermit($sName) {
	fcArray::NzSum($this->arNeeded,$sName);
	echo fcArray::Render($this->arNeeded);
	echo "PERMISSION NEEDED: [$sName]<br>";
	//throw new exception('Need to figure out what event gets us here.');
    }
    protected function GetNeededPermits() {
	return $this->arNeeded;
    }
    protected function HasNeededPermits() {
	echo 'PERMITS NEEDED: '.count($this->arNeeded).'<br>';
	throw new exception('RENDER PHASE');
	
	return !is_null($this->arNeeded);
    }

    // -- STATUS -- //
    // ++ READ DATA ++ //
    
    // ASSUMES: $sName does not contain any characters that need escaping
    public function PermitExists($sName) {
	$sqlFilt = "Name='$sName'";
	$rc = $this->SelectRecords($sqlFilt);
	return $rc->HasRows();
    }

}
class fcrUserPermit extends fcRecord_standard {

    // ++ FIELD ACCESS ++ //

    public function Name() {
	return $this->Value('Name');
    }
    public function Descr() {
	return $this->Value('Descr');
    }

    // -- FIELD ACCESS -- //
}