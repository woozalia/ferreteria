<?php
/*
  PURPOSE: Node Leaf index/dispatcher classes
    This stores both the type and the name of each Leaf, and defines the Leaf ID.
    It loads the appropriate Leaf class for handling Leaf Values.
  HISTORY:
    2017-07-28 created for search functions (e.g. node(s) with leafs of certain values)
    2017-09-25 doing some class/file renaming
*/
trait ftTableAccess_LeafIndex {
    protected function LeafsIndexClass() {
	return KS_CLASS_TF_LEAF_INDEX;
    }
    protected function LeafIndexTable() {
	return $this->GetConnection()->MakeTableWrapper($this->LeafsIndexClass());
    }
}
class fctLeafsIndex extends fcTable_keyed_single_standard {
    //use ftTableAccess_NodeLogic;
    
    // ++ SETUP ++ //

    protected function SingularName() {
	return 'fcrLeafIndex';
    }
    protected function TableName() {
	return 'tf_leaf';
    }

    // -- SETUP -- //
    // ++ TABLES ++ //
 
 /* 2017-10-02 Wait... is this ever what I actually want to look up?
    protected function ValueTable_forType($sType) {
	$sClass = $this->Registry()->GetClass($sType);
	return $this->GetConnection()->MakeTableWrapper($sClass);
    } */
    protected function ValueTable_forFieldName($sName) {
	return fcMapLeafNames::GetTable($sName);
    }
    
    // -- TABLES -- //
    // ++ DATA READ ++ //

    /*----
      RETURNS: Recordset of node + leaf data for all nodes of the given type
      TODO: 2017-09-12 This can't work here -- FigureSourceSQL() is only defined in the Node table class
    */
    public function SelectRecords_forType($sType) {
        $sqlNode = $this->NodeIndexTable()->TableName_Cooked();
	//$sqlLeaf = $this->TableName_Cooked();
	$sqlLeaf = $this->FigureSourceSQL();
	$sqlType = $this->GetConnection()->SanitizeValue($sType);
	
	$sql = "SELECT * FROM $sqlLeaf AS l LEFT JOIN $sqlNode AS n ON l.ID_Node=n.ID WHERE n.Type=$sqlType";
	
	echo '<b>SQL</b>: '.$sql;
	throw new exception('2017-08-07 Working here.');
    }

    // ACTION: Find leaf records where all of the following name-value pairs match
    public function SelectRecords_forLeafValues(array $arPairs) {
	$sqlThis = $this->TableName_Cooked();
	$nCount = 0;
	foreach ($arPairs as $key => $val) {
	    $sqlJoinThis = "$sqlThis AS node$nCount";
	    if ($nCount == 0) {
		$sqlJoin = $sqlJoinThis;
	    } else {
		$sqlJoin = "($sqlJoin) LEFT JOIN $sqlJoinThis";
	    }
	    // 2017-07-28 WORKING HERE
	    die('SQL = '.$sqlJoin);
	    $nCount++;
	}
    }
    // RETURNS: Leaf Index record (loaded), or NULL if none found
    protected function SelectRecord_forNode_andName($idNode,$sName) {
	$sqlName = $this->GetConnection()->SanitizeValue($sName);
	$sql = "(ID_Node=$idNode) AND (Name=$sqlName)";
	$rc = $this->SelectRecords($sql);
	if ($rc->HasRows()) {
	    $rc->NextRow();	// load the only row
	} else {
	    $rc = NULL;		// something went wrong
	}
	return $rc;
    }
    protected function SelectRecords_forNode($idNode) {
	$sql = "ID_Node=$idNode";
	$rs = $this->SelectRecords($sql);
	return $rs;
    }
    public function GetValues_forNode($idNode,array $arVals=array()) {
	$rs = $this->SelectRecords_forNode($idNode);
	while ($rs->NextRow()) {
	    $v = $rs->LoadValue();
	    $arVals[$rs->GetNameString()] = $v;
	}
	return $arVals;
    }
    
    // -- DATA READ -- //
    // ++ DATA WRITE ++ //
    
    /*----
      ACTION: creates or updates Leaf Index record and Leaf Value record
      RETURNS: nothing
      HISTORY:
	2017-10-02 modified so it creates Value record as well as Index, and now returns nothing
	2017-10-19 insert and update working
    */
    public function MakeLeaf_fromNode_andName($idNode,$sName,$sValue) {
	$db = $this->GetConnection();
	$rcIndex = $this->SelectRecord_forNode_andName($idNode,$sName);

	// create or update the Index record:
	
	$tValue = $this->ValueTable_forFieldName($sName);
	$sType = $tValue->GetTypeString();
	$sqlType = $db->SanitizeValue($sType);
	if (is_null($rcIndex)) {
	    // Leaf record not found, so need to create it
	
	    $sAction = "CREATING LEAF INDEX for node [$idNode] leaf [$sName]<br>";
	    $sqlName = $db->SanitizeValue($sName);
	    $ar = array(
	      'ID_Node'	=> $idNode,
	      'Name'	=> $sqlName,
	      'Type'	=> $sqlType,
	      );
	    $idLeaf = $this->Insert($ar);
	    //$rcIndex = $this->GetRecord_forKey($idLeaf);
	} else {
	    // existing Leaf record found, so updating it
	
	    $sAction = "UPDATING LEAF INDEX for node [$idNode] leaf [$sName]<br>";
	    $ar = array(
	      'Type'	=> $sqlType,
	      );
	    $rcIndex->Update($ar);
	    $idLeaf = $rcIndex->GetLeafID();
	}
	if (empty($idLeaf)) {
	    echo $sAction.'<br>';
	    if ($db->IsOkay()) {
		echo 'No database error.';
	    } else {
		echo '<b>DB Error</b>: '.$db->ErrorString().'<br>'
		  .'<b>SQL</b>: '.$db->sql
		  ;
	    }
	    throw new exception('TextFerret internal error: no ID for Leaf');
	}
	
	// create or update the Value record:
	$tValue->SaveValueRecord($idLeaf,$sValue);
	if (!$db->IsOkay()) {
	    echo '<b>DB Error</b>: '.$db->ErrorString().'<br>'
	      .'<b>SQL</b>: '.$db->sql.'<br>'
	      ."<b>Leaf ID</b>: [$idLeaf]"
	      ;
	    throw new exception('TextFerret internal error: Leaf Value could not be saved.');
	}
    }
    
    // -- DATA WRITE -- //
}
class fcrLeafIndex extends fcRecord_standard {

    // ++ FIELDS ++ //

    public function SetLeafID($id) {
	$this->SetFieldValue('ID',$id);
    }
    public function GetLeafID() {
	return $this->GetFieldValue('ID');
    }
    public function GetNameString() {
    	return $this->GetFieldValue('Name');
    }
    protected function GetTypeString() {
	return $this->GetFieldValue('Type');
    }
    
    // NOTE: "Value" is a pseudofield for this class; it's a real field in the Leaf Value record class
    
    private $v;
    // ACTION: look up the Value for the current Leaf Index, store it locally, and return it
    public function LoadValue() {
	$t = fcMapLeafTypes::GetTable($this->GetTypeString());
	$rc = $t->GetRecord_forKey($this->GetLeafID());
	$v = $rc->GetValue();
	$this->SetValue($v);
	return $v;
    }
    public function SetValue($v) {
	$this->v = $v;
    }
    protected function GetValue() {
	return $this->v;
    }

    // -- FIELDS -- //
}