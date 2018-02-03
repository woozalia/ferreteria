<?php
/*
  PURPOSE: Semantic MediaWiki interface classes
    The existing class library is poorly documented, lacking a stable API, and difficult to use.
    This class set goes directly to the data structures -- which may change over time, but the changes
      should be easier to puzzle out than changes to the SMW class library.
  VERSION: SMW v2
  REQUIRES: Ferreteria db
  HISTORY:
    2012-01-22 started
    2012-09-17 useful pieces working
    2014-12-15 no longer invoking config-libs from library files
    2015-03-19 modified to use Ferreteria db v2
    2018-01-23 tweaks in a probably futile attempt to make the code still run in psycrit
    2018-01-24 renamed w3smwPage to fcPageData_SMW
      No longer requires a w3tpl Module in order to get the database.
    2018-01-26 apparently this is still using SMW's v2 schema, but it has been modified to work with current Ferreteria,
      so I am copying it over to the SMWv2 folder before rewriting for SMW schema v3.
*/
//if (!defined('SMW_NS_PROPERTY')) {
//    define('SMW_NS_PROPERTY',102);	// just for debugging without SMW actually installed; should be commented out later
//}

/*::::
  PURPOSE: SMW-specific data functions
*/
class fcDataConn_SMW extends fcDataConn_MW {

    public function GetObjectID($sName) {
	$sqlKey = $this->Sanitize_PageTitle($sName,SMW_NS_PROPERTY);
	$sql = "SELECT smw_id FROM smw_object_ids WHERE (smw_title=$sqlKey) LIMIT 1;";
	$t = $this->MakeTableWrapper('fcUsableTable');
	//$rs = $this->Recordset($sql);
	$rs = $t->FetchRecords($sql);
	if ($rs->HasRows()) {
	    $rs->NextRow();	// should be only one row -- get it.
	    $idObj = $rs->GetFieldValue('smw_id');
	} else {
	    $idObj = NULL;
	}
	return $idObj;
    }

    /*----
      RETURNS: array of page names where the given property has the given value
	(or, if iPropValue is left out, just having the given property)
	Each array element contains the following in an array, keyed to "s_namespace:s_id":
	  * s_id
	  * s_namespace
	  * s_title
	NULL if property is not used anywhere (or possibly if it has never been used -- not sure if disused properties are kept)
      INPUT:
	$iPropName: name of a property to be searched
	$iPropValue: property value to be searched for
	$iPage (optional): page object to be loaded
      USED BY: psycrit.w3f_Show_Response_Header()
      HISTORY:
	2012-11-01 revised to look up SMW ID first, reducing CPU. Return array format has SMW ID as key.
	2012-12-17 look up property's type first, to find out which table to check for the value
    */
    public function GetPages_forPropVal($sPropName,$sPropValue) {

	$idProp = $this->GetObjectID($sPropName); 	// look up property's SMW ID
	if (is_null($idProp)) {
	    return NULL;	// property not found; no pages
	} else {
	    $idVal = $this->GetObjectID($sPropValue);	// look up value's SMW ID
	    
	// find all pages where that property is set

	    // start with smw_di_wikipage, which is properties that are page titles (the default kind of property)
	    // there are probably other tables we need to check, but this will do for immediate needs.

	    $sql = 'SELECT s_id,'
	      .' s.smw_namespace AS s_namespace'
	      .', CAST(s.smw_title AS char) AS s_title'
	      .' FROM'
		.' (smw_di_wikipage AS r'
		.' LEFT JOIN smw_object_ids AS s ON r.s_id=s.smw_id)'
		.' LEFT JOIN smw_object_ids AS o ON r.o_id=o.smw_id'
	      ." WHERE (o_id=$idVal) AND (p_id=$idProp);";
	    $t = $this->MakeTableWrapper('fcUsableTable');
	    //$rs = $this->Recordset($sql);
	    $rs = $t->FetchRecords($sql);
	    if ($rs->HasRows()) {
		$arOut = array();
		while ($rs->NextRow()) {
		    $idPage = $rs->GetFieldValue('s_id');
		    $arOut[$idPage] = $rs->GetFieldValues();
		}
		return $arOut;
	    } else {
		// TODO: some kind of indication that the property was found but no pages matched
		return NULL;	// nothing found
	    }
	}
    }
}

class fcPageData_SMW extends fcPageData_MW {
    //private $objPageEnv;	// wiki page environment object (from w3tpl)

    /*
    public function __construct(xcModule $iPageEnv) {
	$this->objPageEnv = $iPageEnv;
    }*/
    /*----
      RETURNS: database engine from the wiki page environment
    */
    /*
    protected function Engine() {
	return $this->PageEnv()->Engine();
    }
    protected function PageEnv() {
	return $this->objPageEnv;
    }
    */
    
    // ++ FRAMEWORK ++ //
    
    protected function GetDatabase() {
	return fcApp::Me()->GetDatabase();
    }
    
    private $mwoTitle = NULL;
    protected function GetTitleObject() {
	global $wgTitle;
	
	if (is_null($this->mwoTitle)) {
	    $this->mwoTitle = $wgTitle;
	}
	return $this->mwoTitle;
    }
    protected function SetTitleObject(Title $mwo) {	// 2018-01-24 presume this will be needed at some point
	$this->mwoTitle = $mwo;
    }
    
    // -- FRAMEWORK -- //
    // ++ TABLES ++ //
    
    protected function AttributesQuery() {
	return $this->GetDatabase()->MakeTableWrapper('fctqSMW_Attributes');
    }
    protected function RelationshipsQuery() {
	return $this->GetDatabase()->MakeTableWrapper('fctqSMW_Relationships');
    }
    protected function TextBlobsQuery() {
	return $this->GetDatabase()->MakeTableWrapper('fctqSMW_TextBlobs');
    }
    
    /*----
      INPUT: an array in the format returned by GetPages_forPropVal()
    */
    public function Use_Title_Keyed_array(array $iar) {
	$this->Use_Title_Keyed($iar['s_title'],$iar['s_namespace']);
    }
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
    /*----
      TODO:
	* UPGRADE to SMW schema v3
	* Translate special properties such as "Date" -> "_dat".
	  Is there a formal list somewhere?
	Do we have to always check all three tables, or can we assume that
	  if a value is found in one table, it isn't in the others?
    */
    public function GetPropData($iPropName) {
	$db = $this->GetDatabase();
    
	//throw new exception('GetPropData() uses the v2 tables and needs to be either deprecated or updated to v3.');
	$strPageKey = $this->TitleKey();
	$strPropKey = Title::capitalize($iPropName,SMW_NS_PROPERTY);
	$sqlPageKey = $db->SanitizeValue($strPageKey);
	$sqlPropKey = $db->SanitizeValue($strPropKey);

	$intNSpace = (int)$this->Nspace();

	// PART ONE: smw_atts2
	/*
	$sql = 'SELECT value_xsd, value_num'
	  .' FROM (smw_atts2 AS a'
	  .' LEFT JOIN smw_ids AS s ON a.s_id=s.smw_id)'
	  .' LEFT JOIN smw_ids AS p ON a.p_id=p.smw_id'
	  .' WHERE'
	  ." (p.smw_title = $sqlPropKey) AND"
	  ." (s.smw_title = $sqlPageKey) AND"
	  ." (s.smw_namespace = $intNSpace);"; */
	$sqlFilt = 
	  " (p.smw_title = $sqlPropKey) AND"
	  ." (s.smw_title = $sqlPageKey) AND"
	  ." (s.smw_namespace = $intNSpace);"
	  ;

	$t = $this->AttributesQuery();
	$rs = $t->SelectRecords($sqlFilt);
	$arOut['atts'] = $rs;

	// PART TWO: smw_rels2
	/* 2018-01-25 old
	$sql = 'SELECT'
	  .' r.*,'
	  .' CAST(s.smw_title AS char) AS s_title,'
	  .' CAST(p.smw_title AS char) AS p_title,'
	  .' CAST(o.smw_title AS char) AS o_title'
	  .' FROM'
	  .' ((smw_rels2 AS r'
	  .' LEFT JOIN smw_ids AS s ON r.s_id=s.smw_id)'
	  .' LEFT JOIN smw_ids AS p ON r.p_id=p.smw_id)'
	  .' LEFT JOIN smw_ids AS o ON r.o_id=o.smw_id'
	  .' WHERE'
	  ." (p.smw_title = $sqlPropKey) AND"
	  ." (s.smw_title = $sqlPageKey) AND"
	  ." (s.smw_namespace = $intNSpace);";
	$rs = $db->Recordset($sql);
	*/
	$sqlFilt = 
	  " (p.smw_title = $sqlPropKey) AND"
	  ." (s.smw_title = $sqlPageKey) AND"
	  ." (s.smw_namespace = $intNSpace);"
	  ;
	$t = $this->RelationshipsQuery();
	$rs = $t->SelectRecords($sqlFilt);
	
	$arOut['rels'] = $rs;

	// PART THREE: smw_text2
	/* 2018-01-26 old
	$sql = 'SELECT'
	  .' s_id,'
	  .' p_id,'
	  .' CAST(s.smw_title AS CHAR) AS s_title,'
	  .' CAST(p.smw_title AS CHAR) AS p_title,'
	  .' CAST(value_blob AS CHAR) AS value'
	  .' FROM (smw_text2 AS t'
	  .' LEFT JOIN smw_ids AS s ON t.s_id=s.smw_id)'
	  .' LEFT JOIN smw_ids AS p ON t.p_id=p.smw_id'
	  .' WHERE'
	  ." (p.smw_title = $sqlPropKey) AND"
	  ." (s.smw_title = $sqlPageKey) AND"
	  ." (s.smw_namespace = $intNSpace);";
	$rs = $db->Recordset($sql);
	*/
	$sqlFilt =
	  "(p.smw_title = $sqlPropKey) AND"
	  ." (s.smw_title = $sqlPageKey) AND"
	  ." (s.smw_namespace = $intNSpace);"
	  ;
	$t = $this->TextBlobsQuery();
	$rs = $t->SelectRecords($sqlFilt);
	//echo "SQL=".$rs->sql.'<br>';
	
	$arOut['text'] = $rs;
	return $arOut;
    }
    /*----
      RETURNS: array[smw_id] = value
      ASSUMES: smw_sortkey is the non-underscored version of smw_title
      USAGE: when multiple values are expected to happen sometimes
    */
    public function GetPropVals($sPropName) {
	$ar = $this->GetPropData($sPropName);
//	echo "PROP DATA FOR [$sPropName]:".fcArray::Render($ar);
	$out = NULL;
	$idx = 0;

	// first check ATTS
	$rs = $ar['atts'];
	if ($rs->HasRows()) {
	    while ($rs->NextRow()) {
		$idx++;
		$strVal = $rs->FieldValue('value_xsd');
		$out[$idx] = $strVal;
	    }
	} else {
	    //echo "NO ATTS FOUND<br>";
	}

	// next check RELS
	$rs = $ar['rels'];
	if ($rs->HasRows()) {
	    while ($rs->NextRow()) {
		$idx++;
		$strVal = $rs->FieldValue('o_title');
		$out[$idx] = fcDataConn_MW::VisualizeTitle($strVal);
	    }
	} else {
	    //echo "NO RELS FOUND<br>";
	}

	// next check TEXT
	$rs = $ar['text'];
	if ($rs->HasRows()) {
	    while ($rs->NextRow()) {
		$idx++;
		$strVal = $rs->FieldValue('value');
		$out[$idx] = $strVal;
	    }
	} else {
	    //echo "NO TEXT FOUND<br>";
	}

	return $out;
    }
    /*----
      RETURNS: array if multiple values found, otherwise just the value string
      ASSUMES: smw_sortkey is the non-underscored version of smw_title
      USAGE: when there's no reason to expect multiple values
    */
    public function GetPropVal($sPropName) {
	$ar = $this->GetPropVals($sPropName);
	$cnt = count($ar);
	if ($cnt > 1) {
	    return $ar;
	} elseif ($cnt == 1) {
	    return array_shift($ar);	// return just the first element
	} else {
	    return NULL;	// nothing found
	}
    }
    /*----
      RETURNS: nicely-formatted list of property values with links
    */
    public function GetPropLinks($iPropName) {
	$strPgTitle = $this->TitleKey();

	$arArgs = array($strPgTitle,'?'.$iPropName);

	// get list of targets (usually just one, but could be more)
	$htVal = SMWQueryProcessor::getResultFromFunctionParams(
	  $arArgs,
	  SMW_OUTPUT_FILE,
	  SMWQueryProcessor::INLINE_QUERY,
	  TRUE);	// treat as if #show (rather than #ask)
	return $htVal;
    }
}

class fctSMW_PageProps extends fcTable_keyed {

    // ++ SETUP ++ //

    protected function TableName() {
	return 'smw_text2';
    }
    // CEMENT
    protected function SingularName() {
	return 'fcrSMW_PageProp';
    }
    
    // -- SETUP -- //
}

class fctqSMW_Attributes extends fcTable_wSource_wRecords {
    use ftSelectable_Table, ftReadableTable;

    // CEMENT
    protected function SingularName() {
	return 'fcrqSMW_Attribute';
    }
    // OVERRIDE
    protected function FieldsString_forSelect() {
	return 'value_xsd, value_num';
    }
    // OVERRIDE
    protected function SourceString_forSelect() {
	return '(smw_atts2 AS a'
	  .' LEFT JOIN smw_ids AS s ON a.s_id=s.smw_id)'
	  .' LEFT JOIN smw_ids AS p ON a.p_id=p.smw_id'
	  ;
    }
}
class fcrqSMW_Attribute extends fcDataRecord {
}
class fctqSMW_Relationships extends fcTable_wSource_wRecords {
    use ftSelectable_Table, ftReadableTable;
    
    // CEMENT
    protected function SingularName() {
	return 'fcrqSMW_Relationship';
    }
    // OVERRIDE
    protected function FieldsString_forSelect() {
	return 'r.*';
    }
    // OVERRIDE
    protected function SourceString_forSelect() {
	return '((smw_rels2 AS r'
	  .' LEFT JOIN smw_ids AS s ON r.s_id=s.smw_id)'
	  .' LEFT JOIN smw_ids AS p ON r.p_id=p.smw_id)'
	  .' LEFT JOIN smw_ids AS o ON r.o_id=o.smw_id'
	  ;
    }
    
}
class fcrqSMW_Relationship extends fcDataRecord {
}
class fctqSMW_TextBlobs extends fcTable_wSource_wRecords {

    use ftSelectable_Table, ftReadableTable;
    // CEMENT
    protected function SingularName() {
	return 'fcrqSMW_TextBlob';
    }
    // OVERRIDE
    protected function FieldsString_forSelect() {
	return 
	    's_id,'
	  .' p_id,'
	  .' CAST(s.smw_title AS CHAR) AS s_title,'
	  .' CAST(p.smw_title AS CHAR) AS p_title,'
	  .' CAST(value_blob AS CHAR) AS value'
	  ;
    }
    // OVERRIDE
    protected function SourceString_forSelect() {
	return '(smw_text2 AS t'
	  .' LEFT JOIN smw_ids AS s ON t.s_id=s.smw_id)'
	  .' LEFT JOIN smw_ids AS p ON t.p_id=p.smw_id'
	  ;
    }
}
class fcrqSMW_TextBlob extends fcDataRecord {
}
/*
 2012-08-13 This function was actually written for InstaGov, but appears to represent a significant amount of time-investment in
  figuring out how to access SMW data. I don't need it for IG anymore (for now) because I'm doing things differently there now,
  but it could well be useful for creating functions here in smw-base.
*/
/*-----
  TAG: <igov>
  PARAMS:
    list=answers
    target=(name of page)
    name=(name of output array for each row)
    vpage=[name of var in which to store each page name]
*/
function efIGov( $input, $args, $parser ) {
    global $iggProperty_forResponses,$iggProperty_namespace;

    $objArgs = new W3HookArgs($args);

    if ($objArgs->Exists('list')) {
	if ($objArgs->Exists('name')) {
	    $strNameRaw = $objArgs->GetArgVal('name');
	    $strName = trim($strNameRaw);
	    $objVar = new clsW3VarName();
	    $objVar->ParseName($strName);	// resolve any indirection (e.g. $var)
	} else {
	    $out = '[must set output array name using <b>name=</b>]';
	    return;
	}
	$dbr =& wfGetDB( DB_SLAVE );	// read-only db object

// this SQL (when finished) gets a list of pages with the SMW properties we're looking for
	$sqlSMW = 'SELECT
s.smw_title AS s_t,
s.smw_namespace AS s_ns,
pg.page_id
FROM
(((smw_rels2 AS x 
  LEFT JOIN smw_ids AS s ON x.s_id=s.smw_id)
  LEFT JOIN smw_ids as p ON x.p_id=p.smw_id)
  LEFT JOIN smw_ids AS o ON x.o_id=o.smw_id)
  LEFT JOIN page AS pg ON ((s.smw_namespace=pg.page_namespace) AND (s.smw_title=pg.page_title))
 WHERE 
(p.smw_title="'.$iggProperty_forResponses.'") AND
(p.smw_namespace='.$iggProperty_namespace.') AND 
';
	$strType = $objArgs->GetVal('list');
	$strTarg = $objArgs->GetVal('target');
	$strPgVar = $objArgs->GetVal('vpage');
	$doHide = isset($args['hide']);	// TRUE = only output <echo> sections
	$objTarg = Title::newFromText($strTarg);
	$sqlTarg = SQLValue($objTarg->getDBkey());
	$intNS = $objTarg->mNamespace; // is there not a function for this?
	switch($strType) {
	  case 'answers':
	    $sqlFiltAdd = '(o.smw_title='.$sqlTarg.') AND (o.smw_namespace='.$intNS.')';
	    break;
	  default:
	    $out = '[unknown list type]';
	}
	$sqlFull = $sqlSMW.$sqlFiltAdd;
	try {
	    $res = $dbr->query($sqlFull);
	}
	catch (Exception $e) {
	    $out = "W3TPL encountered a database error - ''".$dbr->lastError()."'' - from this SQL:<pre>$sqlFull</pre>";
	    return $parser->recursiveTagParse($out);
	}
	$fPerRow = function($iRow) use($parser,$strName,$dbr,$objVar,$strPgVar) {
	    global $wgW3_data;

	    $idPage = $iRow->page_id;

	    $sqlMW = 'SELECT pp_propname, pp_value FROM page_props WHERE pp_page=';
	    $sqlFull = $sqlMW.$idPage;

	    $out = NULL;
	    try {
		$res = $dbr->query($sqlFull);
	    }
	    catch (Exception $e) {
		$out = "W3TPL encountered a database error - ''".$dbr->lastError()."'' - from this SQL:<pre>$sqlFull</pre>";
		return $parser->recursiveTagParse($out);
	    
	    }
	    $objPage = Title::newFromID($idPage);
	    $objVar->Name = $strPgVar;
	    $objVar->Value = $objPage->getPrefixedText();
	    $objVar->Store();
	    while( $row = $dbr->fetchObject ( $res ) ) {
		$strKey = $row->pp_propname;
		$txtVal = $row->pp_value;
		$objVar->Name = $strKey;
		$objVar->Value = $txtVal;
		$objVar->Store();
	    }

	    return $out;
	};
	$out = ProcessRows($dbr,$res,$strName,$parser,$input,$doHide,$fPerRow);
    }
/* probably not using this after all
	if (isset($args['type'])) {
	    $strType = $args['type'];
	    switch($strType) {
	      case 'question':
		$out = '[question is {'.$input.'}]';
		break;
	      case 'answer':
		$out = '[rnsr is {'.$input.'}]';
		break;
	      default:
		$out = '[unknown type]';
	    }
	} else {
	    $out = '[type not specified]';
	}
*/
    return $out;
}

