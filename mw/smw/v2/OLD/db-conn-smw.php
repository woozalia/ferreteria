<?php
/*
  PURPOSE: Semantic MediaWiki interface classes
  VERSION: SMW v2
    The existing class library is poorly documented, lacking a stable API, and difficult to use.
    This class set goes directly to the data structures -- which may change over time, but the changes
      should be easier to puzzle out than changes to the SMW class library.
  REQUIRES: Ferreteria db
  HISTORY:
    2012-01-22 started
    2012-09-17 useful pieces working
    2014-12-15 no longer invoking config-libs from library files
    2015-03-19 modified to use Ferreteria db v2
*/

//define('SMW_NS_PROPERTY','Property');	// this is wrong

/*====
  PURPOSE: SMW-specific data functions
*/
class fcDataConn_SMW extends fcDataConn_MW {

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

	// look up property name's SMW ID
	$sqlProp = $this->Sanitize_andQuote(self::NormalizeTitle($sPropName,SMW_NS_PROPERTY));
	$sql = "SELECT smw_id FROM smw_ids WHERE (smw_title=$sqlProp) LIMIT 1;";
	$rs = $this->Recordset($sql);
	if ($rs->HasRows()) {
	    $rs->NextRow();	// should be only one row -- get it.
	    $idProp = $rs->FieldValue('smw_id');
	} else {
	    return NULL;	// property does not exist anywhere
	}
	
	// look up value's SMW ID (it might not exist in this table)

	$sqlVal = $this->Sanitize_andQuote(self::NormalizeTitle($sPropValue,SMW_NS_PROPERTY));
	$sql = "SELECT smw_id FROM smw_ids WHERE (smw_title=$sqlVal) LIMIT 1;";
	$rs = $this->Recordset($sql);
	$isFound = $rs->HasRows();	// this will be updated by subsequent operations
	if ($isFound) {
	    $rs->NextRow();	// should be only one row -- get it.
	    $idVal = $rs->FieldValue('smw_id');

	// find all pages where that property is set

	// start with smw_rels2, which is properties that are titles (the default kind of property)
	// there are probably other tables we need to check, but this will do for immediate needs.

	// if property value is not found, then we can rule out RELS -- only check TEXT and ATTS
	
	    $sql = 'SELECT s_id,'
	      .' s.smw_namespace AS s_namespace'
	      .', CAST(s.smw_title AS char) AS s_title'
	      //.', CAST(p.smw_title AS char) AS p_title'
	      //.', CAST(o.smw_title AS char) AS o_title'
	      .' FROM'
		.' (smw_rels2 AS r'
		.' LEFT JOIN smw_ids AS s ON r.s_id=s.smw_id)'
		//.' LEFT JOIN smw_ids AS p ON r.p_id=p.smw_id)'
		.' LEFT JOIN smw_ids AS o ON r.o_id=o.smw_id'
	      ." WHERE (o_id=$idVal) AND (p_id=$idProp);";
	    $rs = $this->Recordset($sql);
	    $isFound = $rs->HasRows();
	    if ($isFound) {
		$arOut = array();
		while ($rs->NextRow()) {
		    $idPage = $rs->FieldValue('s_id');
		    $arOut[$idPage] = $rs->FieldValues();
		}
		return $arOut;
	    }
	    // if the property was initially a page-type and was later changed, we might get here and still not find the ID
	}
	if (!$isFound) {
	    // 2. check ATTS to see if the value is there:
	    $sql = 'SELECT s_id,'
	      .' s.smw_namespace AS s_namespace'
	      .	', CAST(s.smw_title AS char) AS s_title'
	      .	', CAST(a.value_num AS char) AS a_num'
	      .	', CAST(a.value_xsd AS char) AS a_xsd'
	      .' FROM'
	      .	' (smw_atts2 AS a'
	      .	' LEFT JOIN smw_ids AS s ON a.s_id=s.smw_id)'
	      .	' LEFT JOIN smw_ids AS p ON a.p_id=p.smw_id'
	      ." WHERE (a.value_xsd=$sqlVal) AND (p_id=$idProp);";
	    $rs = $this->Recordset($sql);
	    $isFound = $rs->HasRows();
	    if ($isFound) {
		$arOut = array();
		while ($rs->NextRow()) {
		    $idPage = $rs->FieldValue('s_namespace').':'.$rs->FieldValue('s_id');
		    $arOut[$idPage] = $rs->FieldValues();
		}
		return $arOut;
	    }

	    // if we get to here, then it wasn't in ATTS; try TEXT:

	    // nothing worked
	    return NULL;
	}
    }
}

class w3smwPage {
    private $objPageEnv;	// wiki page environment object (from w3tpl)
    private $mwoTitle;		// MediaWiki Title object, if set

    public function __construct(w3tpl_module $iPageEnv) {
	$this->objPageEnv = $iPageEnv;
    }
    /*----
      RETURNS: database engine from the wiki page environment
    */
    protected function Engine() {
	return $this->PageEnv()->Engine();
    }
    protected function PageEnv() {
	return $this->objPageEnv;
    }

    public function Use_TitleObject(Title $iTitle) {
	$this->mwoTitle = $iTitle;
    }
    public function MW_Object() {
	return $this->mwoTitle;
    }
    public function Use_GlobalTitle() {
	global $wgTitle;
	$this->Use_TitleObject($wgTitle);
    }
    public function Use_Title_Named($iName) {
	$mwoTitle = Title::newFromDBkey($iName);
	$this->Use_TitleObject($mwoTitle);
    }
    public function Use_Title_Keyed($iName,$iSpace=NS_MAIN) {
	$mwoTitle = Title::newFromText($iName,$iSpace);
	$this->Use_TitleObject($mwoTitle);
    }
    /*----
      INPUT: an array in the format returned by GetPages_forPropVal()
    */
    public function Use_Title_Keyed_array(array $iar) {
	$this->Use_Title_Keyed($iar['s_title'],$iar['s_namespace']);
    }
    public function TitleKey() {
	return $this->mwoTitle->getDBkey();
    }
    public function TitleShown() {
	return $this->mwoTitle->getText();
    }
    public function TitleFull() {
	return $this->mwoTitle->getPrefixedText();
    }
    public function Nspace() {
	return $this->mwoTitle->getNamespace();
    }
    /*----
      TODO:
	* Translate special properties such as "Date" -> "_dat".
	  Is there a formal list somewhere?
	Do we have to always check all three tables, or can we assume that
	  if a value is found in one table, it isn't in the others?
    */
    public function GetPropData($iPropName) {
	$strPageKey = $this->TitleKey();
	$strPropKey = Title::capitalize($iPropName,SMW_NS_PROPERTY);
	$sqlPageKey = $this->Engine()->Sanitize_andQuote($strPageKey);
	$sqlPropKey = $this->Engine()->Sanitize_andQuote($strPropKey);

	$intNSpace = (int)$this->Nspace();

	// PART ONE: smw_atts2
	$sql = 'SELECT value_xsd, value_num'
	  .' FROM (smw_atts2 AS a'
	  .' LEFT JOIN smw_ids AS s ON a.s_id=s.smw_id)'
	  .' LEFT JOIN smw_ids AS p ON a.p_id=p.smw_id'
	  .' WHERE'
	  ." (p.smw_title = $sqlPropKey) AND"
	  ." (s.smw_title = $sqlPageKey) AND"
	  ." (s.smw_namespace = $intNSpace);";
	$rs = $this->Engine()->Recordset($sql);
	$arOut['atts'] = $rs;

	// PART TWO: smw_rels2
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
	$rs = $this->Engine()->Recordset($sql);
	$arOut['rels'] = $rs;

	// PART THREE: smw_text2
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
	$rs = $this->Engine()->Recordset($sql);
	$arOut['text'] = $rs;
	return $arOut;
    }
    /*----
      RETURNS: array[smw_id] = value
      ASSUMES: smw_sortkey is the non-underscored version of smw_title
      USAGE: when multiple values are expected to happen sometimes
    */
    public function GetPropVals($iPropName) {
	$ar = $this->GetPropData($iPropName);
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
	}

	// next check RELS
	$rs = $ar['rels'];
	if ($rs->HasRows()) {
	    while ($rs->NextRow()) {
		$idx++;
		$strVal = $rs->FieldValue('o_title');
		$out[$idx] = fcDataConn_MW::VisualizeTitle($strVal);
	    }
	}

	// next check TEXT
	$rs = $ar['text'];
	if ($rs->HasRows()) {
	    while ($rs->NextRow()) {
		$idx++;
		$strVal = $rs->FieldValue('value');
		$out[$idx] = $strVal;
	    }
	}

	return $out;
    }
    /*----
      RETURNS: array if multiple values found, otherwise just the value string
      ASSUMES: smw_sortkey is the non-underscored version of smw_title
      USAGE: when there's no reason to expect multiple values
    */
    public function GetPropVal($iPropName) {
	$ar = $this->GetPropVals($iPropName);
	$cnt = count($ar);
	if ($cnt > 1) {
	    return $ar;
	} elseif ($cnt == 1) {
	    return array_shift($ar);	// return just the first element
	} else {
	    return NULL;	// nothing found
	}
/*
	$rs = $this->GetPropData($iPropName);
	if ($rs->HasRows()) {
	    if ($rs->RowCount() == 1) {
		$rs->NextRow();	// load the first row
		$strVal = $rs->Value('value_xsd');
		return $strVal;
	    } else {
		while ($rs->NextRow()) {
		    $strVal = $rs->Value('value_xsd');
		    $out[$id] = $strVal;
		}
		return $out;
	    }
	} else {
	    return NULL;	// problem with the query
	}
*/
    }
    /*----
      RETURNS: nicely-formatted list of property values with links
	Sometimes it's just easier to use the existing code.
	This is just GetProperty_OLD() with the no-link option removed.
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
    /*----
      NOTE: This is the old klugey version of GetPropLinks(), which I'm keeping here (for now) for posterity.
    */
    public function GetProperty_OLD($iPropName,array $iarOptions=NULL) {
	$strPgTitle = $this->TitleKey();

	$arArgs = array($strPgTitle,'?'.$iPropName);

	if (is_array($iarOptions)) {
	    $doLink = NzArray($iarOptions,'link');
	} else {
	    $doLink = FALSE;
	}
	if (!$doLink) {
	    $arArgs[] = 'link=none';
	    // without this, SMW defaults to using links
	}

	// get list of targets (usually just one, but could be more)
	$htVal = SMWQueryProcessor::getResultFromFunctionParams(
	  $arArgs,
	  SMW_OUTPUT_FILE,
	  SMWQueryProcessor::INLINE_QUERY,
	  TRUE);	// treat as if #show (rather than #ask)
	return $htVal;
    }
}

class tblSMW_PageProps extends fcDataTable_indexed {

    // ++ SETUP ++ //

    protected function DefaultTableName() {
	return 'smw_text2';
    }
    protected function DefaultSingularName() {
	return 'rcSMW_PageProp';
    }
    
    // -- SETUP -- //
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

