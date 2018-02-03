<?php
class fcPropertyData_SMW {

    // ++ SETUP ++ //

    public function __construct($sPropName) {
	$this->SetPropertyName($sPropName);
    }
    
    private $sName;
    protected function SetPropertyName($sName) {
	$this->sName = $sName;
    }
    protected function GetPropertyName() {
	return $this->sName;
    }

    // -- SETUP -- //
    // ++ FRAMEWORK ++ //
    
    protected function GetDatabase() {
	return fcApp::Me()->GetDatabase();
    }
        
    // -- FRAMEWORK -- //
    // ++ DATA READ ++ //
    
    /*----
      HISTORY:
	2018-01-30
	  * renaming from GetObjectID() to GetPropertyID_forName()
	  * removing LIMIT 1: if there is more than one record, throw an exception
	  * moving from fcDataConn_SMW to fcPropertyData_SMW
	2018-02-02
	  * renaming from GetPropertyID_forName() to GetPropertyID() because this object already stores property name
	  * removed $sName parameter for the same reason
    */
    public function GetPropertyID() {
	$sName = $this->GetPropertyName();
	$db = $this->GetDatabase();
	$sdbKey = $db->Normalize_PropertyName($sName);
	$sql = "SELECT smw_id FROM smw_object_ids WHERE (smw_title='$sdbKey');";
	$this->sql = $sql;	// for debugging
	$t = $db->MakeTableWrapper('fcUsableTable');
	$rs = $t->FetchRecords($sql);
	if ($rs->HasRows()) {
	    $n = $rs->RowCount();
	    if ($n > 1) {
		// should be exactly one row, if there are any at all
		throw new exception("Ferreteria data design error: $n SMW IDs found for property [$sName].");
	    }
	    $rs->NextRow();	// get the only row
	    $id = $rs->GetFieldValue('smw_id');
	} else {
	    $id = NULL;
	}
	return $id;
    }
    /*----
      ACTION: return an object for every wiki page that uses the given property (specified by SMW ID).
	If $sValue is set, returns only pages which have the property set to that value.
      NOTE: I was originally going to write this as two fx(), one for each step; that could still be done
	(where STEP 1 would return the appropriate table type or NULL if not found).
    */
    public function GetTitleRecords_forID($id,$sValue=NULL) {
	if (is_null($id)) {
	    throw new exception('Ferreteria internal error: looking up titles for null SMW ID.');
	}
    
	// STEP 1: find out which value table has the value for this ID
	$sql = <<<__END__
SELECT fType FROM (
  SELECT p_id,'blob' AS fType FROM smw_di_blob
  UNION
  SELECT p_id,'time' AS fType from smw_di_time
  UNION
  SELECT p_id,'wikiPage' AS fType FROM smw_di_wikipage
  UNION
  SELECT p_id,'bool' AS fType FROM smw_di_bool
  UNION
  SELECT p_id,'coords' AS fType FROM smw_di_coords
  UNION
  SELECT p_id,'number' as fType FROM smw_di_number
  UNION
  SELECT p_id,'uri' AS fType FROM smw_di_uri
) AS u WHERE p_id=$id
__END__;
	$t = $this->GetDatabase()->MakeTableWrapper('fcUsableTable');
	$rs = $t->FetchRecords($sql);
	if ($rs->HasRows()) {
	    $n = $rs->RowCount();
	    if ($n > 1) {
		throw new exception("Ferreteria data design error: $n rows found for SMW ID $id.");
	    }
	    $rs->NextRow();	// should be only one row -- get it.
	    $sClassSfx = $rs->GetFieldValue('fType');
	    
	// STEP 2: look up the record in the appropriate table
	    $sClass = 'fctqSMW_'.ucfirst($sClassSfx);
	    $t = $this->GetDatabase()->MakeTableWrapper($sClass);
	    if (is_null($sValue)) {
		// not sure if this is actually needed, but it was helpful during the writing phase
		$rs = $t->SelectTitleRecords_forPropertyID($id);
	    } else {
		$rs = $t->SelectTitleRecords_forPropertyID_andValue($id,$sValue);
	    }
	    if (is_null($rs)) {
		$sExtra = is_null($sValue)?'':" and value=[$sValue]";
		throw new exception("Ferreteria data error: no titles found for SMW ID=[$id]$sExtra.");
	    }
	} else {
	    $rs = NULL;
	}
	return $rs;
    }
    /*----
      ACTION: Find all pages that use this property, and return an array of page objects
      NOTE: There really ought to be a way to figure out which type-table a given property will be found in,
	because that would save multiple queries here.
	As it is, we pretty much have to cycle through all the classes until we find the right one.
	...and because we don't actually know, we should probably check the rest after that, too.
    */
    public function GetTitleRecords() {
	// STEP 1: look up the property name to get its SMW ID
	$sName = $this->GetPropertyName();
	
	// STEP 2: get the corresponding property object
	$id = $this->GetPropertyID_forName($sName);
	$rs = $this->GetTitleRecords_forID($id);
	//$rs = $rc->GetTitleRecords();
	return $rs;
    /*
	$arClasses = array('fctqSMW_Blob','fctqSMW_WikiPage','fctqSMW_Time');
	foreach ($arClasses as $sClass) {
	    $t = $db->MakeTableWrapper($sClass);
	    $rs = $t->SelectTitleRecords_forProperty($sName);
	    // most of the time, there will be 0 records; 
	    if ($rs->HasRows()) {
		while ($rs->NextRow()) {
		    echo 'ROW: '.fcArray::Render($rs->GetFieldValues());
		}
	    }
	}
    */
    
    }

    // -- DATA READ -- //
}
