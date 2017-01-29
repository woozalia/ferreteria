<?php
/*
  PURPOSE: classes for mapping between menu items and URLs
  HISTORY:
    2016-01-01 started
*/

abstract class fcMenuMap {

    // ++ CLASS FRAMEWORK ++ //

    private $oMgr;
    public function Manager(fcMenuMgr $o=NULL) {
	if (!is_null($o)) {
	    $this->oMgr = $o;
	}
	return $this->oMgr;
    }
    
    // -- CLASS FRAMEWORK -- //
    // ++ URL -> OBJECT ++ //
    
    /*----
      ACTION: given input to be defined by descendants,
	finds the menu item which has been selected and returns its object
    */
    abstract public function SelectedItemObject();
    
    // -- URL -> OBJECT -- //
    // ++ OBJECT -> URL ++ //
}

/*::::
  INCARNATION: Uses a clsPageLogin to get the applicable URL fragment.
    That functionality should probably be encapsulated at some point;
    it doesn't need to be closely integrated with other Page functionality.
*/
class fcMenuMap_Page extends fcMenuMap {
    public function __construct(clsPageLogin $oPage, $sPathKey='page') {
	
	$this->Page($oPage);
    }
    private $sPathKey;
    protected function PathKey($s=NULL) {
	if (!is_null($s)) {
	    $this->sPathKey = $s;
	}
	return $this->sPathKey;
    }
    /*----
      OVERRIDE: We can't query the MenuRoot until after setup time, because
	if we query it during setup we create an infinite call-loop.
	So instead, we store the Page but don't ask it for the MenuRoot
	until we actually need to.
    */
    protected function Menu() {
	return $this->Page()->MenuRoot();
    }
    private $oPage;
    protected function Page(clsPageLogin $oPage=NULL) {
	if (!is_null($oPage)) {
	    $this->oPage = $oPage;
	}
	return $this->oPage;
    }
    
    // ++ CEMENTING ++ //
    
    /*----
      ACTION: given input to be defined by descendants,
	finds the menu item which has been selected and returns its object
    */
    private $miSel;
    public function SelectedItemObject() {
	if (empty($this->miSel)) {
	    $oPage = $this->Page();
	    $sKey = $this->PathKey();
	    // figure out which menu item has been invoked
	    $oPage->ParsePath();
	    if ($oPage->PathArg_exists($sKey)) {	// if a page is specified
		$sPage = $oPage->PathArg($sKey);
		if (is_array($sPage)) {
		    // TODO: silently log an error (may be a bad link): 'page' being set multiple times
		    $sPage = array_pop($sPage);	// get one element and use that
		}
		if (is_null($this->MenuHome())) {
		    throw new exception('Trying to access menu  when there is no home node.');
		}
		// get the menu item object
		$this->miSel = $oPage->MenuHome()->FindNode($sPage);
		$this->miSel->SetSelected(TRUE);	// tell the node it's on
	    } else {
		$this->miSel = NULL;	// maybe we need some way to prevent this being looked up again?
	    }
	}
	return $this->miSel;
    }
    
    // -- CEMENTING -- //
    
}