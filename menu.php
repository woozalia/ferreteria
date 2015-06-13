<?php
/*
  PURPOSE: classes for creating menus
  RULES: each menu link must have name that is unique across all menu links
    This may cause naming conflicts later if dropins are developed by 3rd parties,
      but we'll deal with it then.

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
  HISTORY:
    2013-11-29 started
    2013-12-12 rewriting to use single path-segment for each menu item
      added clsMenuNode
    2014-01-26 rewriting so menu items handle the recursion, with callbacks to the Painter
*/
abstract class clsMenuNode extends clsTreeNode {
    abstract public function RenderContent(clsMenuPainter $oPainter);
}
abstract class clsMenuItem extends clsMenuNode {
    private $sText;	// text to show for item
    private $sTitle;	// title for page
    private $sDescr;	// description - e.g. show when hovering
    private $isSel;	// node is selected?
    private $isAuth;	// node is authorized?
    private $sPHP;	// code to execute when selected
    private $sCtrler;	// name of controller class (if any)

    // ++ SETUP ++ //

    public function __construct(
      clsMenuNode $oRoot=NULL,	// parent node (if any)
      $sName,
      $sText,
      $sTitle,
      $sDescr=NULL,
      $sPerm=NULL
      ) {
	$this->Name($sName);
	$this->sText = $sText;
	$this->sTitle = $sTitle;
	$this->sDescr = $sDescr;
	$this->InitLinks($oRoot,$sPerm);
	$this->Init();
    }
    protected function InitLinks($oRoot,$sPerm) {
	$this->isAuth = FALSE;	// node has not been authorized yet
	$oUser = clsApp::Me()->User('clsMenuItem.InitLinks()');
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
	} elseif (clsApp::Me()->UserKnown('clsMenuItem.NeedPermission()')) {
	    if (clsApp::Me()->User('menu.NeedPermission()')->CanDo($sPerm)) {
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
    public function Selected($isSel=NULL) {
	if (!is_null($isSel)) {
	    $this->isSel = $isSel;
	}
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

    public function Render(clsMenuPainter $oPainter,$sBef,$sAft) {
	if ($this->IsAuth()) {
	    $out = $sBef.$this->RenderContent($oPainter).$sAft;
	    if ($this->HasNodes()) {
		$out .= $oPainter->RenderIndent();
		$out .= $this->RenderSubs($oPainter);
		$out .= $oPainter->RenderOutdent();
	    }
	} else {
	    $out = NULL;
	}
	return $out;
    }
    public function RenderSubs(clsMenuPainter $oPainter) {
	$out = NULL;
	$arNodes = $this->Nodes();
	foreach ($arNodes as $sName => $oSub) {
	    $htLine = $oPainter->RenderNode($oSub);
	    $out .= $htLine;
	}
	return $out;
    }
    public function RenderContent(clsMenuPainter $oPainter) {
	//$sName = $this->Name();	// this doesn't do anything
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
	    $htDescr = ' title="'.htmlspecialchars($sDescr).'"';
	}
	return $htDescr;
    }

    // -- RENDERING -- //
}

class clsMenuLink extends clsMenuItem {
    private $url;	// specified URL for this link, if any

    // ++ SETUP ++ //

    public function Init() {
	$this->sCtrler = NULL;
	$this->url = NULL;
	$this->isSel = NULL;
    }

    // -- SETUP -- //
    // ++ FIELD ACCESS ++ //

    /*----
      RETURNS: URL fragment to be added to base URL
    */
    public function URL($url=NULL) {
	if (!is_null($url)) {
	    $this->url = $url;
	}
	if (is_null($this->url)) {
	    return $this->Name();
	} else {
	    return $this->url;
	}
    }

    // -- FIELD ACCESS -- //
    // ++ RENDERING ++ //

    public function RenderContent(clsMenuPainter $oPainter) {
	//$sName = $this->Name();	// this doesn't do anything
	$sText = $this->Text();
	$htText = htmlspecialchars($sText);
	$sDescr = $this->Descr();
	$urlNode = $oPainter->MenuItem_BuildURL($this->URL());
	$isSel = $this->Selected();
	$cssClass = $isSel?'node-active':'node-passive';
	$arAttr = array('class'=>$cssClass);

	$htLink = clsHTML::BuildLink($urlNode,$htText,$sDescr,$arAttr);

	return $htLink;
    }

    // -- RENDERING -- //
}
class clsMenuFolder extends clsMenuItem {
    public function RenderContent(clsMenuPainter $oPainter) {
	return $this->Text();
    }
}
class clsMenuHidden extends clsMenuFolder {
    public function __construct(
      clsMenuNode $oRoot=NULL,	// parent node (if any)
      $sName,
      $sTitle,
      $sDescr=NULL,
      $sPerm=NULL
      ) {
	$this->Name($sName);
	if (!is_null($oRoot)) {
	    $oRoot->NodeAdd($this);
	}
	$this->Title($sTitle);
	$this->Descr($sDescr);
	$this->InitLinks($oRoot,$sPerm);
	$this->Init();
    }
/*
    public function IsLink() {
	return FALSE;
    }
*/
    public function Render(clsMenuPainter $oPainter,$sBef,$sAft) {
	return NULL;
    }
/*
    public function RenderContent(clsMenuPainter $oPainter) {
	return NULL;
    }
    */
}

abstract class clsMenuPainter {
    private $urlBase;
    private $oRoot;
    private $sSep;
    protected $urlPfx,$urlSfx;

    public function __construct($sSep) {
	$this->urlBase = NULL;
	$this->sSep = $sSep;
	$this->urlPfx = NULL;
	$this->urlSfx = NULL;
    }
    public function DecorateURL($urlPfx,$urlSfx) {
	$this->urlPfx = $urlPfx;
	$this->urlSfx = $urlSfx;
    }
    public function BaseURL($url=NULL) {
	if (!is_null($url)) {
	    $this->urlBase = $url;
	}
	return $this->urlBase;
    }
    protected function Sep() {
	return $this->sSep;
    }
    /*----
      USAGE: This will probably mainly be an internal call, since
	the Page object now just keeps track of the registry rather than
	the menu-root.
    */
    public function RootNode(clsMenuItem $oNode=NULL) {
	if (!is_null($oNode)) {
	    $this->oRoot = $oNode;
	}
	return $this->oRoot;
    }
    /*----
      NOTE: For now, we're just snagging the root node from here,
	since we don't actually need the Registry. That could
	change later, however.
    */
    public function Home(clsMenuItem $oNode=NULL) {
	$this->RootNode($oNode);
    }
    public function Render(clsMenuItem $oNode=NULL) {
	if (is_null($oNode)) {
	    $oNode = $this->RootNode();
	}
	// render the current node
	$out = $this->RenderNode($oNode);
	return $out;
    }
/*
    public function RenderSubnodes(clsMenuItem $oNode=NULL) {
	if (is_null($oNode)) {
	    $oNode = $this->RootNode();
	}
	$this->nDepth++;
	$out = NULL;
	$arNodes = $oNode->Nodes();
	foreach ($arNodes as $sName => $oSub) {
	    $out .= $this->Render($oSub);
	}
	$this->nDepth--;
	return $out;
    }
    */

    // ++ clsMenuItem CALLBACKS ++ //

    abstract public function RenderNode(clsMenuItem $oNode);
    abstract public function RenderIndent();
    abstract public function RenderOutdent();
    abstract public function MenuItem_BuildURL($urlItem);

    // -- clsMenuItem CALLBACKS -- //
}

/*%%%%
  PURPOSE: Renders the menu as an unordered list using <ul> and <li> tags
*/
class clsMenuPainter_UL extends clsMenuPainter {

    // ++ clsMenuItem CALLBACKS ++ //

    public function RenderNode(clsMenuItem $oNode) {
	$out = $oNode->Render($this,'<li>','</li>');
	return $out;
    }
    public function RenderIndent() {
	return "\n<ul>";
    }
    public function RenderOutdent() {
	return "\n</ul>";
    }
    public function MenuItem_BuildURL($urlItem) {
	if (is_null($urlItem)) {
	    $out = NULL;
	} else {
	    $out = $this->urlPfx.$urlItem.$this->urlSfx;
	}
	return $out;
    }

    // -- clsMenuItem CALLBACKS -- //
    // ++ DEBUGGING ++ //
/*
    public function RenderStats(clsMenuItem $oNode) {
	$urlPBase = $this->BaseUrl();

	$urlNPath = $oNode->PathStr($this->Sep());
	$sArgsDump = print_r($oNode->Args(),TRUE);
	$ht = <<<__END__
<ul>
  <li>PAINTER:</li>
  <ul>
    <li>Base: <b>$urlPBase</b></li>
  </ul>
  <li>NODE:</li>
  <ul>
    <li>Path: <b>$urlNPath</b></li>
    <li>Args:<pre>$sArgsDump</pre></li>
  </ul>
</ul>
__END__;
	return $ht;
    }
*/

    // -- DEBUGGING -- //
}

/*
This might be useful later if we want a more general painter:
    private $sPreItem,$sPostItem,$sIndent,$sOutdent;

    public function Decorate($sPreItem,$sPostItem,$sIndent,$sOutdent) {
	$this->sPreItem = $sPreItem;
	$this->sPostItem = $sPostItem;
	$this->sIndent = $sIndent;
	$this->sOutdent = $sOutdent;
    }
*/
