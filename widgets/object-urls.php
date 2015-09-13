<?php
/*
  PURPOSE: helper class for menu system
    manages action URLs
  HISTORY:
    2013-12-04 adapted from MediaWiki classes for standalone menu system
    2015-05-08 MW data record classes have no App() method, so resorting to static call in _AdminURL() and BuildLink();
    2015-07-12 resolving conflicts with other edited version
    2015-08-11 resolving conflicts with other edited version again
      How did this even happen, since I've only been editing here on cloud2?
    2015-09-05 New class: fcMenuTable_helper
      * TODO: Should probably rename clsMenuData_helper to fcMenuRecord_helper
      * Reworking how this works, so that it makes more sense.
        * renaming classes
        * adding traits
    2015-09-06 renamed from menu-helper.php to object-urls.php; removing old classes
*/

trait ftLinkableObject {
    protected function Test() {
    }
}

trait ftLinkableTable {
    use ftLinkableObject;

    // ++ API ++ //

    // OVERRIDEABLE
    protected function LinkBuilder() {
        return new fcLinkBuilder_table($this);
    }
    // OVERRIDEABLE
    // PUBLIC so Link Builder can use it
    public function SelfLink_idArray() {
        return 	array(
            'page' =>   $this->ActionKey()
            );
    }
    public function SelfArray(array $arAdd=NULL) {
        return $this->LinkBuilder()->LinkArray($arAdd);
    }
    public function SelfURL($arAdd=NULL) {
	return $this->LinkBuilder()->LinkURL($arAdd);
    }
    public function SelfLink($sText=NULL,$sPopup=NULL,array $arAdd=NULL,array $arAttr=NULL) {
        return $this->LinkBuilder()->LinkHTML($sText,$sPopup,$arAdd,$arAttr);
    }
    public function SelfRedirect(array $arAdd=NULL,$sText=NULL) {
	return $this->LinkBuilder()->LinkRedirect($arAdd,$sText);
    }

    // -- API -- //
    // ++ SETUP ++ //

    protected function InitVars() {
        parent::InitVars();
        $this->KeyName('ID');	// default key field; can be overridden
    }
    public function ActionKey($sNew=NULL) {
        static $sActionKey = NULL;

	if (!is_null($sNew)) {
	    $sActionKey = $sNew;
	}
	if (is_null($sActionKey)) {
	    return $this->ClassSng();
	} else {
	    return $sActionKey;
	}
    }

    // -- SETUP -- //
}
trait ftLinkableRecord {

    // ++ CONFIGURATION ++ //

    // OVERRIDEABLE
    protected function LinkBuilder() {
        return new fcLinkBuilder_records($this);
    }
    // OVERRIDEABLE
    // PUBLIC so Link Builder can use it
    public function SelfLink_idArray() {
	$arThis = array(
	  'id'	=> $this->KeyString()
	  );
	$tbl = $this->Table();
	if (!method_exists($tbl,'SelfArray')) {
	    $sMsg = 'The table class "'.get_class($tbl).'" needs to use the ftLinkableTable trait'
	      .' so that the recordset class "'.get_class($this).'" can use self-linking features.';
	    throw new exception($sMsg);
	}
	$arAll = $this->Table()->SelfArray($arThis);
        return $arAll;
    }
    /*----
      PURPOSE: r/w access method for the ID value to use for new record ID links
      OVERRIDEABLE
      RULES:
	* If set to an empty string, no link will be generated
    */
    public function SelfLink_NewKey() {
	return 'new';
    }

    // -- CONFIGURATION -- //
    // ++ API ++ //

    /*----
      USED BY: VbzAdmin::VbzAdminStkItems::Listing_forItem()
      HISTORY:
	2010-10-06 Disabled, because it wasn't clear if anyone was using it.
	  Thought I checked VbzAdmin, WorkFerret, and AudioFerret
	2010-10-13 VbzAdmin::VbzAdminStkItems::Listing_forItem() calls it
	2013-12-14 Moved from mw/admin.php to vbz-data.php
    */
    public function SelfArray(array $arAdd=NULL) {
        return $this->LinkBuilder()->LinkArray($arAdd);
    }
    public function SelfURL($arAdd=NULL) {
	return $this->LinkBuilder()->LinkURL($arAdd);
    }
    public function SelfLink($sText=NULL,$sPopup=NULL,array $arAdd=NULL,array $arAttr=NULL) {
	return $this->LinkBuilder()->LinkHTML($sText,$sPopup,$arAdd,$arAttr);
    }
    public function SelfRedirect(array $arAdd=NULL,$sText=NULL) {
	return $this->LinkBuilder()->LinkRedirect($arAdd,$sText);
    }

    // -- API -- //
    // ++ DEFAULT VALUES ++ //

    /* 2015-09-06 need clarity on what these are for
    protected function Text_toShow($sText) {
	return is_null($sText)
	  ?($this->RecordString())
	  :$sText
	  ;
    }
    protected function RecordString() {
	$rs = $this->Recs();
	return $rs->IsNew()
	  ?$this->NewKey()
	  :$rs->KeyString()
	  ;
    }*/

    // -- DEFAULT VALUES -- //

}

abstract class fcLinkBuilder {

    // ++ STATIC METHODS ++ //
/*
    private static function _Spawn() {
	$strClass = static::_ClassName();
	$obj = new $strClass();
	return $obj;
    }
*/
    // -- STATIC METHODS -- //
    // ++ ABSTRACT METHODS ++ //

    // RETURNS: an array of values that identify the linked object (table or table+record)
    abstract protected function IdentityValues();

    // STATIC: faux-abstract methods
/*
    protected static function _ClassName() {
	throw new exception('Invoke one of the non-abstract descendant classes instead of '.__CLASS__.'.');
	// descendant classes should just do this:
	return __CLASS__;
    } */

    // -- ABSTRACT METHODS -- //
    // ++ INTERNAL INFORMATION ++ //

    protected function BaseURL_rel() {
	return clsApp::Me()->Page()->BaseURL_rel();
    }

    // -- INTERNAL INFORMATION -- //
    // ++ API ++ //

    /*----
      RETURNS: array from which to build the URL
      INPUT:
	$arAdd = optional array of values to add to the list, or to override default values
    */
    public function LinkArray(array $arAdd=NULL) {
	$arBase = $this->IdentityValues();
	$arAll = clsArray::Merge($arBase,$arAdd);	// add/override defaults
	return $arAll;
    }
    /*----
      RETURNS: URL for link
      INPUT:
	$arAdd: same as LinkArray()
    */
    public function LinkURL(array $arAdd=NULL) {
	$arLink = $this->LinkArray($arAdd);
	$urlAdd = clsURL::FromArray($arLink);
	$urlBase = $this->BaseURL_rel();
	return $urlBase.'/'.$urlAdd;
    }
    /*----
      RETURNS: complete tag for link
      INPUT:
	$sText: the text that will represent the link
	$sPopup: text to pop up when hovering over the link
	$arAdd: same as LinkArray()
	$arAttr: any HTML attributes to apply to the link
    */
    public function LinkHTML($sText=NULL,$sPopup=NULL,array $arAdd=NULL,array $arAttr=NULL) {
	$url = $this->LinkURL($arAdd);
	$out = clsHTML::BuildLink($url,$sText,$sPopup,$arAttr);
	return $out;
    }
    /*----
      ACTION: redirects to a generated URL
    */
    public function LinkRedirect($sText=NULL,array $arAdd=NULL) {
	// TODO: finish adapting this

	clsHTTP::DisplayOnReturn($sText);
	$url = $this->LinkURL($arAdd);
	echo 'REDIRECTING to '.$url.' and saving the following text:<br>'.$sText;
	clsHTTP::Redirect($url,array(),FALSE,HTTP_REDIRECT_POST);
	die();	// don't do any more work (including accidentally erasing the cookie)
    }

    // -- API -- //
}

class fcLinkBuilder_table extends fcLinkBuilder {
    private $tbl;

    // ++ SETUP ++ //

    public function __construct(clsTable $tbl) {
        $this->tbl = $tbl;
    }

    // -- SETUP -- //
    // ++ STATIC METHODS ++ //
/*
    static public function _LinkHTML(clsTable $tbl,$sText=NULL,$sPopup=NULL,array $arAdd=NULL,array $arAttr=NULL) {
	$self = static::_Spawn();			// make an object so we can call dynamic methods
	$self->Table($tbl);
	return $self->LinkHTML($sText,$sPopup,$arAdd,$arAttr);
    } */

    // -- STATIC METHODS -- //
    // ++ LOCAL FIELD ACCESS ++ //

    protected function Table(clsTable $tbl=NULL) {
	if (!is_null($tbl)) {
	    $this->tbl = $tbl;
	}
	return $this->tbl;
    }

    // -- LOCAL FIELD ACCESS -- //
    // ++ CEMENTING ++ //

    protected function IdentityValues() {
	return $this->Table()->SelfLink_idArray();
    }

    // -- CEMENTING -- //
}

/*%%%%
  PURPOSE: approximately replaces clsMenuData_helper and offspring
*/
class fcLinkBuilder_records extends fcLinkBuilder {
    private $rs;
    private $strNewTxt;
    private $strNewKey;

    // ++ SETUP/CONFIG ++ //
/*
    public function __construct() {
	$this->sNewTxt = 'new';
	$this->sNewKey = '';
    }*/

    public function __construct(clsRecs_keyed_abstract $rcThis) {
        $this->rs = $rcThis;
    }

    /*----
      HISTORY:
	2014-02-21 changed $iRecs from single-keyed class to abstract-keyed class
    */
    public function Recs(clsRecs_keyed_abstract $iRecs=NULL) {
	if (!is_null($iRecs)) {
	    $this->rs = $iRecs;
	}
	return $this->rs;
    }

    // -- SETUP/CONFIG -- //
    // ++ CEMENTING ++ //

    protected function IdentityValues() {
	return $this->Recs()->SelfLink_idArray();
    }

    // -- CEMENTING -- //
}