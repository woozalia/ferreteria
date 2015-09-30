<?php
/*
  PURPOSE: MediaWiki data interface classes
  HISTORY:
    2014-12-27 split from smw-base.php
    2015-03-14 forked from data-mw.php (renamed db-conn-mw.php); rewriting for Ferreteria db v2
*/

/*%%%%
  PURPOSE: Connection that uses MediaWiki's db
*/
class fcDataConn_MW extends fcDataConn_MySQL {
    private $mwDB;

    // ++ SETUP ++ //

    public function __construct(DatabaseBase $oMWDB) {
	$this->MWDB($oMWDB);
    }
    public function MWDB($oDB=NULL) {
	if (!is_null($oDB)) {
	    $this->mwDB = $oDB;
	}
	return $this->mwDB;
    }

    // -- SETUP -- //
    // ++ INHERITED ++ //

    // For this class, these methods do nothing; the connection is opened/closed elsewhere.
    public function Open() {}
    public function Shut() {}
    // it looks like the MW DB classes don't support this
    public function Sanitize($sSQL) {
	throw new exception(__CLASS__.' does not support Sanitize() without quoting.');
    }
    // this also quotes numbers, but that *shouldn't* be a problem...
    public function Sanitize_andQuote($sSQL) {
	return $this->MWDB()->addQuotes($sSQL);
    }
    /*----
      ACTION: Normalizes wiki page title (spaces become underscores, etc.) then
	sanitizes and quotes for use in SQL statements.
      INPUT:
	$idNSpace: ID of namespace to which title belongs
    */
    public function Sanitize_PageTitle($sName,$idNSpace) {
	return $this->Sanitize_andQuote(static::NormalizeTitle($sName,$idNSpace));
    }

    // -- INHERITED -- //
    // ++ CONVENTIONS ++ //

    /*----
      ACTION: Convert title into normalized DB-key format
	Surely there's some MW function which already does this...?
    */
    static public function NormalizeTitle($iTitle,$iNameSpace) {
	$strTitle = Sanitizer::decodeCharReferencesAndNormalize($iTitle);	// convert HTML entities
	$strTitle = Title::capitalize($strTitle,$iNameSpace);			// initial caps, if needed
	$strTitle = str_replace(' ', '_',$strTitle);				// convert spaces to underscores
	return $strTitle;
    }
    /*----
      ACTION: convert DB-key formatted title into display format
	Basically, just convert underscores to spaces.
    */
    static public function VisualizeTitle($iTitle) {
	$strTitle = str_replace('_',' ',$iTitle);				// convert spaces to underscores
	return $strTitle;
    }

    // -- CONVENTIONS -- //
    // ++ DATA RETRIEVAL ++ //

    public function Recordset($sSQL) {

	$mwoRe = $this->MWDB()->query($sSQL);	// $mwoRe is a ResultWrapper http://svn.wikimedia.org/doc/classResultWrapper.html
	$poRes = $mwoRe->result;

	/*
	if (is_resource($mwoRes)) {
	    $foRes = new fcDataResult_MW($this,$mwoRes);
	    $sClass = $this->RecordsetClassName();
	    $rs = new $sClassName();
	    $rs->sqlMake = $sSQL;		// WORKING HERE
	    $rs->ResultHandler($foRes);
	    $this->PrepareItem($rs);
	    return $rs;
	} else {
	    echo '<b>Note</b>: mwoRes is an object of class "'.get_class($mwoRes).'".<br>';
	    throw new exception('MediaWiki query failed. SQL: '.$sSQL);
	}*/

	return $this->ProcessResultset($poRes);
    }

    // -- DATA RETRIEVAL -- //
    // ++ ??? ++ //

    protected function PrepareItem(clsRecs_abstract $iItem) {
	$iItem->objDB = $this;
    }

    // -- ??? -- //
    // ++ MEDIAWIKI CONTENT ++ //

    /*----
      PURPOSE: same as Titles_forTopic_res, but returns an array
    */
    public function Titles_forTopic_arr($sTopic) {
	$res = $this->Titles_forTopic_res($sTopic);
	$db = $this->MWDB();

	// process the data
	$arTitles = array();
	if ($db->numRows($res)) {
	    while ( $row = $db->fetchObject($res) ) {
		$idTitle = $row->idTitle;
		$sTitle = $row->sTitle;
		$arTitles[$idTitle] = $sTitle;
	    }
	}
	$db->freeResult( $res );
	return $arTitles;
    }
    /*----
      INPUT:
	$sTopic = name of category for which we want the title list
      DATA DETAILS
	cl_to 		= the text of the name (no namespace) of the category being linked to
	page_title	= the text for a page's title
	page_id		= the numeric ID for that page
      RETURNS: MW resultset
    */
    public function Titles_forTopic_res($sTopic) {
	global $wgCapitalLinks;

	if ($wgCapitalLinks) {
	    $sTopic = ucfirst($sTopic);
	}

	$arTables = array(
	    'cl'	=> 'categorylinks',
	    'p'		=> 'page',
	  );
	$arFields = array(		// vars (array)
	    'idTitle'	=> 'p.page_id',
	    'sTitle'	=> 'p.page_title'
	  );
	$arConds = array(		// conditions
	    'cl.cl_to="'.$sTopic.'"',
	  );
	$arOptions = array(		// options
	    'ORDER BY'	=> 'page_title DESC',
	  );
	$arJoins = array(
	    'p'	=> array('LEFT JOIN','cl.cl_from=p.page_id'),
	  );

	$db = $this->MWDB();
//	$db =& wfGetDB( DB_SLAVE );
/*
	$sql = $db->selectSQLText(
	  $arTables,	// tables (string or array)
	  $arFields,	// fields to return
	  $arConds,	// filter conditions
	  __METHOD__,
	  $arOptions,
	  $arJoins	// joins (array)
	);
*/
	// execute SQL and get data
	$res = $db->select(
	  $arTables,	// tables (string or array)
	  $arFields,	// fields to return
	  $arConds,	// filter conditions
	  __METHOD__,
	  $arOptions,
	  $arJoins	// joins (array)
	);

// debugging
//$sql = $db->lastQuery();
//echo "SQL=[$sql]<br>";

	return $res;
    }
    /*----
      RETURNS: array of properties for the given page
	array[property name] = property value
    */
    public function Props_forPage_arr($idTitle) {
	$res = $this->Props_forPage_res($idTitle);
	$db = $this->MWDB();

	// process the data
	$arProps = NULL;
	if ($db->numRows($res)) {
	    while ( $row = $db->fetchObject($res) ) {
		$sName = $row->sName;
		$sValue = $row->sValue;
		$arProps[$sName] = $sValue;
	    }
	}
	return $arProps;
    }
    /*----
      RETURNS: MW resultset of properties for the given page
    */
    public function Props_forPage_res($idTitle) {
	$arTables = array(
	    'pp'	=> 'page_props'
	    );
	$arFields = array(		// vars (array)
	    'sName'	=> 'pp_propname',
	    'sValue'	=> 'pp_value'
	  );
	$arConds = array(
	    'pp.pp_page='.$idTitle
	    );
	$arOptions = array();
	$arJoins = array();

	$db = $this->MWDB();
	// execute SQL and get data
	$res = $db->select(
	  $arTables,	// tables (string or array)
	  $arFields,	// fields to return
	  $arConds,	// filter conditions
	  __METHOD__,
	  $arOptions,
	  $arJoins	// joins (array)
	);
	return $res;
    }

    // -- MEDIAWIKI CONTENT -- //
}

//class fcDataResult_MW extends fcDataResult {
//}
