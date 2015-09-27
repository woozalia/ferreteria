<?php
/*
  LIBRARY: admin.php - some classes useful for administration functions in MW extensions
  GLOBALS:
    $vgOut is usually set in menu.php
      It must be a descendant of clsRichText, which is defined in richtext.php
  HISTORY:
    2010-04-06
	clsAdminTable, clsAdminData, clsAdminData_Logged written (in menu.php)
    2010-10-25 clsAdminTable, clsAdminData extracted from menu.php
    2015-07-16 resolving conflicts with other edited version
*/
/*%%%%
  CLASS: clsAdminTable
  HISTORY:
    2010-10-04 Moved ActionKey() back into data.php, because it's useful for logging.
      No more need for this class; deprecated now.
    2010-10-19 ...except for AdminLink() (when did I add that?)
    2015-07-21 added clsAdminTable::AdminURL(), but it needs to be generalized to pull ID keys from the Table object
      ...and so does AdminLink, even more so.
*/
class clsAdminTable extends clsTable_key_single {
    protected function PageObject() {
	//return $this->Engine()->App()->Page();
	return SpecialPageApp::Me()->Page();
    }
    protected function BaseURL_rel() {
	return SpecialPageApp::Me()->BaseURL_rel();
    }
    public function AdminURL($sShow=NULL,$sPopup=NULL) {
	global $vgOut;

	$arLink = array(
	  'page'	=> $this->ActionKey(),
	  );
	//$out = $vgOut->SelfLink($arLink,$sShow,$sPopup);
	/*$oPage = $this->PageObject();
	if (!is_object($oPage)) {
	    throw new exception('Internal error: Asked for Page object, got '.gettype($oPage));
	}
	$urlBase = $oPage->BaseURL_rel();*/
	$urlBase = $this->BaseURL_rel();
	$urlAdd = clsURL::FromArray($arLink);
	//echo "BASE=[$urlBase] ADD=[$urlAdd] ";
	$out = $urlBase.'/'.$urlAdd;
	//echo "OUT=[$out]<br>"; die();
	return $out;
    }
    /*----
      USED BY: VbzAdmin::VbzAdminItems::AdminLink()
      HISTORY:
	Extricated from VbzAdmin::VbzAdminItems
    */
    public static function AdminLink($iID,$iShow=NULL,$iPopup=NULL) {
	global $vgOut;

	$id = $iID;

	$arLink = array(
	  'page'	=> 'item',
	  'id'		=> $id);
	$strShow = is_null($iShow)?($id):$iShow;
	$strPopup = is_null($iPopup)?('view item ID '.$id):$iPopup;
	$out = $vgOut->SelfLink($arLink,$strShow,$strPopup);
	return $out;
    }
}
/*=====
  HISTORY:
    2010-11-23 Added _AdminRedirect(), _AdminLink_array(), _AdminURL(), _ActionID()
  BOILERPLATES:
    public function AdminLink($iText=NULL,$iPopup=NULL,array $iarArgs=NULL) {
	return clsAdminData::_AdminLink($this,$iText,$iPopup,$iarArgs);
    }
    public function AdminRedirect(array $iarArgs=NULL) {
	return clsAdminData::_AdminRedirect($this,$iarArgs);
    }
*/
class clsAdminData extends clsRecs_key_single {
    /*====
      SECTION: static versions of dynamic functions
      PURPOSE: for classes which inherit from non-Admin classes (fake multiple inheritance)
    */
    static protected function _ActionID(clsRecs_keyed_abstract $iObj) {
	return $iObj->IsNew()?'new':$iObj->KeyString();
    }
    static protected function _AdminLink_array(clsRecs_keyed_abstract $iObj, $iarArgs=NULL) {
	$txtID = self::_ActionID($iObj);
	if (isset($iObj->Table->ActionKey)) {
	    $strKey = $iObj->Table->ActionKey;
	} else {
	    $strKey = $iObj->Table->Name();
	}

	$arLink = array(
	  'page'	=> $strKey,
	  'id'		=> $txtID
	  );

	if (is_array($iarArgs)) {	// 2010-06-25 This has not been tested -- maybe it's not useful?
	    $arLink = array_merge($arLink,$iarArgs);
	}
	return $arLink;
    }
    static public function _AdminURL(clsRecs_keyed_abstract $iObj, $iarArgs=NULL) {
	global $vgPage;

	$arLink = self::_AdminLink_array($iObj,$iarArgs);
	$out = $vgPage->SelfURL($arLink,TRUE);
	return $out;
    }
    /*----
      USED BY: VbzAdmin::clsAdminTopic::AdminLink()
      HISTORY:
	2010-10-06 Wasn't sure who is using this, so commented it out (not WorkFerret or AudioFerret)
	  VbzAdmin didn't *seem* to be using it...
	2010-10-13 Found that VbzAdmin uses it
	  Actually, this is the "boilerplate" function used by most clsDataSet descendants.
	2010-11-19 Added handling of new records.
	2012-03-31 If I added handling of new records in 2010, it must have gotten removed somehow.
	  Adding handling of new records again.
    */
    static public function _AdminLink(clsRecs_keyed_abstract $iObj, $iText=NULL,$iPopup=NULL,array $iarArgs=NULL) {
	global $vgOut;

	if (is_object($vgOut)) {
	    if ($iObj->IsNew()) {
		$out = NzArray($iarArgs,'text.new','?');
	    } else {
		$arLink = self::_AdminLink_array($iObj,$iarArgs);
		$txtID = self::_ActionID($iObj);
		$txtShow = is_null($iText)?($txtID):$iText;
		$out = $vgOut->SelfLink($arLink,$txtShow,$iPopup);
	    }

	    return $out;
	} else {
	    throw new exception('Need to initialize vgOut by calling vgPage->Use*()');
	}
    }
    static public function _AdminRedirect(clsRecs_keyed_abstract $iObj,array $iarArgs=NULL) {
	global $vgOut,$vgPage;
	global $wgOut;

	$url = self::_AdminURL($iObj,$iarArgs);
	$wgOut->redirect($url);
    }

    /*====
      SECTION: normal dynamic functions
    */
    /*----
      USED BY: VbzAdmin::VbzAdminStkItems::Listing_forItem()
      HISTORY:
	2010-10-06 Disabled, because it wasn't clear if anyone was using it.
	  Thought I checked VbzAdmin, WorkFerret, and AudioFerret
	2010-10-13 VbzAdmin::VbzAdminStkItems::Listing_forItem() calls it
    */
    public function AdminLink($iText=NULL,$iPopup=NULL,array $iarArgs=NULL) {
	return self::_AdminLink($this,$iText,$iPopup,$iarArgs);
    }
}
