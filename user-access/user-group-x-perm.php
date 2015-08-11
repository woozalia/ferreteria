<?php
/*
  PURPOSE: handles assignments of security groups to permissions
  HISTORY:
    2014-01-02 started
*/
class clsUGroup_x_UPerm extends clsTable_abstract {
    private $sClsPerms,$sClsPerm;

    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->Name('ugroup_x_uperm');
	  $this->ClassName_Perms(KS_CLASS_USER_PERMISSIONS);
	  $this->ClassName_Perms(KS_CLASS_USER_PERMISSION);
    }

    // ++ CLASS NAMES ++ //

    public function ClassName_Perms($sClass=NULL) {
	if (!is_null($sClass)) {
	    $this->sClsPerms = $sClass;
	}
	return $this->sClsPerms;
    }
    public function ClassName_Perm($sClass=NULL) {
	if (!is_null($sClass)) {
	    $this->sClsPerm = $sClass;
	}
	return $this->sClsPerm;
    }

    // -- CLASS NAMES -- //
    // ++ DATA TABLE ACCESS ++ //

    protected function PermTable() {
	return $this->Engine()->Make($this->ClassName_Perms());
    }

    // -- DATA TABLE ACCESS -- //
    // ++ DATA RECORD ACCESS ++ //

    /*----
      RETURN: Records for all permissions assigned to the given group
    */
    public function UPermRecords($idUGroup) {
	if (empty($idUGroup)) {
	    throw new exception('Trying to get permissions without specifying group.');
	}
	$sql = 'SELECT up.*'
	  .' FROM '.KS_TABLE_UGROUP_X_UPERM.' AS axp'
	  .' LEFT JOIN '.KS_TABLE_USER_PERMISSION.' AS up'
	  .' ON up.ID=axp.ID_UPrm'
	  ." WHERE axp.ID_UGrp=$idUGroup";
	$rs = $this->DataSQL($sql,$this->ClassName_Perm());
	$tbl = $this->PermTable();
	$rs->Table($tbl);
	return $rs;
    }

    // -- DATA RECORD ACCESS -- //
    // ++ ACTIONS ++ //

    /*----
      INPUT:
	$idGroup : group to which we are assigning permissions
	arPerms[id] = (any value) : user should be assigned to group 'id'
    */
    public function SetUPerms($idGroup, array $arPerms) {
	$this->Engine()->TransactionOpen();
	// first, delete any existing assignments:
	$sql = 'DELETE FROM '.$this->NameSQL().' WHERE ID_UGrp='.$idGroup;
	$ok = $this->Engine()->Exec($sql);

	// next, add any specified by the form:
	foreach ($arPerms as $idPerm => $on) {
	    $this->Insert(array(
	      'ID_UGrp'=>$idGroup,
	      'ID_UPrm'=>$idPerm
	      )
	    );
//	    $sql = $this->sqlExec;
//	    echo "SQL: $sql<br>";
	}

	$this->Engine()->TransactionSave();
    }

    // -- ACTIONS -- //
}