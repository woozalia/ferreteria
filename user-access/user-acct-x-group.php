<?php
/*
  PURPOSE: handles assignments of user accounts to security groups
  HISTORY:
    2013-12-19 started
    2017-01-27 rewriting to work with Ferreteria changes
*/
class fctUGroups_for_UAcct extends fcTable_wSource {
    use ftName_forTable;
    use ftWriteableTable;	// Insert()

    // ++ SETUP ++ //
    
    protected function TableName() {
	return KS_TABLE_UACCT_X_UGROUP;
    }

    // -- SETUP -- //
    // ++ CLASSES ++ //
    
    protected function GroupsClass() {
	return KS_CLASS_USER_GROUPS;
    }

    // -- CLASSES -- //
    // ++ TABLES ++ //

    protected function GroupTable() {
	return $this->GetConnection()->MakeTableWrapper($this->GroupsClass());
    }

    // -- TABLES -- //
    // ++ RECORDS ++ //

    /*----
      RETURN: Records for all groups to which the given user belongs
    */
    public function UGroupRecords($idUAcct) {
	$sql = 'SELECT ug.*'
	  .' FROM '.KS_TABLE_UACCT_X_UGROUP.' AS axg'
	  .' LEFT JOIN '.KS_TABLE_USER_GROUP.' AS ug'
	  .' ON ug.ID=axg.ID_Group'
	  .' WHERE ID_Acct='.$idUAcct;
	// if there is a group to which all users automatically belong...
	if (defined('ID_GROUP_USERS')) {
	    // ...include that group too
	    $sql .= ' UNION DISTINCT SELECT ug.*'
	  .' FROM '.KS_TABLE_USER_GROUP.' AS ug'
	  .' WHERE ID='.ID_GROUP_USERS;
	}
	$rs = $this->GroupTable()->FetchRecords($sql);
	//$rs = $this->FetchRecords($sql);
	//$rs->Table($this->GroupTable());
	return $rs;
    }

    // -- RECORDS -- //
    // ++ ACTIONS ++ //

    /*----
      INPUT:
	$idAcct : user to which we are assigning groups
	arGrps[id] = (arbitrary value) : user should be assigned to group 'id'
      HISTORY:
	2018-04-28 updating for current Ferreteria API (has needed it since at least 2017-01-28)
    */
    public function SetUGroups($idAcct, array $arGrps=NULL) {
	$db = $this->GetConnection();
	$db->TransactionOpen();
	// first, delete any existing assignments:
	$sql = 'DELETE FROM '.$this->TableName_Cooked().' WHERE ID_Acct='.$idAcct;
	$this->sql = $sql;
	$ok = $db->ExecuteAction($sql);

	// next, add any specified by the form:
	if (is_null($arGrps)) {
	    $out = "User $idAcct has been removed from all groups.";
	} else {
	    $htG = NULL;
	    foreach ($arGrps as $idGrp => $on) {
		$htG .= ' '.$idGrp;
		$this->Insert(array(
		  'ID_Acct'=>$idAcct,
		  'ID_Group'=>$idGrp
		  )
		);
	    }
	    $out = "User $idAcct has been assigned to ".fcString::Pluralize(count($arGrps),'group'.$htG,'these groups:'.$htG);
	    //die ($out);
	}

	$db->TransactionSave();
	return $out;
    }

    // -- ACTIONS -- //
}
