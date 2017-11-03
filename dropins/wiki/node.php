<?php
/*
  PURPOSE: abstract base class for specific Node types to descend from
  TODO: These should probably be renamed fctNodesIndex/fcrNodeIndex - these classes are just
    for managing the node index table, not for building full node structures. Node structures
    bring together all the different tables. They might be built from a query or by packing stuff
    into an array, but either way it makes sense to keep them separate from the Node table. I think.
  RULES:  
    (TO BE REVISED) The raw, sequential, per-Leaf Value data is pulled up by another class, and shuffled into a per-node array in this class.
    (TO BE REVISED) Descendants from this class register themselves here at init-time, so we have a list of all the Node Types in which we
      might be interested (and for which we have handlers).
    Each Node Type has a set of Leafs (fields); each Leaf has a Type.
    Each generic Leaf has a record, and there's a separate table for each type of Value (one Value per Leaf)
    (TO BE REVISED) Each Node Type Table registers (in the base Node Type class, i.e. here) the Leafs it will be using.
      This causes the Leafs to register the Types they will need.
  HISTORY:
    2017-04-01 started
    2017-08-14 experimentally rolling node-type.php back into node.php to see if all the dependencies work out ok
    2017-10-20 event logging
*/
define('KS_EVENT_TEXTFERRET_NODE_CREATE','node-ins');
define('KS_EVENT_TEXTFERRET_NODE_UPDATE','node-upd');

// PURPOSE: stuff that's common to both the array-based abstract node classes and the table-based node classes
trait ftNodeBaseTable {

    // ++ SETUP ++ //

    protected function TableName() {
	return 'tf_node';
    }

    // -- SETUP -- //
}
/*::::
  PURPOSE: root node table/records
*/
abstract class fctNodesBase extends fcTable_keyed_single_standard /* implements fiLinkableTable, fiEventAware */ {
    //use ftLinkableTable;
    //use ftExecutableTwig;
    use ftNodeBaseTable;
    use ftTableAccess_LeafIndex;	// implements LeafIndexTable()


    // ++ TRANSIENT DATA ++ //

      // node types

    // NOTE: Not sure if this will work as intended; if the static data is instantiated separately for each child class, then nope.
    static private $arTypes=NULL;
    // USAGE: Each descendant Node Type should register itself here
    static protected function RegisterNodeType(fctNodesBase $tNodes) {
	// add table wrapper object to types index
	$sType = $tNodes->GetTypeString();
	self::$arTypes[$sType] = $tNodes;
    }
    // RETURNS: Table object for the given Node Type
    static public function GetNodeTable($sType) {
	return self::$arTypes[$sType];
    }
    static protected function TypeExists($sType) {
	return array_key_exists($sType,self::$arTypes);
    }

      // leafs
    
    protected function FigureLeafJoinSQL() {
	$sql = NULL;
	foreach ($this->$arLeafs as $sLeaf => $t) {	// leaf name => table object
	    $sName = $t->TableName_cooked();	// SQL-safe table name
	    $sqlThis = $sName.' AS '.$sLeaf;
	    
	    $sql = is_null($sql) ? $sqlThis : "($sql AS $sLeafLast LEFT JOIN $sqlThis AS $sLeaf)";

	    $sLeafLast = $sLeaf;	// so we know what to JOIN with
	}
	return $sql;
    }
    protected function FigureSourceSQL() {
	$sqlBase = $this->TableName_cooked();
	$sqlLeafs = $this->FigureLeafJoinSQL();
	$sql = "$sqlBase LEFT JOIN $sqlLeafs";
	return $sql;
    }
    
      // leaf types
    
    private $arLTypes;
    private $arLClasses = array();
    protected function LoadLeafType($sClass) {
	throw new exception('2017-09-30 Call GetLeafTable_fromType() instead.');
    }

    // -- TRANSIENT DATA -- //
    // ++ TABLES ++ //
    
    //protected function LeafIndexTable() {
	//return $this->GetConnection()->MakeTableWrapper(KS_CLASS_TF_LEAF_INDEX);
    //}
    
    // -- TABLES -- //
    // ++ DATA WRITE ++ //    
    
    protected function CreateNode($sType) {
	if (self::TypeExists($sType)) {
	    $idEvent = fcApp::Me()->CreateEvent($sCode,$sText,$arData);
	    $ar = array(
	      'Type'	=> $this->GetConnection()->Sanitize_andQuote($sType)
	      );
	    return $this->Insert($ar);
	} else {
	    throw new exception("Ferreteria node usage error: trying to create a node of type [$sType], which has not been registered.");
	}
    }
    
    /*----
      NOTE: This is more complex than a regular Table insert. It goes like this:
	1. Create a Node record.
	2. For each Leaf:
	  2a. create a Leaf record pointing at the new Node.
	  3b. create a Leaf Value record of the appropriate type pointing to its corresponding Leaf.
      INPUT:
	$arData: array[field name] => Leaf object (of non-base type) with value set, in STORAGE format
    */
    public function InsertNode(array $arData,$sType) {
	$db = $this->GetConnection();
	$db->TransactionOpen();
	
	$arEvent = array(
	  'type'	=> $sType,
	  'after'	=> $arData,
	  );

	// STEP 1: create the base node record
	$idNode = $this->CreateNode($sType);
	//echo "Creating node ID=[$idNode] SQL=".$this->sql."<br>";
	if ($idNode === FALSE) {
	    $db->TransactionKill();
	    // 2017-05-22 Eventually make this catchable so it can just be displayed on the admin panel
	    if ($db->IsOkay()) {
		echo '<b>Coding error somewhere.</b>';
	    } else {
		echo '<b>Database error</b>: '.$db->ErrorString();
		echo '<br><b>SQL</b>: '.$db->sql;
	    }
	    throw new exception('Could not create node.');
	} else {
	    // (step 1 success)
	        
	    // STEP 2: create the node's leafs
	    $tLeaf = $this->LeafIndexTable();
	    foreach ($arData as $sName => $sValue) {
		//$tLeaf = $this->GetLeafTable_fromName($sName);		// look up Leaf table-object from field name
		$tLeaf->MakeLeaf_fromNode_andName($idNode,$sName,$sValue);
		if (!$db->IsOkay()) {
		    $db->TransactionKill();
		  // 2017-05-22 Eventually make this catchable so it can just be displayed on the admin panel
		    echo '<b>Database error</b>: '.$db->ErrorString()
		      .'<br><b>SQL</b>: '.$db->sql
		      ;
		    // these seem to always come to the same thing:
		    //echo '<br><b>SQL 2</b>: '.$tLeaf->sql;
		    //echo '<br><b>SQL 3</b>: '.$rcLeaf->sql;
		    throw new exception("Could not create Leaf Value named [$sName] with value [$sValue].");
		    return NULL;
		}
	    }
	    $idEvent = fcApp::Me()->CreateEvent(KS_EVENT_TEXTFERRET_NODE_CREATE,'node create',$arEvent);
	    $db->TransactionSave();
	}
	return $idNode;
    }

    // -- DATA WRITE -- //

}
class fcrNodeBase extends fcRecord_standard {
    // ++ FIELD VALUES ++ //

    /* 2017-09-22 maybe not needed
    protected function SetTypeString($s) {
	return $this->SetFieldValue('Type',$s);
    } */
    
    // -- FIELD VALUES -- //
    // ++ DATA READ ++ //
   
    // ACTION: Loads Leaf Values for this Node and adds them to the local field array
    protected function LoadLeafValues() {
	$idNode = $this->GetKeyValue();
	$tLeafs = $this->LeafIndexTable();
	$arVals = $tLeafs->GetValues_forNode($idNode);
	$this->SetFieldValues($arVals);
    }
    
    // -- DATA READ -- //
    // ++ DATA WRITE ++ //
    
    public function UpdateNode(array $arData,$sType) {
	$db = $this->GetConnection();
	$db->TransactionOpen();

	// -- save old values for event log (TODO: this could be an option that some classes might not need)
	$this->LoadLeafValues();
	$arEvent['before'] = $this->GetFieldValues();
	// -- save new values for event log
	$arEvent['after'] = $arData;
	
	$idNode = $this->GetKeyValue();
	$tLeaf = $this->LeafIndexTable();
	foreach ($arData as $sName => $sValue) {
	    $tLeaf->MakeLeaf_fromNode_andName($idNode,$sName,$sValue);	// update the Leaf Type and Value

	    if (!$db->IsOkay()) {
		$db->TransactionKill();
	      // 2017-05-22 Eventually make this catchable so it can just be displayed on the admin panel
		echo '<b>Database error</b>: '.$db->ErrorString()
		  .'<br><b>SQL</b>: '.$db->sql
		  ;
		throw new exception("Could not update Leaf Value named [$sName] with value [$sValue].");
		return NULL;
	    }
	}
	$idEvent = fcApp::Me()->CreateEvent(KS_EVENT_TEXTFERRET_NODE_UPDATE,'node update',$arEvent);
	$db->TransactionSave();
    }

    // -- DATA WRITE -- //
}



