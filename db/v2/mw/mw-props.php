<?php
/*
  PURPOSE: wrapper class for accessing MediaWiki property tables
  HISTORY:
    2015-09-28 Extracted from W3TPL.php
*/

/*%%%%
  PURPOSE: General access to the Properties data (not Title-specific)
  DETAILS:
    * This currently uses the page_props table, but we're treating the properties as global
      and pretending that pages will play nice by not overwriting each other's properties.
    * There's probably a better way to do this, but it probably involves creating a new table for globals,
      which would make it more difficult to install.
*/
class clsContentProps {

    // ++ SETUP ++ //

    public function __construct(Parser $mwo) {
	$this->MW_ParserObject($mwo);
    }

    // -- SETUP -- //
    // ++ INTERNAL OBJECT ACCESS ++ //

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

    // -- INTERNAL OBJECT ACCESS -- //
    // ++ GLOBAL OBJECT ACCESS ++ //

    protected function MWDB() {
	return wfGetDB( DB_MASTER );
    }
    protected function Database() {
	$foDB = new fcDataConn_MW($this->MWDB());
	return $foDB;
    }

    // -- GLOBAL OBJECT ACCESS -- //
    // ++ SQL CALCULATIONS ++ //

    /*----
      RETURNS: SQL for retrieving properties
      INPUT:
	$sKey: if NULL, retrieve all properties; if not null, just retrieve the named property.
    */
    protected function GetLoadSQL($sKey=NULL) {
	$sqlKey = $this->Database()->Sanitize_andQuote($sKey);
	$sql = 'SELECT pp_page, pp_value FROM page_props';
	if (!is_null($sKey)) {
	    $sql .= " WHERE pp_propname=$sqlKey";
	}
	return $sql;
    }

    // -- SQL CALCULATIONS -- //
    // ++ ACTION: READ ++ //

    /*----
      ACTION: Load the page property value for the given key, or all page properties
	The set of data loaded is determined by how GetLoadSQL() is implemented.
    */
    public function LoadVal($sKey=NULL) {
	$sql = $this->GetLoadSQL($sKey);
	try {
	    $rs = $this->Database()->Recordset($sql);
	} catch (Exception $e) {
	    $txt = "W3TPL got a db error searching for property [$iKey] - ''".$dbr->lastError()."'' - from this SQL:\n* ".$sql;
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
    public function SaveArray(array $iArr, $iBase=NULL) {
	$keys = NULL;
	foreach ($iArr as $name => $val) {
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
    public function SaveVal($sKey,$sVal) {
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
    // ++ OBJECT-SPECIFIC ++ //


    // -- OBJECT-SPECIFIC -- //
}
/*%%%%
  PURPOSE: Title-specific access to the Properties data
  DETAILS: This currently uses the page_props table, but it should be substrate-independent -- e.g.
    it could be modified to use SMW without breaking anything.
*/
class clsPageProps extends clsContentProps {

    // ++ SETUP ++ //

    public function __construct(Parser $mwoParser, Title $mwoTitle) {
	parent::__construct($mwoParser);
	$this->MW_TitleObject($mwoTitle);
    }

    // -- SETUP -- //
    // ++ FIELD ACCESS ++ //

    protected $mwoTItle;
    protected function MW_TitleObject(Title $mwo=NULL) {
	if (!is_null($mwo)) {
	    $this->mwoTitle = $mwo;
	}
	return $this->mwoTitle;
    }

    // -- FIELD ACCESS -- //
    // ++ SQL CALCULATIONS ++ //

    /*----
      RETURNS: SQL for retrieving properties for the current page
      INPUT:
	$sKey: if NULL, retrieve all properties; if not null, just retrieve the named property.
    */
    protected function GetLoadSQL($sKey=NULL) {
	if (is_object($this->MW_TitleObject())) {
	    $idArticle = $this->MW_TitleObject()->getArticleID();
	    $sql = 'SELECT pp_page, pp_propname, pp_value FROM page_props'
	      ." WHERE (pp_page=$idArticle)";
	    if (!is_null($sKey)) {
		$sqlKey = $this->Database()->Sanitize_andQuote($sKey);
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
    protected function GetSaveSQL() {
	$sql = "REPLACE INTO page_props (pp_page,pp_propname,pp_value) VALUES (@ID,@KEY,@VAL)";
	return $sql;
    }

    // -- SQL CALCULATIONS -- //
    // ++ ACTION: WRITE ++ //

    /* 2015-09-29 This just doesn't work.
    public function SaveVal($sKey,$sVal) {
$sTitle = $this->MW_ParserOutputObject()->getDisplayTitle();
echo "\nSAVING [$sKey] to [$sTitle]: [$sVal]<br>";
	$mwo = $this->MW_TitleObject();
//	$sql = $this->GetSaveSQL($mwo->getArticleID(),$sKey,$sVal);
	$sql = $this->GetSaveSQL();
	$arArgs = array(
	  'ID'	=> $mwo->getArticleID(),
	  'KEY'	=> $sKey,
	  'VAL'	=> $sVal
	  );
	$this->Database()->MWDB()->execute($sql,$arArgs);
    } */

    // -- ACTION: WRITE -- //

}
