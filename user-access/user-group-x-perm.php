<?php
/*
  PURPOSE: handles assignments of security groups to permissions
  HISTORY:
    2014-01-02 started
    2017-01-27 rewriting to work with Ferreteria changes
      While we might eventually want to be able to pull up groups from a permission,
	we don't need it now. The best way to do this seems to be to have two types
	of table -- one for each direction of lookup:
	  fctUGroups_for_UPermit
	  fctUPermits_for_UGroup
	Given that all we currently need is "lookup permissions from group", I am
	  renaming the table class from fctUGroup_x_UPerm to fctUPermits_for_UGroup
	  and adding a corresponding recordset type.
*/
class fctUPermits_for_UGroup extends fcTable_wSource {
    use ftName_forTable;
    use ftWriteableTable;	// Insert()

    // ++ SETUP ++ //
/*
    public function __construct($iDB) {
	parent::__construct($iDB);
	  $this->Name('ugroup_x_uperm');
	  $this->ClassName_Perms(KS_CLASS_USER_PERMISSIONS);
	  $this->ClassName_Perms(KS_CLASS_USER_PERMISSION);
    }*/
    protected function TableName() {
	return 'ugroup_x_uperm';
    }

    // -- SETUP -- //
    // ++ CLASSES ++ //

    /*
    // TODO: why can't this just return a constant?
    private $sClsPerms;
    public function ClassName_Perms($sClass=NULL) {
	if (!is_null($sClass)) {
	    $this->sClsPerms = $sClass;
	}
	return $this->sClsPerms;
    }
    // TODO: why can't this just return a constant?
    private $sClsPerm;
    public function ClassName_Perm($sClass=NULL) {
	if (!is_null($sClass)) {
	    $this->sClsPerm = $sClass;
	}
	return $this->sClsPerm;
    }
    */
    protected function PermitsClass() {
	return KS_CLASS_USER_PERMISSIONS;
    }

    // -- CLASSES -- //
    // ++ TABLES ++ //

    protected function PermitTable() {
	return $this->GetConnection()->MakeTableWrapper($this->PermitsClass());
    }

    // -- TABLES -- //
    // ++ RECORDS ++ //

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
	  .' ON up.ID=axp.ID_Permit'
	  ." WHERE axp.ID_Group=$idUGroup";
	$rs = $this->PermitTable()->FetchRecords($sql);
	//$tbl = $this->PermTable();
	//$rs->Table($tbl);
	return $rs;
    }

    // -- RECORDS -- //
    // ++ ACTIONS ++ //

    /*----
      INPUT:
	$idGroup : group to which we are assigning permissions
	arPerms[id] = (any value) : user should be assigned to group 'id'
    */
    public function SetUPerms($idGroup, array $arPerms) {
	$db = $this->GetConnection();
	$db->TransactionOpen();
	// first, delete any existing assignments:
	$sql = 'DELETE FROM '.$this->TableName_Cooked().' WHERE ID_UGrp='.$idGroup;
	$ok = $db->ExecuteAction($sql);

	// next, add any specified by the form:
	foreach ($arPerms as $idPerm => $on) {
	    $this->Insert(array(
	      'ID_Group'=>$idGroup,
	      'ID_Permit'=>$idPerm
	      )
	    );
	    //$sql = $this->sql;
	    //echo "SQL: $sql<br>";
	}
	$db->TransactionSave();
    }

    // -- ACTIONS -- //
}
/* actually, not necessary
class fcrUPermits_for_UGroup extends fcDataRecord {

}*/