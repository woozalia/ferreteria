<?php
/*
  PURPOSE: classes for creating menus
  USED BY: Dropins
  RULES: each menu link must have name that is unique across all menu links
    This may cause naming conflicts later if dropins are developed by 3rd parties,
      but we'll deal with that when we get to it.

    SECURITY:
      1. If a node is given an access requirement ($sPerm) when it is created,
	the permission is checked immediately and the node is not added to the tree
	if the user does not have adequate permission.
      2. If a node is given an access requirement later on (via NeedPermission()),
	then it is immediately removed from the tree if the user does not have
	that permission.
      3. Any setting of permission that leaves the node in the tree will set the isAuth
	flag. If this flag is not set by the time the node is rendered, the node will
	be skipped (and not rendered).

  REQUIRES: KS_CHAR_URL_ASSIGN must be defined (typically ':')
  TODO: Merge this with navbar classes in page.menu.php.
  HISTORY:
    2013-11-29 started
    2013-12-12 rewriting to use single path-segment for each menu item
      added clsMenuNode
    2014-01-26 rewriting so menu items handle the recursion, with callbacks to the Painter
    2016-01-01 Node-descendants are now initialized with the Root node, which has a pointer
      to the MenuManager, eliminating the need to pass the MenuPainter repeatedly.
    2016-12-04 renaming from cls* to fc*
*/

abstract class fcMenuNode extends fcTreeNode {

    // ++ SETUP ++ //

    public function __construct(fcMenuMgr $o) {
    throw new exception('Who calls this?');
	$this->ManagerObject($o);
    }
    private $oMgr;
    public function ManagerObject(fcMenuMgr $o=NULL) {
	if (!is_null($o)) {
	    $this->oMgr = $o;
	}
	return $this->oMgr;
    }
    protected function PainterObject() {
	return $this->ManagerObject()->Painter();
    }
    protected function MapperObject() {
	return $this->ManagerObject()->Mapper();
    }
    
    // -- SETUP -- //
    // ++ ACTIONS ++ //
    
    abstract public function RenderContent();
    
    // -- ACTIONS -- //
}
abstract class fcMenuItem extends fcMenuNode {
    private $sText;	// text to show for item
    private $sTitle;	// title for page
    private $sDescr;	// description - e.g. show when hovering
    private $isSel;	// node is selected?
    private $isAuth;	// node is authorized?
    private $sPHP;	// code to execute when selected
    private $sCtrler;	// name of controller class (if any)

    // ++ SETUP ++ //

    public function __construct(
      fcMenuNode $oRoot,	// must have a parent node
      $sName,
      $sText,
      $sTitle,
      $sDescr=NULL,
      $sPerm=NULL
      ) {
	parent::__construct($oRoot->ManagerObject());
	$this->Name($sName);
	$this->sText = $sText;
	$this->sTitle = $sTitle;
	$this->sDescr = $sDescr;
	$this->InitLinks($oRoot,$sPerm);
	$this->Init();
    }
    protected function InitLinks($oRoot,$sPerm) {
	$this->isAuth = FALSE;	// node has not been authorized yet
	$oUser = clsApp::Me()->User();
	if (is_object($oUser)) {
	    $hasParent = !is_null($oRoot);
	    $hasReq = !is_null($sPerm);
	    if ($hasReq) {
		// permission requirement has been set
		$isAuth = $oUser->CanDo($sPerm);	// find out if user has permission
		$doAdd = $isAuth;			// add node if permission granted
	    } else {
		// permission requirement not yet set
		$isAuth = FALSE;	// permission not given
		$doAdd = TRUE;	// add node for now, check auth later
	    }
	    if ($hasParent && $doAdd) {
		$oRoot->NodeAdd($this);
		$this->isAuth = $hasReq;	// save authorization status
	    }
	}
    }
    protected function Init() {}

    // -- SETUP -- //
    // ++ ACCESS ++ //

    public function Text($sVal=NULL) {
	if (!is_null($sVal)) {
	    $this->sText = $sVal;
	}
	return $this->sText;
    }
    public function Title($sVal=NULL) {
	if (!is_null($sVal)) {
	    $this->sTitle = $sVal;
	}
	return $this->sTitle;
    }
    public function Descr($sVal=NULL) {
	if (!is_null($sVal)) {
	    $this->sDescr = $sVal;
	}
	return $this->sDescr;
    }
    public function Controller($sName=NULL) {
	if (!is_null($sName)) {
	    $this->sCtrler = $sName;
	}
	return $this->sCtrler;
    }
    public function IsAuth() {
	return $this->isAuth;
    }
    public function NeedPermission($sPerm) {
	$this->isAuth = FALSE;	// assume node has NOT been authorized
	if (is_null($sPerm)) {
	    // in this context, NULL permission means all users are authorized
	    $this->isAuth = TRUE;	// node has been authorized
	} elseif (clsApp::Me()->UserIsLoggedIn()) {
	    if (clsApp::Me()->User()->CanDo($sPerm)) {
		// this item has been authorized for access
		$this->isAuth = TRUE;	// node has been authorized
	    }
	}
	if (!$this->isAuth) {
	    // remove this item from the menu
	    if ($this->HasParent()) {
		// can't remove parent, so only delete if node *has* a parent
		$this->Delete();
//	    } else {
//		$this->isAuth = FALSE;	// node is NOT authorized
	    }
	}
    }
    /*----
      USAGE: Caller determines which menu node is active, then calls this with TRUE for that node.
    */
    public function SetSelected($isSel) {
	$this->isSel = $isSel;
    }
    public function GetSelected() {
	return $this->isSel;
    }
    /*----
      USAGE: eval(item->GoCode()) in the context where you want it to work.
    */
    public function GoCode($sPHP=NULL) {
	if (!is_null($sPHP)) {
	    $this->sPHP = $sPHP;
	}
	return $this->sPHP;
    }
    //abstract public function IsLink();

    // -- ACCESS -- //
    // ++ RENDERING ++ //

    public function Render($sBef,$sAft) {
	if ($this->IsAuth()) {
	    $out = $sBef.($this->RenderContent()).$sAft;
	    if ($this->HasNodes()) {
		$oPaint = $this->PainterObject();
		$out .= $oPaint->RenderIndent();
		$out .= $this->RenderSubs();
		$out .= $oPaint->RenderOutdent();
	    }
	} else {
	    $out = NULL;
	}
	return $out;
    }
    public function RenderSubs() {
	$out = NULL;
	$arNodes = $this->Nodes();
	$oPaint = $this->PainterObject();
	foreach ($arNodes as $sName => $oSub) {
	    $htLine = $oPaint->RenderNode($oSub);
	    $out .= $htLine;
	}
	return $out;
    }
    public function RenderContent() {
	$sText = $this->Text();
	$isSel = $this->Selected();
	$htDescr = $this->RenderTitleAttr();
	if ($isSel) {
	    $out = "<span class=selected$htDescr>$sText</span>";
	} else {
	    $out = "<span$htDescr>$sText</span>";	// this should probably have a class
	}
	return $out;
    }
    protected function RenderTitleAttr() {
	$sDescr = $this->Descr();
	if (is_null($sDescr)) {
	    $htDescr = NULL;
	} else {
	    $htDescr = ' title="'.fcString::EncodeForHTML($sDescr).'"';
	}
	return $htDescr;
    }

    // -- RENDERING -- //
}

class fcMenuLink extends fcMenuItem {
    private $url;	// specified URL for this link, if any
    private $isAbs;	// URL is absolute?

    // ++ SETUP ++ //

    public function Init() {
	$this->sCtrler = NULL;
	$this->url = NULL;
	$this->isSel = NULL;
	$this->isAbs = NULL;
    }

    // -- SETUP -- //
    // ++ FIELD ACCESS ++ //

    /*----
      RETURNS: URL fragment to be added to base URL
    */
    public function URL($url=NULL, $doRepl=FALSE) {
	if (!is_null($url)) {
	    $this->url = $url;
	    $this->isAbs = $doRepl;
	}
	if (is_null($this->url)) {
	    return $this->Name();
	} else {
	    return $this->url;
	}
    }
    protected function URL_toUse() {
	if ($this->isAbs) {
	    return $this->URL();
	} else {
	    return $this->PainterObject()->MenuItem_BuildURL($this->URL());
	}
    }

    // -- FIELD ACCESS -- //
    // ++ RENDERING ++ //

    public function RenderContent() {
	//$sName = $this->Name();	// this doesn't do anything
	$sText = $this->Text();
	$htText = fcString::EncodeForHTML($sText);
	$sDescr = $this->Descr();
	$urlNode = $this->URL_toUse($this->PainterObject());
	$isSel = $this->GetSelected();
	$cssClass = $isSel?'node-active':'node-passive';
	$arAttr = array('class'=>$cssClass);

	$htLink = clsHTML::BuildLink($urlNode,$htText,$sDescr,$arAttr);

	return $htLink;
    }

    // -- RENDERING -- //
}
class fcMenuFolder extends fcMenuItem {
    public function RenderContent() {
	return $this->Text();
    }
}
class fcMenuHidden extends fcMenuFolder {
    public function __construct(
      fcMenuNode $oRoot,	// must have a parent node
      // 2016-01-01 do we really need all these parameters?
      $sName,
      $sTitle,
      $sDescr=NULL,
      $sPerm=NULL
      ) {
	$oMgr = $oRoot->ManagerObject();
	$this->ManagerObject($oMgr);
	$this->Name($sName);
	if (!is_null($oRoot)) {
	    $oRoot->NodeAdd($this);
	}
	$this->Title($sTitle);
	$this->Descr($sDescr);
	$this->InitLinks($oRoot,$sPerm);
	$this->Init();
    }
    public function Render($sBef,$sAft) {
	return NULL;
    }
}
class fcMenuRoot extends fcMenuHidden {
    public function __construct(fcMenuMgr $o) {
	$this->ManagerObject($o);
    }
}
