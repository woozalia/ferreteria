<?php
/*
  PURPOSE: classes for retrieving permissions info for user accounts
  HISTORY:
    2017-01-07 adapting from code in user-acct.php
*/

class fcqtUserPerms extends fcTable_wSource_wRecords {

    // ++ SETUP ++ //
    
    protected function SingularName() {
	return 'fcqrUserPerm';
    }

    // -- SETUP -- //
    // ++ RECORDS ++ //

    public function PermissionRecords($idAcct) {
	$sql =
	  'SELECT up.*'
	  .' FROM (('.KS_TABLE_UACCT_X_UGROUP.' AS uxg'
	  .' LEFT JOIN '.KS_TABLE_USER_GROUP.' AS ug'
	  .' ON ug.ID=uxg.ID_Group)'
	  .' LEFT JOIN '.KS_TABLE_UGROUP_X_UPERM.' AS uxp'
	  .' ON uxp.ID_Group=ug.ID)'
	  .' LEFT JOIN '.KS_TABLE_USER_PERMISSION.' AS up'
	  .' ON up.ID=uxp.ID_Permit'
	  ." WHERE ID_Acct=$idAcct";
	// if there is a group to which all users automatically belong...
	if (defined('ID_GROUP_USERS')) {
	    // ...include permissions for that group too
	    $sql .= ' UNION DISTINCT SELECT up.*'
	      .' FROM '.KS_TABLE_UGROUP_X_UPERM.' AS uxp'
	      .' LEFT JOIN '.KS_TABLE_USER_PERMISSION.' AS up'
	      .' ON up.ID=uxp.ID_Permit'
	      .' WHERE uxp.ID_Group='.ID_GROUP_USERS;
	}
	return $this->FetchRecords($sql);
    }

    // -- RECORDS -- //
}
class fcqrUserPerm extends fcDataRecord {

/* not needed after all
    public function GetNameString() {
	return $this->GetFieldValue('Name');
    } */
}