<?php
/*
  PURPOSE: helper class for menu system
    manages action URLs
  HISTORY:
    2013-12-04 adapted from MediaWiki classes for standalone menu system
*/

/*%%%%
  HISTORY:
    2013-08-27 added _AdminLink_array(), _AdminURL(); modified _AdminLink() to use _AdminLink_array()
*/
class clsMenuData_helper {
    private $objRecs;
    private $strNewTxt;
    private $strNewKey;

//=====
// STATIC section
    static public function _AdminLink(clsRecs_keyed_abstract $iThis,$iText=NULL,$iPopup=NULL,array $arArgs=NULL) {
	$txtShow = is_null($iText)?($iThis->KeyString()):$iText;
	$objSelf = static::_Spawn();
	$objSelf->Recs($iThis);
// TODO: probably need to rethink this
	$arBase = $iThis->IdentityValues();
	$arAll = clsArray::Merge($arBase,$arArgs);

	$out = $objSelf->BuildLink($arAll,$iText,$iPopup);
	return $out;
    }
    static public function _AdminURL(clsRecs_keyed_abstract $iThis,array $iarArgs=NULL) {
	$arLink = static::_AdminLink_array($iThis,$iarArgs);
	$url = $iThis->Engine()->App()->Page()->SelfURL($arLink);
	return $url;
    }
    // this can be made public if needed
    static private Function _AdminLink_array(clsRecs_keyed_abstract $iThis,array $iarArgs=NULL) {
	if (isset($iThis->Table->ActionKey)) {
	    $sKeyAct = $iThis->Table->ActionKey;
	} else {
	    $sKeyAct = $iThis->Table->Name();
	}

	$sKeyVal = $iThis->IsNew()?'new':$iThis->KeyString();

	return self::_PageLink_array($sKeyAct,$sKeyVal,$iarArgs);
/*
	$arLink = is_array($iarArgs)?$iarArgs:array();
	$arLink = array_merge($arLink,array(
	  'page'	=> $sKeyAct,
	  'id'		=> $sKeyVal
	  ));
	return $arLink;
*/
    }
    static public function _PageLink_array($sPage,$id=NULL,array $iarArgs=NULL) {
	$arLink = is_array($iarArgs)?$iarArgs:array();
	$arLink = array_merge($arLink,array(
	  'page'	=> $sPage,
	  'id'		=> $id
	  ));
	return $arLink;
    }
    static public function _PageLink_URL($sPage,$id=NULL,array $iarArgs=NULL) {
	$ar = self::_PageLink_array($sPage,$id,$iarArgs);
	return clsURL::FromArray($ar);
    }
    private static function _ClassName() {
	return __CLASS__;
    }
    private static function _Spawn() {
	$strClass = static::_ClassName();
	$obj = new $strClass();
	return $obj;
    }
    /*----
      USED BY: VbzAdmin::VbzAdminItems::AdminLink()
      HISTORY:
	2013-01-04 function needed but not found; rewrote from scratch to use dynamic version
	2013-02-04 needed static version for WorkFerret -- wrote static version, with _ClassName() and Spawn()
	2014-02-21 changed $iRecs from single-keyed class to abstract-keyed class
    */
    public static function _AdminRedirect(clsRecs_keyed_abstract $iRecs,array $iarArgs=NULL,$sText=NULL) {
	$objSelf = static::_Spawn();
	$objSelf->Recs($iRecs);
	return $objSelf->AdminRedirect($iarArgs,$sText);
    }
//=====
// DYNAMIC section

    public function __construct() {
	$this->strNewTxt = 'new';
	$this->strNewKey = '';
    }
    /*----
      RETURNS: appropriate key value, even if record is new
      TODO: possibly this should be renamed
    */
    protected function ActionID() {
	$objRecs = $this->Recs();
	$sKeyNew = $this->NewKey();
	$sKeyStr = $objRecs->KeyString();
	return $objRecs->IsNew()?$sKeyNew:$sKeyStr;
    }
    /*----
      PURPOSE: r/w access method for the text to display for new record ID links
    */
    public function NewText($iStr=NULL) {
	if (!is_null($iStr)) {
	    $this->strNewTxt = $iStr;
	}
	return $this->strNewTxt;
    }
    /*----
      PURPOSE: r/w access method for the ID value to use for new record ID links
      RULES:
	* If set to an empty string, no link will be generated
    */
    public function NewKey($iStr=NULL) {
	if (!is_null($iStr)) {
	    $this->strNewKey = $iStr;
	}
	return $this->strNewKey;
    }
    /*----
      HISTORY:
	2014-02-21 changed $iRecs from single-keyed class to abstract-keyed class
    */
    public function Recs(clsRecs_keyed_abstract $iRecs=NULL) {
	if (!is_null($iRecs)) {
	    $this->objRecs = $iRecs;
	}
	return $this->objRecs;
    }
    protected function AdminLink_array($iarArgs=NULL) {
	$arLink = self::_AdminLink_array($this->Recs(),$iarArgs);
	return $arLink;
    }
    /*----
      USED BY:
	* (static) VbzAdmin::clsAdminTopic::AdminLink() -- may need some rewriting
	* (dynamic) VbzAdmin::VbzAdminStkItems::Listing_forItem()
      HISTORY:
	2010-10-06 (both) Wasn't sure who is using this, so commented it out (not WorkFerret or AudioFerret)
	  VbzAdmin didn't *seem* to be using it... (dynamic) Thought I checked VbzAdmin, WorkFerret, and AudioFerret
	2010-10-13 (static) Found that VbzAdmin uses it
	  Actually, this is the "boilerplate" function used by most clsDataSet descendants.
	2010-10-13 (dynamic) VbzAdmin::VbzAdminStkItems::Listing_forItem() calls it
	2010-11-19 (static) Added handling of new records.
	2012-03-31 (static) If I added handling of new records in 2010, it must have gotten removed somehow.
	  Adding handling of new records again.
	2012-12-31 After moving this code to clsAdminData_helper, modifying new-record handling
	  to use object field values. Also, no longer any need for a static function, so merging
	  with dynamic version.
      TODO: This should probably be deprecated.
    */
    public function AdminLink($iText=NULL,$iPopup=NULL,array $iarArgs=NULL) {
	$txtID = $this->ActionID();

	$arLink = $this->AdminLink_array($iarArgs);
	$txtShow = is_null($iText)?($txtID):$iText;

	$url = $this->Recs()->Engine()->App()->Page()->SelfURL_basic($arLink);

	$out = clsHTML::BuildLink($url,$txtShow,$iPopup);

	return $out;
    }
    /*----
      ACTION: create a link from the given parameters
	URL: derived from $arArgs
	Text: sText if set, otherwise the current ActionID
	Title (popup): $sPopup if set
    */
    public function BuildLink(array $arArgs,$sText=NULL,$sPopup=NULL) {
	$sID = $this->ActionID();
	$urlBase = $this->Recs()->Engine()->App()->BaseURL();
	$uriPage = clsURL::FromArray($arArgs);
	$url = $urlBase.'/'.$uriPage;
	if (is_null($sText)) {
	    $sText = $this->ActionID();
	}
	$htTitle = is_null($sPopup)?'':(' title="'.htmlspecialchars($sPopup).'"');
	return "<a href='$url'$htTitle>$sText</a>";
    }
    protected function RecURL() {
	$arArgs = NULL;
	if (method_exists($this->Recs(),'Value_IdentityKeys')) {
	    $rc = $this->Recs();
	    $arKeys = $rc->Value_IdentityKeys();
	    if (is_array($arKeys)) {
		$oPage = $rc->Engine()->App()->Page();
		foreach ($arKeys as $key) {
		    $arArgs[$key] = $oPage->PathArg($key);
		}
	    } else {
		echo '<b>Error</b>: $arKeys is not an array; value=['.$arKeys.'], recordset class=['.get_class($rc).'].';
		throw new exception('Internal error: $arKeys is not an array.');
	    }
	}
	// NOTE: This will choke when next used in Ferreteria's App framework -- but we just need to write SelfURL_extend().
	$url = $this->Recs()->Engine()->App()->Page()->SelfURL_extend($arArgs);
	return $url;
    }
    /*----
      HISTORY:
	2012-12-31 After moving code to Helper, making this dynamic
	2013-01-04 Do we need both this and the static _AdminLink() method? How are they different?
	2014-06-10 $arArgs is now IGNORED
    */
    public function AdminRedirect(array $arArgs=NULL,$sText=NULL) {
	clsHTTP::DisplayOnReturn($sText);
	$url = $this->RecURL();
	echo 'REDIRECTING to '.$url.' and saving the following text:<br>'.$sText;
	clsHTTP::Redirect($url,array(),FALSE,HTTP_REDIRECT_POST);
	die();	// don't do any more work (including accidentally erasing the cookie)
    }
}
