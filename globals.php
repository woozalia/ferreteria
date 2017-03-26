<?php
/*
  PURPOSE: A somewhat more controlled way of managing global values
  HISTORY:
    2017-03-13 created: fcGlobals and ftSingleton
*/
/*::::
  PURPOSE: for forcing a class to have only one object-instance
*/
trait ftSingleton {
    static protected $me;
    static public function Me() {
	if (!isset(self::$me)) {
	    throw new exception('Ferreteria usage error: attempting to access '.get_class($this).' object before it has been set.');
	}
	return self::$me;
    }
    public function __construct() {
	if (isset(self::$me)) {
	    throw new exception('Ferreteria usage error: attempted to create duplicate instance of '.get_class($this).'.');
	}
	self::$me = $this;	// there can be only one
    }
}
/*::::
  USAGE: Descend from this to set return values
*/
abstract class fcGlobals {
    use ftSingleton;
    
    // file paths

    abstract protected function GetFilePath_forSiteFolders();
    public function GetFilePath_forDropins() {
	return $this->GetFilePath_forSiteFolders().'/dropins';
    }
    
    // web paths
    
    abstract protected function GetWebPath_forAppBase();
    abstract protected function GetWebPath_forStyleSheets();

    /*----
      PURPOSE: Deals with the root/non-root dilemma
	* If you use a slash for root:
	** If you want paths not to end in a slash:
	*** If you always add a slash between AppBase and RelPath, then you get double slashes if AppBase is root
	*** If you don't add a slash between AppBase and RelPath, then you get pathwreck (/parent/child becomes /parentchild).
	** If you end all paths in a slash, this tends to cause other issues (to be documented)
	* If you use blank for root:
	** If you want paths not to end in a slash:
	*** You have to specify AppBase without a beginning or ending slash
	*** You have to prefix constructed paths with a slash after assembly
	** If you end all paths with a slash:
	*** You have to remember to request BasePath as '/' and terminate all RelPaths with '/' as well.
	*** ...and ending all paths with a slash causes issues elsewhere
	Solution:
	  * Use blank for root.
	  * Prepend slash to all constructed paths.
	  * Specify nonblank BasePath with ending slash, no beginning slash
	  * Request RelPath with ending '/', even if blank (i.e. you're actually requesting BasePath)
	  * Implement MakeWebPath_forAppPath_noSlash() which removes ending slashes, in case this is needed
    */
    protected function MakeWebPath_forAppPath($fp) {
	$fpBase = $this->GetWebPath_forAppBase();
	return '/'.$fpBase.$fp;
    }
    protected function MakeWebPath_forAppPath_noSlash($fp) {
	if (substr($fp,-1) != '/') {
	    throw new exception("Ferreteria usage error: requested relative path [$fp] does not end with a '/'.");
	}
	$fpBase = $this->GetWebPath_forAppBase();
	return $fpBase.substr($fp,0,-1);	// omit the last character, which must be a slash
    }
    
    // -- individual files
    
    abstract public function GetWebPath_forSuccessIcon();
    abstract public function GetWebPath_forWarningIcon();
    abstract public function GetWebPath_forErrorIcon();
}