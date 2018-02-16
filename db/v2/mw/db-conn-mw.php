<?php
/*
  PURPOSE: MediaWiki data interface classes
  HISTORY:
    2014-12-27 split from smw-base.php
    2015-03-14 forked from data-mw.php (renamed db-conn-mw.php); rewriting for Ferreteria db v2
    2016-10-24 This will probably need to be heavily updated.
      For example, FetchRecordset() needs to have a Table wrapper object for the 2nd argument.
    2017-11-05 updating so w3tpl can live again
*/

/*::::
  PURPOSE: Connection that uses MediaWiki's db
*/
//class fcDataConn_MW extends fcDataConn_MySQL {
class fcDataConn_MW extends fcDataConn_CliSrv {

    // ++ SETUP ++ //

    public function __construct(IDatabase $mwo) {
	//$this->MWDB($oMWDB);
	$this->NativeObject($mwo);
    }
    private $oNative;
    protected function NativeObject(IDatabase $mwo=NULL) {
	if (!is_null($mwo)) {
	    $this->oNative = $mwo;
	}
	return $this->oNative;
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
    /*----
      ACTION: Normalizes wiki page title (spaces become underscores, etc.) then
	sanitizes and quotes for use in SQL statements.
      INPUT:
	$idNSpace: ID of namespace to which title belongs
    */
    public function Sanitize_PageTitle($sName,$idNSpace) {
	return $this->SanitizeValue(static::NormalizeTitle($sName,$idNSpace));
    }
    
    // -- INHERITED -- //
    // ++ STATUS ++ //

    public function IsOkay() {
	return ($this->ErrorNumber() == 0);
    }
    public function ErrorNumber() {
	return $this->NativeObject()->lastErrno();
    }
    public function ErrorString() {
	return $this->NativeObject()->lastError();
    }

    // -- STATUS -- //
    // ++ DATA PREPROCESSING ++ //
    
    /*----
      INPUT: non-NULL string value
      OUTPUT: string value with quotes escaped, but not quoted
    */
    public function SanitizeString($s) {
	throw new exception(__CLASS__.' does not support SanitizeString() (sanitize-without-quoting).');
	// ...because the MediaWiki interface upon which this all depends does not either.
    }
    /*----
      INPUT: any scalar value
      OUTPUT: non-blank SQL-compatible string that equates to the input value
	quoted if necessary
    */
    public function SanitizeValue($v) {
	return $this->NativeObject()->addQuotes($v);	// 2017-11-05 not sure if this behaves quite as expected
    }
    
    // -- DATA PREPROCESSING -- //
    // ++ DATA READ ++ //

    // NOTE: $nors is a mysqli_result
    public function Result_RowCount(fcDataRecord $rs) {
	$nors = $rs->GetDriverBlob();	// native object: recordset
	if (is_null($nors)) {
	    return 0;
	} else {
	    return $nors->num_rows;	// this may be the wrong call - try $this->NativeObject()->numRows($native)
	}
    }
    // NOTE: $nors is a mysqli_result
    public function Result_NextRow(fcDataRecord $rs) {
	//return $this->RetrieveDriver($rs)->fetch_assoc();	// again, may be wrong call; also RetrieveDriver() may not exist
	//$nodb = $this->NativeObject();	// native object: database
	$nors = $rs->GetDriverBlob();	// native object: recordset
	//$arRow = $nodb->fetchRow($nors);
	$arRow = $nors->fetch_assoc();
	return $arRow;
    }
    
    // -- DATA READ -- //
    // ++ DATA READ/WRITE ++ //

    public function ExecuteAction($sSQL) {
	$this->sql = $sSQL;
	return $this->NativeObject()->query($sSQL);
    }
    public function CountOfAffectedRows() {
    	return $this->NativeObject()->affectedRows();
    }
    public function CreatedID() {
	return $this->NativeObject()->insertId();
    }

    // -- DATA READ/WRITE -- //
    // ++ TRANSACTIONS ++ //
    
    public function TransactionOpen() {
	return $this->NativeObject()->begin();
    }
    public function TransactionSave() {
	return $this->NativeObject()->commit();
    }
    public function TransactionKill() {
	return $this->NativeObject()->rollback();
    }
    
    // -- TRANSACTIONS -- //
    // ++ CONVENTIONS ++ //

    /*----
      ACTION: Convert title into normalized DB-key format
	Surely there's some MW function which already does this...?
      
      NOTES:
	Tentatively:
	  $omw = Title::newFromText($iTitle,$iNameSpace);
	  $snTitle = $omw->getDBkey();
      TODO: try that ^
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

    public function FetchRecordset($sSQL,fiTable_wRecords $tbl) {
	$mwoRe = $this->NativeObject()->query($sSQL);	// $mwoRe is a ResultWrapper http://svn.wikimedia.org/doc/classResultWrapper.html
	$poRes = $mwoRe->result;			// $poRes is... I forget.
	return $tbl->ProcessResultset($poRes,$sSQL);	// return provisioned recordset
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
	$db = $this->NativeObject();

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

	$db = $this->NativeObject();
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
	$db = $this->NativeObject();

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

	$db = $this->NativeObject();
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
