<?php
/*
  PURPOSE: user access control classes: available security permissions
  HISTORY:
    2013-12-29 started
    2017-01-04 This will need some updating to work with Ferreteria revisions.
    2017-03-25 Finally realized that permissions should be an app-defined array, not a db table. Revising.
*/
class fctUserPerms extends fcDataTable_array {

    // ++ SETUP ++ //
    
    protected function SingularName() {
	return KS_CLASS_USER_PERMISSION;
    }
    public function RegisterPermit($sName,$sMeaning) {
	return $this->InsertValues(array('Name'=>$sName,'Descr'=>$sMeaning));
    }

    // -- SETUP -- //

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