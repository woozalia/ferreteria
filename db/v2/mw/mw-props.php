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
*/
class fcMWSiteProperties extends fcTable_wSource_wRecords {

    // ++ SETUP ++ //

    public function __construct(Parser $mwo) {
	$this->MW_ParserObject($mwo);
    }
    // CEMENT
    protected function SingularName() {
	return 'fcMWProperty';
    }
    
    // -- SETUP -- //
    // ++ MEDIAWIKI OBJECTS ++ //

    protected $mwoParser;
    protected function MW_ParserObject(Parser $mwo=NULL) {
	if (!is_null($mwo)) {
	    $this->mwoParser = $mwo;
	}
	return $this->mwoParser;
    }
    protected $mwoParserOutput;
    protected function MW_ParserOutputObject() {
	if (empty($this->mwoParserOutput)) {
	    $this->mwoParserOutput = $this->MW_ParserObject()->getOutput();
	}
	return $this->mwoParserOutput;
    }

    // -- MEDIAWIKI OBJECTS -- //
    // ++ SQL ++ //

    /*----
      RETURNS: SQL for retrieving properties
      INPUT:
	$sKey: if NULL, retrieve all properties; if not null, just retrieve the named property.
      TODO: Not sure if this is useful.
    */
    protected function FigureSQL_forProperty($sKey=NULL) {
	$sqlKey = $this->GetConnection()->SanitizeValue($sKey);
	$sql = 'SELECT pp_page, pp_value FROM page_props';
	if (!is_null($sKey)) {
	    $sql .= " WHERE pp_propname=$sqlKey";
	}
	return $sql;
    }
    
    // -- SQL -- //
    // ++ DATA READ ++ //

    /*----
      ACTION: Load the page property value for the given key, or all page properties
	The set of data loaded is determined by how GetLoadSQL() is implemented.
    */
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
    }

    // -- DATA READ -- //
    // ++ DATA WRITE ++ //
    
    public function SaveValue($sKey,$sVal) {
	$this->MW_ParserOutputObject()->setProperty($sKey,$sVal);
    }
    /*----
      ACTION: Saves global properties
    */
    public function SaveArray(array $ar, $sBase=NULL) {
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
/*::::
  PURPOSE: handles properties for a given MW page/title
*/
class fcMWPageProperties extends fcMWSiteProperties {

    // ++ SETUP ++ //

    public function __construct(Parser $mwoParser, Title $mwoTitle) {
	parent::__construct($mwoParser);
	$this->MW_TitleObject($mwoTitle);
    }

    // -- SETUP -- //
    // ++ MEDIAWIKI ++ //

    protected function MWDB() {
	return wfGetDB( DB_MASTER );
    }
    protected $mwoTitle;
    protected function MW_TitleObject(Title $mwo=NULL) {
	if (!is_null($mwo)) {
	    $this->mwoTitle = $mwo;
	}
	return $this->mwoTitle;
    }

    // -- MEDIAWIKI -- //
    // ++ SQL CALCULATIONS ++ //

    /*----
      RETURNS: SQL for retrieving the value of the given property for the current page
      INPUT:
	$sKey: if NULL, retrieve all properties; if not null, just retrieve the named property.
    */
    protected function FigureSQL_forProperty($sKey=NULL) {
	if (is_object($this->MW_TitleObject())) {
	    $idArticle = $this->MW_TitleObject()->getArticleID();
	    $sql = 'SELECT pp_page, pp_propname, pp_value FROM page_props'
	      ." WHERE (pp_page=$idArticle)";
	    if (!is_null($sKey)) {
		$sqlKey = $this->GetConnection()->Sanitize_andQuote($sKey);
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
	$sqlKey = $this->Database()->Sanitize_andQuote($sKey);
	$sqlVal = $this->Database()->Sanitize_andQuote($sVal);
	$sqlID = (int)$idTitle;
	$sql = "REPLACE INTO page_props (pp_page,pp_propname,pp_value) VALUES ($sqlID,$sqlKey,$sqlVal)";
	return $sql;
    }
    */
    protected function FigureSQL_toSaveParams() {
	$sql = "REPLACE INTO page_props (pp_page,pp_propname,pp_value) VALUES (@ID,@KEY,@VAL)";
	return $sql;
    }

    // -- SQL CALCULATIONS -- //
    // ++ ACTION: WRITE ++ //

    /*----
      ACTION: Load an array of values for the given key
	This assumes that arrays are stored in a structure something like this:
	  "key>" => "\subkey1\subkey2"
	  "key>subkey1" => value
	  "key>subkey2" => value
	  (Not sure if this is the exact structure; that should be checked.)
      TODO: This should be renamed something like "LoadArray_forKey()".
    */

    // -- ACTION: WRITE -- //
}
// 2017-11-05 tentatively, we just need this as a type for the Table types to spawn
class fcMWProperty extends fcDataRecord /* was fcRecord_keyed */ {

    // ++ FIELD CALCULATIONS ++ //
/*
    // CEMENT
    public function GetKeyString() {
	return $this->GetPageID().'.'.$this->GetPropertyName();
    }
    public function GetKeyValue() {
	return $this->GetKeyString();
    }
    
    // CEMENT
    protected function GetSelfFilter() {
	$idPage = $this->GetPageID();
	$sqlProp = $this->GetPropertyName_Cooked();
	return "(pp_page=$idPage) AND (pp_propname=$sqlProp)";
    }
*/
    // -- FIELD CALCULATIONS -- //
    // ++ FIELD VALUES ++ //
/*    
    protected function GetPageID() {
	$this->GetFieldName('pp_page');
    }
    protected function GetPropertyName() {
	$this->GetFieldName('pp_propname');
    }
    
    // -- FIELD VALUES -- //
    // ++ FIELD CALCULATIONS ++ //
    
    protected function GetPropertyName_Cooked() {
	return $this->GetConnection()->Sanitize_andQuote($this->GetPropertyName());
    }
*/
    // -- FIELD CALCULATIONS -- //

}
