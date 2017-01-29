<?php
/*
  PURPOSE: wrapper class for accessing MediaWiki property tables
  HISTORY:
    2015-09-28 Extracted from W3TPL.php
    2016-10-24 This will probably need to be heavily updated.
    2016-11-09 Revising to get it back on its feet again...
*/

/*::::
  PURPOSE: handles page properties across all pages
*/
class w3ctProperties extends fcTable_keyed {
    /*----
      RETURNS: SQL for retrieving properties
      INPUT:
	$sKey: if NULL, retrieve all properties; if not null, just retrieve the named property.
      TODO: Not sure if this is useful.
    */
    protected function FigureSQL_forProperty_global($sKey=NULL) {
	$sqlKey = $this->GetConnection()->Sanitize_andQuote($sKey);
	$sql = 'SELECT pp_page, pp_value FROM page_props';
	if (!is_null($sKey)) {
	    $sql .= " WHERE pp_propname=$sqlKey";
	}
	return $sql;
    }
}
/*::::
  PURPOSE: handles properties for a given MW page/title
  DETAILS:
    * This currently uses the page_props table, but we're treating the properties as global
      and pretending that pages will play nice by not overwriting each other's properties.
    * There's probably a better way to do this, but it probably involves creating a new table for globals,
      which would make it more difficult to install.
*/
class w3ctPageProperties extends w3ctProperties {

    // ++ SETUP ++ //

    public function __construct(Parser $mwoParser, Title $mwoTitle) {
	parent::__construct($mwoParser);
	$this->MW_TitleObject($mwoTitle);
    }

    // -- SETUP -- //
    // ++ CEMENTING ++ //

    protected function TableName() {
	throw new exception('How is this fx used, in this context?');
    }
    protected function SingularName() {
	throw new exception('How is this fx used, in this context?');
    }

    // -- CEMENTING -- //
    // ++ MEDIAWIKI ++ //

    protected function MWDB() {
	return wfGetDB( DB_MASTER );
    }
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
	    $this->mwoParserOutput = $this->MW_ParserObject()->mOutput;
	}
	return $this->mwoParserOutput;
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
    // ++ ACTION: READ ++ //

    /*----
      ACTION: Load the page property value for the given key, or all page properties
	The set of data loaded is determined by how GetLoadSQL() is implemented.
    */
    public function LoadValue($sKey=NULL) {
	$sql = $this->FigureSQL_forProperty($sKey);
	$dbr = $this->GetConnection();
	try {
	    $rs = $dbr->FetchRecordset($sql,$tbl);
	} catch (Exception $e) {
	    $txt = "W3TPL got a db error searching for property [$sKey] - ''".$dbr->ErrorString()."'' - from this SQL:\n* ".$sql;
	    W3AddEcho('<div class="previewnote">'.$txt.'</div>');	// what is the *proper* class for error msgs?
	}

	$qRows = $rs->RowCount();
	if ($qRows <= 0) {
	    // key not found
	    $rtn = NULL;
	} elseif (is_null($sKey)) {
	    // list requested - return as array
	    while($rs->NextRow()) {
		$sKey = $rs->FieldValue('pp_propname');
		$sVal = $rs->FieldValue('pp_value');
		$rtn[$sKey] = $sVal;
	    }
	} else {
	    // one value requested - return as scalar
	    $rs->NextRow();
	    $rtn = $rs->FieldValue('pp_value');
	}

	return $rtn;
    }
    public function LoadVals($sKey) {
	$keys = $this->LoadVal($sKey.'>');
	if (is_null($keys)) {
	    return NULL;
	} else {
	    $xts = new xtString($keys);
	    $arNames = $xts->Xplode();
	    foreach ($arNames as $name) {
		$key = $sKey.'>'.$name;
		$val = $this->LoadVal($key);
		$arDown = $this->LoadVals($key);
		if (is_array($arDown)) {
		    $arThis[$name] = $arDown;
		} else {
		    $arThis[$name] = $val;
		}
	    }
	    return $arThis;
	}
    }

    // -- ACTION: READ -- //
    // ++ ACTION: WRITE ++ //

    /*----
      ACTION: Saves global properties
    */
    public function SaveArray(array $ar, $iBase=NULL) {
	$keys = NULL;
	foreach ($ar as $name => $val) {
	    $keys .= '>'.$name;
	    $key = $iBase.'>'.$name;
	    if (is_array($val)) {
		$this->SaveArray($val,$key);
	    } else {
		$this->SaveVal($key,$val);
	    }
	}
	$this->SaveVal($iBase.'>',$keys);	// save list of all sub-keys
    }
    public function SaveValue($sKey,$sVal) {
	$this->MW_ParserOutputObject()->setProperty($sKey,$sVal);
    }
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
class w3crProperty extends fcRecord_keyed {

    // ++ CEMENTING ++ //

    public function GetKeyString() {
	return $this->GetPageID().'.'.$this->GetPropertyName();
    }
    protected function GetSelfFilter() {
	$idPage = $this->GetPageID();
	$sqlProp = $this->GetPropertyName_Cooked();
	return "(pp_page=$idPage) AND (pp_propname=$sqlProp)";
    }

    // -- CEMENTING -- //
    // ++ FIELD VALUES ++ //
    
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

    // -- FIELD CALCULATIONS -- //

}