<?php
/*
  PURPOSE: Splitting off Page Menu methods into a Trait in order to simplify inheritance.
    Earlier, I had for some reason put the menuing functions in clsPageLogin, from which
    most VbzCart classes descend because they need the login functions in order to track
    user sessions -- but they don't need the menus (at least not yet). The menuing functions,
    however, require implementing the method MenuPainter_new() -- so we get a whole bunch
    of classes having to just stub that off.
    
    This allows me to import those functions only when I need them.
    
  HISTORY:
    2016-03-08 started
*/
trait ftPageMenu {
    private $oMHome;	// menu "home" node
    protected function MenuHome() {
	if (empty($this->oMHome)) {
	    $this->oMHome = $this->MenuHome_new();
	}
	return $this->oMHome;
    }
    abstract protected function MenuHome_new();
    /*----
      USED: internally and by drop-in controllers
    */
    private $oMNode;	// selected menu node (if any)
    protected function MenuNode() {
	if (is_null($this->oMNode)) {
	    // figure out which menu item has been invoked
	    $this->ParsePath();
	    if ($this->PathArg_exists('page')) {	// if a page is specified
		$sPage = $this->PathArg('page');
		if (is_array($sPage)) {
		    // TODO: silently log an error (may be a bad link): 'page' being set multiple times
		    $sPage = array_pop($sPage);	// get one element and use that
		}
		if (is_null($this->MenuHome())) {
		    throw new exception('Trying to access menu  when there is no home node.');
		}
		// get the menu item object
		$this->oMNode = $this->MenuHome()->FindNode($sPage);
	    } else {
		$this->oMNode = NULL;	// maybe we need some way to prevent this being looked up again?
	    }
	}
	return $this->oMNode;
    }
    private $oMPaint;	// menu painter
    protected function MenuPainter() {
	if (is_null($this->oMPaint)) {
	    $this->oMPaint = $this->MenuPainter_new();
	}
	return $this->oMPaint;
    }
    abstract protected function MenuPainter_new();
    /*----
      FUTURE: We might eventually want to generalize the MenuMapper the same way the MenuPainter is.
    */
    private $oMenuMap;
    protected function MenuMapper() {
	if (empty($this->oMenuMap)) {
	    $this->oMenuMap = new fcMenuMap_Page($this);
	}
	return $this->oMenuMap;
    }
    protected function RenderHome() {
	return $this->RenderLogout();	// ok to override this
    }
    /*----
      FUTURE: We might eventually want to generalize the MenuRoot the same way the MenuPainter is.
      PUBLIC so MenuMapper can access it
    */
    private $oMenuRoot;
    public function MenuRoot() {
    static $idxLoop = 0;
	if (empty($this->oMenuRoot)) {
	    $idxLoop++;
	    if ($idxLoop > 100) {
	      throw new exception('How does this happen?');
	    }
	
	    $this->oMenuRoot = new clsMenuRoot($this->MenuManager());
	    
	    $idxLoop--;
	}
	return $this->oMenuRoot;
    }
    /*----
      FUTURE: We might eventually want to generalize the MenuManager the same way the MenuPainter is.
    */
    private $oMenuMgr;
    protected function MenuManager() {
	if (empty($this->oMenuMgr)) {
	    $this->oMenuMgr = new fcMenuMgr(
	      $this->MenuMapper(),
	      $this->MenuPainter()
	      );
	}
	return $this->oMenuMgr;
    }
    
    /*----
      ACTION: Executes the main action for the currently chosen menu selection
	(as derived from URL path info)
      LATER: (2013-12-03) This probably needs to be generalized a bit more,
	but hopefully it will do for now.
      HISTORY:
	2014-01-27 Renamed from HandleMenu() to MenuNode_Exec()
    */
    protected function MenuNode_Exec() {
	$oNode = $this->MenuNode();
	if (!is_null($oNode)) {
	    if (is_null($oNode->GoCode())) {
		// if no Go Code, attempt to use controller
		$sCtrler = $oNode->Controller();
		$ok = FALSE;
		if (!is_null($sCtrler)) {
		    $id = $this->PathArg('id');
		    $this->Skin()->SetPageTitle($oNode->Title());
		    if (is_null($id)) {
			$tbl = $this->Data()->Make($sCtrler);
			// surely this duplicates other code -- but where, and why isn't it being triggered? (https://vbz.net/admin/page:ord/)
			$out = $tbl->MenuExec($this->PathArgs());
		    } else {
			//$rc2 = $tbl->GetItem($id);
			$rc = $this->Data()->Make($sCtrler,$id);
			$out = $rc->MenuExec($this->PathArgs());
		    }
		    return $out;
		}
		if (!$ok) {
		    $sName = $oNode->Name();
		    if (empty($sName)) {
			// this is a bit of a kluge; I don't know why we end up here
			return $this->DefaultContent();
		    } else {
			return 'No action defined for menu item "'.$sName.'".';
		    }
		}
	    } else {
		$php = $oNode->GoCode();
		return eval($php);	// execute the menu choice
	    }
	} else {
	    return $this->DefaultContent();
	}
    }
    /*----
      PURPOSE: Called when no menu item is active
    */
    protected function DefaultContent() {
	return 'Choose an item from the menu, or '.$this->RenderHome().'.';
    }
    /*----
      ACTION: Does any initialization needed for the currently chosen menu selection
    */
    protected function MenuNode_Init() {
	$oNode = $this->MenuNode();
	if (!is_null($oNode)) {
	    $oNode->SetSelected(TRUE);
	    $sCtrler = $oNode->Controller();
	    if (!is_null($sCtrler)) {
		$id = $this->PathArg('id');
		$obj = $this->Data()->Make($sCtrler,$id);
		if (method_exists($obj,'MenuInit')) {
		    $out = $obj->MenuInit($this->PathArgs());
		} else {
		    $out = NULL;
		}
		return $out;
	    }
	}
    }
}