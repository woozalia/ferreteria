<?php
/*
  PURPOSE: handles assignments of user accounts to security groups
  HISTORY:
    2013-12-19 started
*/
class clsUAcct_x_UGroup extends clsTable_abstract {
    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->Name('user_x_ugroup');
    }

    // ++ CLASS NAMES ++ //

    protected function GroupsClass() {
	return 'clsUserGroups';
    }

    // -- CLASS NAMES -- //
    // ++ DATA TABLE ACCESS ++ //

    protected function GroupTable() {
	return $this->Engine()->Make($this->GroupsClass());
    }

    // -- DATA TABLE ACCESS -- //
    // ++ DATA RECORD ACCESS ++ //

    /*----
      RETURN: Records for all groups to which the given user belongs
    */
    public function UGroupRecords($idUAcct) {
	$sql = 'SELECT ug.*'
	  .' FROM '.KS_TABLE_UACCT_X_UGROUP.' AS axg'
	  .' LEFT JOIN '.KS_TABLE_USER_GROUP.' AS ug'
	  .' ON ug.ID=axg.ID_UGrp'
	  .' WHERE ID_User='.$idUAcct;
	// if there is a group to which all users automatically belong...
	if (defined('ID_GROUP_USERS')) {
	    // ...include that group too
	    $sql .= ' UNION DISTINCT SELECT ug.*'
	  .' FROM '.KS_TABLE_USER_GROUP.' AS ug'
	  .' WHERE ID='.ID_GROUP_USERS;
	}
	$rs = $this->DataSQL($sql,KS_CLASS_ADMIN_USER_GROUP);
	$rs->Table($this->GroupTable());
	return $rs;
    }

    // -- DATA RECORD ACCESS -- //
    // ++ ACTIONS ++ //

    /*----
      INPUT:
	$idAcct : user to which we are assigning groups
	arGrps[id] = (arbitrary value) : user should be assigned to group 'id'
    */
    public function SetUGroups($idAcct, array $arGrps=NULL) {
	$this->Engine()->TransactionOpen();
	// first, delete any existing assignments:
	$sql = 'DELETE FROM '.$this->NameSQL().' WHERE ID_User='.$idAcct;
	$ok = $this->Engine()->Exec($sql);

	// next, add any specified by the form:
	if (is_null($arGrps)) {
	    $out = "User $idAcct has been removed from all groups.";
	} else {
	    $htG = NULL;
	    foreach ($arGrps as $idGrp => $on) {
		$htG .= ' '.$idGrp;
		$this->Insert(array(
		  'ID_User'=>$idAcct,
		  'ID_UGrp'=>$idGrp
		  )
		);
	    }
	    $out = "User $idAcct has been assigned to ".Pluralize(count($arGrps),'group'.$htG,'these groups:'.$htG);
	    //die ($out);
	}

	$this->Engine()->TransactionSave();
	return $out;
    }

    // -- ACTIONS -- //
}