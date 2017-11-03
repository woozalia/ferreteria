<?php
/*
  PURPOSE: Base classes for specific Node types (i.e. Nodes with specific collections of Values).
  HISTORY:
    2017-08-06 Split off from node-type.wiki.php
    2017-08-26 "handler" classes moved from node-type.php to node-dispatch.php
*/
/*::::
  PURPOSE: An instantiable Node class-pair which dispatches handling to appropriate Node Types
  REQUIRES:
    fiInsertableTable - so form object can write entered data
  HISTORY:
    2017-08-14 decommissioned fctNodeTypes / fcrNodeType
    2017-08-20 reusing as fctNodesIndex (was fctNodesHandler) / fcrNodeIndex (was fcrNodeHandler)
*/
// 2017-10-05 is this actually needed?
/*
trait ftTableAccess_NodeIndex {
    protected function NodesIndexClass() {
	return KS_CLASS_TF_NODE_INDEX;
    }
      //static $n=0;
    protected function NodeIndexTable() {
      //echo "<br><b>N: ".self::$n.' memory: '.memory_get_usage().'</b><br>';
      //$oTrace = new fcStackTrace();
      //echo $oTrace->RenderAllRows();
      //self::$n++;
	return $this->GetConnection()->MakeTableWrapper($this->NodesIndexClass());
    }
}*/
class fctNodesIndex extends fcDataTable_array implements fiInsertableTable, fiLinkableTable, fiEventAware {
    use ftStoredTable;			// implements Insert()
    use ftLinkableTable;
    use ftExecutableTwig;		// event dispatcher
    use ftSource_forTable;		// implements GetConnection(), FetchRecords() (as shortcuts)
    //use ftSingleKeyedTable;		// requires GetKeyName(), implements GetRecord_forKey()
    //use ftName_forTable;		// implaments FigureSelectSQL()
    //use ftNodeBaseTable;		// implements TableName()
    use ftTableAccess_LeafIndex;	// implements LeafIndexTable()

    // ++ SETUP ++ //

    /*----
      NOTE: There may be a more optimized way to do this, especially as the number of node types increases,
	but for now this should work: just register all the node types by creating their classes.
    */
    protected function InitVars() {
	$db = $this->GetConnection();
	// register all known node types (see note above)
	$t = $db->MakeTableWrapper(KS_CLASS_TF_NODE_PAGE_ADMIN);	// for now, this is the only node type
    }
    protected function SingularName() {
	return 'fcrNodeIndex';
    }
    public function GetActionKey() {
	return KS_ACTION_TF_NODE;
    }
    // 2017-10-17 was commented out (why?); re-enabled...
    public function GetKeyName() {
	return 'ID';
    }
        
    // -- SETUP -- //
    // ++ EVENTS ++ //
  
    protected function OnCreateElements() {}
    protected function OnRunCalculations() {
	$oPage = fcApp::Me()->GetPageObject();
	//$oPage->SetPageTitle('Suppliers');
	$oPage->SetBrowserTitle('Nodes');
	$oPage->SetContentTitle('All Nodes');
    }
    /*----
      NOTE: App()->GetUserRecord() may return the non-dropin class (TODO: FIX), so
	we have to upgrade it here.
    */
    public function Render() {
	return $this->AdminPage();
     }
     
    // -- EVENTS -- //
    // ++ CLASSES ++ //
    
    protected function NodesLogicClass() {
	return KS_CLASS_TF_NODE_LOGIC;
    }
    //protected function LeafsIndexClass() {
	//return KS_CLASS_TF_LEAF_INDEX;
   // }
    
    // -- CLASSES -- //
    // ++ TABLES ++ //

    protected function NodeLogicTable() {
	return $this->GetConnection()->MakeTableWrapper($this->NodesLogicClass());
    }
   //protected function LeafIndexTable() {
	//return $this->GetConnection()->MakeTableWrapper($this->LeafsIndexClass());
    //}

    // -- TABLES -- //
    // ++ DATA READ ++ //
    
    public function GetRecord_forKey($id) {
    
	//* 2017-10-15 wait... may be unnecessary....
	$tNodes = $this->NodeLogicTable();
	$rcLogic = $tNodes->GetRecord_forKey($id);
	
	$sType = $rcLogic->GetTypeString();
	$tNodes = fctNodesBase::GetNodeTable($sType);
	$rcIndex = $tNodes->SpawnRecordset();
	$rcIndex->SetFieldValues($rcLogic->GetFieldValues());
	return $rcIndex;

	/*
	$rc = $this->SelectNodeRecords('ID='.$id);
	return $rc; */
    }
    /*----
      PURPOSE: 	This class doesn't attach itself to a data schema, so we have to fob record-retrieval off
	  onto other classes, mainly the Node logic class.
      ACTION: I *think* the way to make this work is just to pull up a recordset of Nodes,
	and then invoke the named Node Type classes to display them.
    */
    public function SelectNodeRecords($sqlWhere=NULL,$sqlSort=NULL,$sqlOther=NULL) {
	$tLeafs = $this->LeafIndexTable();
	$tNodes = $this->NodeLogicTable();
	$rsBase = $tNodes->SelectRecords($sqlWhere,$sqlSort,$sqlOther);
	while ($rsBase->NextRow()) {
	    $idNode = $rsBase->GetKeyValue();
	    $arRow = $rsBase->GetFieldValues();
	    $arRow = $tLeafs->GetValues_forNode($idNode,$arRow);	// add Leaf Values to array
	    $this->SetRow($idNode,$arRow);				// stash array in Table row array
	}
	$rsIndex = $this->GetAllRecords();	// pull the table's row array into a recordset
	return $rsIndex;
    }

    // -- DATA WRITE -- //
    // ++ WEB UI ++ //
    
    /*----
      NOTE: For now, this class (fctNodesIndex) handles admin for dynamically-determined node types,
	while there is also admin code for specific node types in fctNodeTypesBase descendants.
	Not sure yet if this is the way it should be. See fctNodes_SimpleWikiPage::AdminPage().
    */
    protected function AdminPage() {
	$rs = $this->SelectNodeRecords();
	$qRows = $rs->RowCount();
	if ($qRows > 0) {
	    return $rs->AdminRows();
	} else {
	    return '<span class=content>No node records in table.</span>';
	}
    }

    // -- WEB UI -- //
}
class fcrNodeIndex extends fcDataRow_array implements fiEventAware {
    use ftLinkableRecord;
    use ftShowableRecord;
    use ftRecord_keyed_single;
    use ftTabledDataRow;		// implements [Get/Set]TableWrapper()
    use ftExecutableTwig;		// event dispatcher
    use ftrNodeLogic;			// core fields

    // ++ EVENTS ++ //
  
    protected function OnCreateElements() {}
    protected function OnRunCalculations() {
	$id = $this->GetKeyValue();
	$htTitle = 'Node #'.$id;
	$sTitle = 'n'.$id;
    
	$oPage = fcApp::Me()->GetPageObject();
	//$oPage->SetPageTitle($sTitle);
	$oPage->SetBrowserTitle($sTitle);
	$oPage->SetContentTitle($htTitle);
    }
    public function Render() {
	return $this->AdminPage();
    }

    // -- EVENTS -- //
    // ++ DATA READ ++ //
    
    /* 2017-10-13 apparently not needed after all
    public function LoadNode_fromBase(fcrNodeLogic $rcNode) {
	$this->SetFieldValues($rcNode->GetFieldValues());	// copy basic record data
	$sType = $rcNode->GetTypeString();
	$tNode = fctNodesBase::GetNodeTable($sType);
	$id = $rcNode->GetKeyValue();
	$rcNodeExt = $tNode->GetRecord_forKey($id);
	echo 'RCNODEEXT:'.fcArray::Render($rcNodeExt->GetFieldValues());
	die();
    } */
    
    // -- DATA READ -- //
    // ++ ADMIN UI ++ //
    
    public function AdminRows_settings_columns() {
	return array(
	  'ID' =>	'ID',
	  'Type' => 	'Type',
	  'WhenMade' => 'Created',
	  //'title' =>	'title',
	  //'content' => 	'content',
	  '!Data' =>	'Data'
	  );
    }
    protected function AdminField($sField) {
	switch($sField) {
	  case 'ID':
	    $val = $this->SelfLink();
	    break;
	  case '!Data':
	    $val = 'place-holder';
	    break;
	  default:
	    $val = $this->GetFieldValue($sField);
	}
	return "<td>$val</td>";
    }
    
    // -- ADMIN UI -- //
}
