<?php
/*
  PURPOSE: classes for rendering menus
  USES: menu-item.php
  HISTORY:
    2016-01-01 split off from menu-item.php (formerly menu.php)
*/

abstract class fcMenuPainter {
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
    private $oMgr;
    public function Manager(fcMenuMgr $o=NULL) {
	if (!is_null($o)) {
	    $this->oMgr = $o;
	}
	return $this->oMgr;
    }
    /*----
      NOTE: For now, we're just snagging the root node from here,
	since we don't actually need the Registry. That could
	change later, however.
    */
    /*----
      HISTORY:
	2016-01-03 This seems to be redundant now -- merge with RenderNode at some point. TODO.
    */
    public function Render(fcMenuItem $oNode) {
	// render the given node
	return $this->RenderNode($oNode);
    }

    // ++ fcMenuItem CALLBACKS ++ //

    abstract public function RenderNode(fcMenuItem $oNode);
    abstract public function RenderIndent();
    abstract public function RenderOutdent();
    abstract public function MenuItem_BuildURL($urlItem);

    // -- fcMenuItem CALLBACKS -- //

}

/*::::
  PURPOSE: Renders the menu as an unordered list using <ul> and <li> tags
*/
class fcMenuPainter_UL extends fcMenuPainter {

    // ++ fcMenuItem CALLBACKS ++ //

    public function RenderNode(fcMenuItem $oNode) {
	$out = $oNode->Render('<li>','</li>');
	return $out;
    }
    public function RenderIndent() {
	return "\n<ul class=painted-menu>";
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

    // -- fcMenuItem CALLBACKS -- //
    // ++ DEBUGGING ++ //
/*
    public function RenderStats(fcMenuItem $oNode) {
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
