<?php
class fcPageData_SMW extends fcPageData_MW {
    
    // ++ FRAMEWORK ++ //
    
    protected function GetDatabase() {
	return fcApp::Me()->GetDatabase();
    }
        
    // -- FRAMEWORK -- //
    // ++ FIELD CALCULATIONS ++ //
    
    /*----
      INPUT: an array in the format returned by GetPages_forPropVal()
    */
    /* 2018-01-26 appears to be no longer used.
    public function Use_Title_Keyed_array(array $iar) {
	$this->Use_Title_Keyed($iar['s_title'],$iar['s_namespace']);
    } */
    public function TitleKey() {
	return $this->GetTitleObject()->getDBkey();
    }
    public function TitleShown() {
	return $this->GetTitleObject()->getText();
    }
    public function TitleFull() {
	return $this->GetTitleObject()->getPrefixedText();
    }
    public function Nspace() {
	return $this->GetTitleObject()->getNamespace();
    }
    
    // -- FIELD CALCULATIONS -- //
    // ++ PROPERTIES ++ //
    
    /*----
      RETURNS: single value string, or NULL if no values found
	Throws an exception if there are multiple values.
	(TODO: Maybe there should be a default concatenation format instead.)
      USAGE: when there's no reason to expect multiple values
    */
    public function GetPropVal($sPropName) {
	throw new exception('GetPropVal() has been renamed GetPropertyValue().');
	
	$ar = $this->GetPropertyValues($sPropName);
	$cnt = count($ar);
	if ($cnt > 1) {
	    return $ar;
	} elseif ($cnt == 1) {
	    return array_shift($ar);	// return just the first element
	} else {
	    return NULL;	// nothing found
	}
    }
    private $arProps = NULL;	// array of property objects
    public function ClearProperties() {
	$this->arProps = NULL;
    }
    /*----
      INPUT: property name, not normalized
      ASSUMES: properties have been loaded
    */
    public function GetProperty($sPropName) {
	if (is_null($this->arProps)) {
	    $this->LoadProperties();
	}
	$snPropName = $this->GetDatabase()->Normalize_PropertyName($sPropName);
	if ($this->PropertyExists($snPropName)) {
	    return $this->arProps[$snPropName];
	} else {
	    return NULL;
	}
    }
    public function GetPropertyValue($sPropName) {
	$oProp = $this->GetProperty($sPropName);
	if (is_null($oProp)) {
	    return NULL;
	} else {
	    if (is_array($oProp)) {
		$sTitle = $this->TitleFull();
		$n = count($oProp);
		throw new \exception('Ferreteria usage error: '
		  ."expecting single value for property [$sPropName] on page [$sTitle] "
		  ."but $n were found. Values are:"
		  .$this->RenderPropertyValues($sPropName)
		  );
	    }
	    return $oProp->GetPropertyValue();
	}
    }
    /*----
      RETURNS: property values as an array
	*always* returns an array, even if there is only a single value
      NOTE: This is a rewrite of GetPropVals().
    */
    public function GetPropertyValues($sPropName) {
	$vProp = $this->GetProperty($sPropName);
	if (is_null($vProp)) {
	    return NULL;	// property not set
	} elseif (is_array($vProp)) {
	    // multiple properties -- extract the value from each
	    foreach ($vProp as $oProp) {
		$ar[] = $oProp->GetPropertyValue();
	    }
	    return $ar;
	} else {
	    return array($vProp->GetPropertyValue());	// put single property value into array
	}
    }
    // NOTE: At this point, just used for debugging
    public function RenderPropertyValues($sPropName,$sSep=', ') {
	$ar = $this->GetPropertyValues($sPropName);
	$out = NULL;
	if (is_array($ar)) {
	    foreach ($ar as $sVal) {
		if (!is_null($out)) {
		    $out .= $sSep;
		}
		$out .= $sVal;
	    }
	}
	return $out;
    }
    // INPUT: normalized property name
    protected function PropertyExists($sPropName) {
	return fcArray::Exists($this->arProps,$sPropName);
    }
    protected function SavePropertyRecord(fcrqSMW $rc) {
	$sName = $rc->GetPropertyName();
	$rcProp = $rc->GetDataClone();
	if ($this->PropertyExists($sName)) {
	    // this is a multivalue property, so needs to be an array
	    $cur = $this->arProps[$sName];	// get old value
	    if (is_array($cur)) {
		// if it's already an array, then we're just adding to that
		$ar = $cur;
	    } else {
		// if it's not an array yet, then start one
		$ar[] = $cur;
	    }
	    $ar[] = $rcProp;	// add current record to the array
	    $this->arProps[$sName] = $ar;
	} else {
	    $this->arProps[$sName] = $rcProp;
	}
    }
    // ACTION: Load all properties for this page
    protected function LoadProperties() {
	$db = $this->GetDatabase();
    
	$sPageKey = $this->TitleKey();
	$sqlPageKey = $db->SanitizeValue($sPageKey);
	$intNSpace = (int)$this->Nspace();

	// array of property class names - add in here as more are written
	$arClasses = array('fctqSMW_Blob','fctqSMW_WikiPage','fctqSMW_Time');
	
	foreach ($arClasses as $sClass) {
	    $t = $db->MakeTableWrapper($sClass);
	    $rs = $t->SelectPropertyRecords_forTitle($sqlPageKey,$intNSpace);
	    while ($rs->NextRow()) {
		$this->SavePropertyRecord($rs);
	    }
	}
    
    }
    public function DumpProperties() {
	$sPage = $this->TitleKey();
	$sProps = '<ul>';
	foreach ($this->arProps as $arProp) {
	    if (!is_array($arProp)) {
		$arProp = array($arProp);
	    }
	    // TODO: we can probably consolidate prop arrays by just showing the values
	    foreach ($arProp as $oProp) {
		$sProps .= '<li><b>'
		  // TODO: check $oProp - may be an array
		  .$oProp->GetPropertyName()
		  .'</b>: '
		  .$oProp->GetPropertyValue()
		  ;
	    }
	}
	$sProps .= '</ul>';
    }
    
    /*----
      RETURNS: nicely-formatted list of property values with links
    */
    public function GetPropLinks($sPropName) {
	$strPgTitle = $this->TitleKey();

	$arArgs = array($strPgTitle,'?'.$sPropName);

	// get list of targets (usually just one, but could be more)
	$htVal = SMWQueryProcessor::getResultFromFunctionParams(
	  $arArgs,
	  SMW_OUTPUT_FILE,
	  SMWQueryProcessor::INLINE_QUERY,
	  TRUE);	// treat as if #show (rather than #ask)
	return $htVal;
    }
    
    // -- PROPERTIES -- //
}
// base classes for property-handling tables

abstract class fctqSMW extends fcTable_wSource_wRecords {
    use ftSelectable_Table, ftReadableTable;

    abstract public function SelectPropertyRecords_forTitle($sqlPageKey,$intNSpace);
//    abstract public function SelectRecords_forTitle_andPropName($sqlPageKey,$intNSpace,$sqlPropKey);
    // 2018-01-29 This seems unlikely to be needed:
    //abstract public function SelectTitleRecords_forProperty($sqlPropKey);
    
    // TODO: Maybe this should be "SelectPageRecords..." to distinguish between Titles (MW) and Pages (Ferreteria).
    public function SelectTitleRecords_forPropertyID($id) {
	$rs = $this->SelectRecords('p_id='.$id);
	return $rs;
    }
}
// PURPOSE: for classes where it makes sense to search for an exact value (e.g. NOT floating-point or time)
abstract class fctqSMW_exact extends fctqSMW {

    // ++ SQL PROPERTIES ++ //

    private $sqlFields=NULL;
    protected function SetFieldsString_forSelect($sql) {
	$this->sqlFields = $sql;
    }
    protected function FieldsString_forSelect() {
	if (is_null($this->sqlFields)) {
	    throw new exception('Ferreteria usage error: must define SQL for field list before calling this.');
	}
	return $this->sqlFields;
    }

    private $sqlSource=NULL;
    protected function SetSourceString_forSelect($sql) {
	$this->sqlSource = $sql;
    }
    // OVERRIDE
    protected function SourceString_forSelect() {
	if (is_null($this->sqlSource)) {
	    throw new exception('Ferreteria usage error: must define source SQL before calling this.');
	}
	return $this->sqlSource;
    }

    // -- SQL PROPERTIES -- //
    // ++ DATA READ ++ //

    // TODO: Maybe this should be "SelectPageRecords..." to distinguish between Titles (MW) and Pages (Ferreteria).
    abstract public function SelectTitleRecords_forPropertyID_andValue($id,$sValue);

    // -- DATA READ -- //

}
abstract class fcrqSMW extends fcDataRecord {
    abstract public function GetPropertyName();
    abstract public function GetPropertyValue();

    // ++ FIELD VALUES ++ //

    public function GetTitleID() {
	return $this->GetFieldValue('s_id');
    }

    // -- FIELD VALUES -- //
    // ++ QUERY FIELDS ++ //
    
    /*----
      HISTORY:
	2018-02-02 Actually, probably not a good idea for external callers to get the title without the namespace.
	  Making this protected. External callers should use GetTitleObject().
    */
    protected function GetTitleString() {
	return $this->GetFieldValue('s_title');
    }
    protected function GetTitleNSID() {
	return $this->GetFieldValue('s_namespace');
    }
    public function GetTitleObject() {
	$sTitle = $this->GetTitleString();
	$nSpace = $this->GetTitleNSID();
	$oTitle = new fcPageData_SMW();
	$oTitle->Use_Title_Keyed($sTitle,$nSpace);
	return $oTitle;
    }
    
    // -- QUERY FIELDS -- //
    
}
