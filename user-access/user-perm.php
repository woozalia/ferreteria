<?php
/*
  PURPOSE: user access control classes: available security permissions
  HISTORY:
    2013-12-29 started
    2017-01-04 This will need some updating to work with Ferreteria revisions.
    2017-03-30 Working; making '*' permission into a constant
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

    /*----
      NOTE 2017-03-28: I originally made this a dynamic variable, but the value was being lost
	somehow between fctUserPerms and fctAdminUserPermits. Probably what is happening is
	that MakeTableWrapper() sees that fctAdminUserPermits is a different class, so creates
	a different instance. It should probably *upgrade the old instance* instead.
	
	The problem lies in identifying where a descendant class already has an instantiated ancestor.
	
	We might want to do something like moving GetActionKey() up in the hierarchy of creatable "wrapper classes"
	and using it as the identifier -- but that's for a later revision.
    */
    static private $arMissing;
    public function SetMissingPermit($sName) {
	if ($sName != KS_PERM_FE_ROOT) {
	    fcArray::NzSum(self::$arMissing,$sName);
	}
    }
    protected function GetMissingPermits() {
	return self::$arMissing;
    }
    protected function PermitsAreMissing() {
	return !is_null(self::$arMissing);
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