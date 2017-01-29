<?php
/*
  PURPOSE: class that handles general caching
  HISTORY:
    2013-11-06 split off from data.php
    2016-12-02 rewriting completely (much simpler); extracted all data-cache-manager stuff elsewhere
*/

/*::::
  PURPOSE: general keyed data cache
  HISTORY:
    2016-12-02 Written to completely replace existing cache classes.
*/
class fcThingCache {
    private $ar;

    public function __construct() {
	$this->ar = array();
    }
    public function ItemExists($sKey) {
	return array_key_exists($sKey,$this->ar);
    }
    // ASSUMES: $this->ItemExists() is TRUE; will generate error if not.
    public function GetItem($sKey) {
	return $this->ar[$sKey];
    }
    public function SetItem($sKey,$vItem) {
	$this->ar[$sKey] = $vItem;
    }
    public function DumpHTML() {
	return fcArray::Render($this->ar);
    }
}
