<?php
/*
  PURPOSE: class that handles general caching
  HISTORY:
    2013-11-06 split off from data.php
    2016-12-02 rewriting completely using trait; extracted all data-cache-manager stuff elsewhere
*/
trait ftCacheableTable {
    protected function CacheClass() {
	return 'fcThingCache';
    }
    protected $oCache;
    protected function CacheObject() {
	if (!isset($this->oCache)) {
	    $sClass = $this->CacheClass();
	    $this->oCache = new $sClass();
	}
	return $this->oCache;
    }
    public function GetRecord_Cached($id) {
	$oCache = $this->CacheObject();
	if ($oCache->ItemExists($id)) {
	    $arVals = $oCache->GetItem($id);
	    $rc = $this->SpawnRecordset();
	    $rc->SetFieldValues($arVals);
	} else {
	    $rc = $this->GetRecord_forKey($id);
	    $oCache->SetItem($id,$rc->GetFieldValues());
	}
	return $rc;
    }
}

