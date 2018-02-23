<?php
/*
  PURPOSE: wrapper class for accessing MediaWiki property tables
  PREVIOUS CLASS NAMES: clsPageProps, w3ctProperties, w3ctPageProperties
  HISTORY:
    2015-09-28 Extracted from W3TPL.php
    2016-10-24 This will probably need to be heavily updated.
    2016-11-09 Revising to get it back on its feet again...
    2017-10-30 Renaming:
      w3ctProperties -> fcMWSiteProperties
      w3ctPageProperties -> fcMWPageProperties
    2018-02-10 Okay, this is silly; we should be caching page properties in a local array as needed
      fcMWSiteProperties -> fcMWProperties_site
      fcMWPageProperties -> fcMWProperties_page
*/

/*::::
  PURPOSE: handles page properties across all pages
  DETAILS:
    * This currently uses the page_props table, but we're treating the properties as global
      and pretending that pages will play nice by not overwriting each other's properties.
    * There's probably a better way to do this, but it probably involves creating a new table for globals,
      which would require a setup procedure.
  HISTORY:
    2017-11-04 Much revision over the past few days; changing base table-type to fcTable_wSource.
    2017-11-05 Changing it again to fcTable_wSource_wRecords because we need the SpawnRecordset() method.
    2018-02-10 Substantially rewriting API and how data is stored.
      This class no longer writes data; the MW API only lets pages alter their own properties.
	If we need to write properties on other pages, first document a case for it and then write directly
	to the DB (if MW will allow this).
*/
class fcMWProperties extends fcTable_wSource_wRecords {

    // ++ SETUP ++ //
    
    public function __construct(Parser $mwo) {
	$this->SetMWParserObject($mwo);
    }
    // CEMENT
    protected function SingularName() {
	return 'fcMWProperty';
    }
    
    // -- SETUP -- //
    // ++ MEDIAWIKI OBJECTS ++ //

    protected $mwoParser;
    protected function SetMWParserObject(Parser $mwo) {
	$this->mwoParser = $mwo;
    }
    protected function GetMWParserObject() {
	return $this->mwoParser;
    }
    protected $mwoParserOutput;
    protected function GetMWParserOutputObject() {
	if (empty($this->mwoParserOutput)) {
	    $this->mwoParserOutput = $this->GetMWParserObject()->getOutput();
	}
	return $this->mwoParserOutput;
    }

    // -- MEDIAWIKI OBJECTS -- //
    // ++ CACHE ++ //
    
    private $arProps_byPage, $arProps_byName;
    protected function SetProperty(fcMWProperty $rcProp) {
	$idPage = $rcProp->GetPageID();
	$sName = $rcProp->GetPropertyName();
	$this->arProps_byPage[$idPage][$sName] = $rcProp->GetFieldValues();
	$this->arProps_byName[$sName][$idPage] = $rcProp->GetFieldValues();
	//echo "STORED PAGE [$idPage] NAME [$sName]<br>\n";
    }
    protected function SetProperties(fcMWProperty $rs) {
	while($rs->NextRow()) {
	    $this->SetProperty($rs);
	}
    }
    // TODO: should be renamed something that indicates this specifically means the property values are LOADED
    protected function IsPropertyLoaded($sName) {
	return fcArray::Exists($this->arProps_byName,$sName);
    }
    protected function GetLoadedProperty($sName) {
	return fcArray::Nz($this->arProps_byName,$sName);
    }
    public function DumpLoadedValues() {
	$out = "<ul>\n";
	foreach ($this->arProps_byName as $sProp => $arPages) {
	    $out .= "<li><b>Prop name</b>: [$sProp]\n<ul>\n";
	    foreach ($arPages as $idPage => $val) {
		$sVal = $this->GetPagePropertyValue($idPage,$sProp);
		$out .= "<li><b>on Page ID</b> [$idPage] = [$sVal]";
	    }
	    $out .= "</ul>";
	}
	return $out;
    }
    
    // -- CACHE -- //
    // ++ DATA READ ++ //

    /*----
      USAGE: Always load something first; this returns NULL if property is not loaded (does not load it).
      API
    */
    public function GetPagePropertyObject($idPage,$sName) {
	if (array_key_exists($idPage,$this->arProps_byPage)) {
	    $arProp = fcArray::Nz($this->arProps_byPage[$idPage],$sName);
	    $rcProp = $this->SpawnRecordset();
	    $rcProp->SetFieldValues($arProp);
	    return $rcProp;
	} else {
	    return NULL;
	}
    }
    // RETURNS: unserialized property value (use GetPagePropertyObject() if you want raw)
    public function GetPagePropertyValue($idPage,$sName) {
	$oProp = $this->GetPagePropertyObject($idPage,$sName);
	if (is_null($oProp)) {
	    return NULL;
	} else {
	    if (is_array($oProp)) {
		echo 'okay THIS IS GETTING OLD - array is:'.fcArray::Render($oProp); die();
	    }
	    return unserialize($oProp->GetPropertyValue());
	}
    }

    // -- DATA READ -- //
}
class fcMWProperties_site extends fcMWProperties {
    // ++ SQL ++ //

    /*----
      RETURNS: SQL for retrieving properties
      INPUT:
	$sName: retrieve all values (across all pages) for the named property
      NOTES:
	* Not sure if this is useful. (Renamed 2018-02-10; if nothing gripes about this, then it isn't.)
	  I *think* the idea was that it was useful for finding w3tpl functions, and potentially other global values.
      HISTORY:
	2018-02-10 Renamed FigureSQL_forProperty() -> SQLfor_SelectProperties_byName(); $sName now cannot be NULL
	  (NULL value formerly would retrieve all properties for entire site)
	2018-02-22 Including pp_propname in results because it's needed for the cache array.
    */
//    protected function FigureSQL_forProperty($sName=NULL) {
      protected function SQLfor_SelectProperties_byName($sName) {
	$sqlName = $this->GetConnection()->SanitizeValue($sName);
	$sql = 'SELECT pp_page, pp_propname, pp_value FROM page_props'
	  ." WHERE pp_propname=$sqlName"
	  ;
	return $sql;
    }
    
    // -- SQL -- //
    // ++ DATA READ ++ //

    /*----
      ACTION: Finds all pages having this property, and loads their values into the local cache
      RETURNS: array of page-property-record data
	array[page ID][field] = field value
	  field: pp_page, pp_propname, pp_value
      TODO: should this be renamed GetPropertyRecordsArray()?
	Possibly it should be PROTECTED as well.
      HISTORY:
	2018-02-10 created (rewrite of LoadValue() etc.)
    */
    public function GetPropertyValues($sName) {
	if (!$this->IsPropertyLoaded($sName)) {
    
	    $sql = $this->SQLfor_SelectProperties_byName($sName);
	    try {
		$rs = $this->FetchRecords($sql);
	    } catch (Exception $e) {
		$sErr = fcApp::Me()->GetDatabase()->ErrorString();
		$txt = "db error searching for property [$sName] - <i>$sErr</i> - from this SQL:\n* ".$sql;
		echo $txt;
		throw new exception('Ferreteria/MW data error');
		// TODO: display more gracefully
	    }

	    $this->SetProperties($rs);
	}
	return $this->GetLoadedProperty($sName);
    }
    protected function GetGlobalPropertyValue($sKey) {
	$arVals = $this->GetPropertyValues($sKey);
	$nVals = count($arVals);
	if ($nVals > 1) {
	    // if we actually run into this condition, then should probably list the pages.
	    throw new exception("Multiple pages define the array [$sKey].");	// kluge for now
	} elseif ($nVals == 0) {
	    return NULL;
	}
	$arRec = array_pop($arVals);
	return $arRec['pp_value'];	// return first/only value
	// NOTE: $arRec could also be turned into a Page Property object
    }
    /*----
      LEGACY
      HISTORY:
	2018-02-22 I'm writing this *only* for dealing with old markup in Issuepedia.
      TODO: All w3tpl functions should be re-saved with the new array format or (better) rewritten as plugins.
    */
    public function LoadOldFormatFunction($sName) {
	$sKeyFx = ">fx()>$sName";
	$arOut = $this->LoadOldFormatGlobalArray($sKeyFx);
	return $arOut;
    }
    protected function LoadOldFormatGlobalArray($sName) {
	$sKeyAr = $sName.'>';
	$sList = $this->GetGlobalPropertyValue($sKeyAr);	// value is xplodable list
	$arOut = NULL;
	if (!is_null($sList)) {
	    $arList = fcString::Xplode($sList);			// xplode the value
	    foreach ($arList as $sSubName) {
		$sSubKey = $sKeyAr.$sSubName;
		$sSubVal = $this->GetGlobalPropertyValue($sSubKey);
		if (is_null($sSubVal)) {
		    // not found, so presumably a sub-array
		    $arSub = $this->LoadOldFormatGlobalArray($sSubKey);
		    $arOut[$sSubName] = $arSub;
		} else {
		    $arOut[$sSubName] = $sSubVal;
		}
	    }
	    return $arOut;
	}
    }
    
    /*----
      ACTION: Load the page property value for the given key, or all page properties
	The set of data loaded is determined by how GetLoadSQL() is implemented.
    */
    /* 2018-02-10 I am so just rewriting this mess
    public function LoadValue($sKey=NULL) {
	$sql = $this->FigureSQL_forProperty($sKey);
	
	$dbr = $this->GetConnection();
	try {
	    $rs = $dbr->FetchRecordset($sql,$this);
	} catch (Exception $e) {
	    $txt = "db error searching for property [$sKey] - ''".$dbr->ErrorString()."'' - from this SQL:\n* ".$sql;
	    echo $txt;
	    throw new exception('Ferreteria/MW internal error');
	    //W3AddEcho('<div class="previewnote">'.$txt.'</div>');	// what is the *proper* class for error msgs?
	    // TODO: display more gracefully
	}

	$qRows = $rs->RowCount();
	if ($qRows <= 0) {
	    // key not found
	    $rtn = NULL;
	} elseif (is_null($sKey)) {
	    // list requested - return as array
	    while($rs->NextRow()) {
		$sKey = $rs->GetFieldValue('pp_propname');
		$sVal = $rs->GetFieldValue('pp_value');
		$rtn[$sKey] = $sVal;
	    }
	} else {
	    // one value requested - return as scalar
	    $rs->NextRow();
	    $rtn = $rs->GetFieldValue('pp_value');
	}

	return $rtn;
    }
    public function LoadValues($sKey) {
	$keys = $this->LoadValue($sKey.'>');	// get the key list
	if (is_null($keys)) {
	    return NULL;
	} else {
	    $xts = new xtString($keys);
	    $arNames = $xts->Xplode();
	    foreach ($arNames as $name) {
		$key = $sKey.'>'.$name;
		$val = $this->LoadValue($key);
		$arDown = $this->LoadValues($key);
		if (is_array($arDown)) {
		    $arThis[$name] = $arDown;
		} else {
		    $arThis[$name] = $val;
		}
	    }
	    return $arThis;
	}
    } */

    // -- DATA READ -- //
}
/*::::
  PURPOSE: handles properties for a given MW page/title
*/
class fcMWProperties_page extends fcMWProperties {

    // ++ SETUP ++ //

    public function __construct(Parser $mwoParser, Title $mwoTitle=NULL) {
	if (is_null($mwoTitle)) {
	    global $wgTitle;
	    $mwoTitle = $wgTitle;
	}
	parent::__construct($mwoParser);
	$this->SetMWTitleObject($mwoTitle);
    }

    // -- SETUP -- //
    // ++ MEDIAWIKI ++ //
/* 2018-02-10 this is now the wrong way to get a db object
    protected function MWDB() {
	return wfGetDB( DB_MASTER );
    }
*/
    protected $mwoTitle=NULL;
    protected function SetMWTitleObject(Title $mwo) {
	$this->mwoTitle = $mwo;
	return $mwo;
    }
    protected function GetMWTitleObject() {
	return $this->mwoTitle;
    }
    protected function HasMWTitleObject() {
	return !is_null($this->GetMWTitleObject());
    }


    // -- MEDIAWIKI -- //
    // ++ SQL CALCULATIONS ++ //

    /*----
      RETURNS: SQL for retrieving the value of the given property for the current page
      INPUT:
	$sKey: if NULL, retrieve all properties; if not null, just retrieve the named property.
    */
    protected function FigureSQL_forProperty($sKey=NULL) {
	if ($this->HasMWTitleObject()) {
	    $idArticle = $this->GetMWTitleObject()->getArticleID();
	    $sql = 'SELECT pp_page, pp_propname, pp_value FROM page_props'
	      ." WHERE (pp_page=$idArticle)";
	    if (!is_null($sKey)) {
		$sqlKey = $this->GetConnection()->SanitizeValue($sKey);
		$sql .= " AND (pp_propname=$sqlKey)";
	    }
	    return $sql;
	} else {
	    throw new exception('No page object available for loading value of page property ['.$iKey.'].');
	}
    }
    /*----
      RETURNS: SQL for writing properties
    */
/*    protected function GetSaveSQL($idTitle,$sKey,$sVal) {
	$sqlKey = $this->Database()->SanitizeValue($sKey);
	$sqlVal = $this->Database()->SanitizeValue($sVal);
	$sqlID = (int)$idTitle;
	$sql = "REPLACE INTO page_props (pp_page,pp_propname,pp_value) VALUES ($sqlID,$sqlKey,$sqlVal)";
	return $sql;
    }
    */
    /* 2018-02-10 is this actually in use?
    protected function FigureSQL_toSaveParams() {
	$sql = "REPLACE INTO page_props (pp_page,pp_propname,pp_value) VALUES (@ID,@KEY,@VAL)";
	return $sql;
    } */

    // -- SQL CALCULATIONS -- //
    // ++ DATA READ ++ //

    /*----
      ACTION: Loads all the property values for the current page. Useful if you know you'll be accessing a bunch of them.
      USAGE: Call this before using GetPagePropertyValue() repeatedly on the same page
    */
    public function LoadPropertyValues() {
	$sql = $this->FigureSQL_forProperty();
	try {
	    $rs = $this->FetchRecords($sql);
	    
	    // debugging
	    /*
	    global $wgOut;
	    $wgOut->addHTML(
	      'TITLE: '.$this->GetMWTitleObject()->getFullText().'<br>'
	      .'SQL: '.$sql.'<br>'
	      .'# PROPERTY RECORDS FOUND: ['.$rs->RowCount().']<br>'
	      ); */
	    
	    $this->SetProperties($rs);
	} catch (Exception $e) {
	    $idPage = $this->GetMWTitleObject()->getArticleID();
	    $sErr = $db->ErrorString();
	    $txt = "db error accessing properties for page ID [$idPage] - <i>$sErr</i> - from this SQL:<br> &gt; ".$sql;
	    echo $txt;
	    throw new exception('Ferreteria/MW data error');
	    // TODO: display more gracefully
	}
    }
    /*----
      API
      ACTION: retrieve value for the given property on the current page
      NOTES: This currently uses the MW API, which only works for the current page
	OR if Ferreteria has already loade the page's properties.
    */
    public function GetValue($sName) {
	/* 2018-02-11 This does it the hard way.
	$sVal = $this->GetPagePropertyValue($idPage,$sName);
	*/
	//echo "LOADING PROP [$sName]:";
	$sRaw = $this->GetMWParserOutputObject()->getProperty($sName);
	//echo " MW=[$sRaw]";
	if ($sRaw===FALSE) {
	    // the property might have been set during the current editing session but not saved yet
	    if ($this->IsPropertyLoaded($sName)) {
		// apparently so
		$idPage = $this->GetMWTitleObject()->getArticleID();
		$sVal = $this->GetPagePropertyValue($idPage,$sName);
		//echo " LOADED=[$sVal]";
	    } else {
		//echo ' NOT LOADED';
		$sVal = NULL;
	    }
	} else {
	    //echo ' NOT FALSE';
	    $sVal = unserialize($sRaw);
	}
	return $sVal;
    }
    
    // -- DATA READ -- //
    // ++ DATA WRITE ++ //
    
    // NOTE: (2018-02-10) Hopefully setProperty() actually does all the db writing in one go.
    public function SaveValue($sName,$sVal) {
	$this->GetMWParserOutputObject()->setProperty($sName,serialize($sVal));
	$idPage = $this->GetMWTitleObject()->getArticleID();
	$oProp = new fcMWProperty($this,$idPage,$sName,$sVal);
	$this->SetProperty($oProp);
	//echo "SAVING PROP [$sName] AS [$sVal]<br>\n";
    }
    /*----
      ACTION: Saves global properties
    */
    public function SaveArray(array $ar, $sBase=NULL) {
	throw new exception('2018-02-10 This should be unnecessary now, since values are serialized for storage by default.');
	$keys = NULL;
	foreach ($ar as $name => $val) {
	    $keys .= '>'.$name;
	    $key = $sBase.'>'.$name;
	    if (is_array($val)) {
		$this->SaveArray($val,$key);
	    } else {
		$this->SaveValue($key,$val);
	    }
	}
	$this->SaveValue($sBase.'>',$keys);	// save list of all sub-keys
    }

    // -- DATA WRITE -- //

}
/*----
  HISTORY:
    2017-11-05 tentatively, we just need this as a type for the Table types to spawn
    2018-02-12 cannot have the first argument be anything but a Table type, else Spawning gets messed up
*/
class fcMWProperty extends fcDataRecord {

    // ++ SETUP ++ //
    
    public function __construct(fcTable_wRecords $t, $idPage=NULL,$sName=NULL,$sValue=NULL) {
	parent::__construct($t);
	$this->SetPageID($idPage);
	$this->SetPropertyName($sName);
	$this->SetPropertyValue($sValue);
    }
    
    // ++ FIELD VALUES ++ //
    
    public function GetPageID() {
	return $this->GetFieldValue('pp_page');
    }
    protected function SetPageID($id) {
	$this->SetFieldValue('pp_page',$id);
    }
    public function GetPropertyName() {
	return $this->GetFieldValue('pp_propname');
    }
    protected function SetPropertyName($s) {
	$this->SetFieldValue('pp_propname',$s);
    }
    public function GetPropertyValue() { 
	return $this->GetFieldValue('pp_value');
    }
    protected function SetPropertyValue($s) {
	$this->SetFieldValue('pp_value',$s);
    }

    // -- FIELD VALUES -- //

}
