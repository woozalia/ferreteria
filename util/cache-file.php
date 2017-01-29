<?php
/* =============================
  CLASS LIBRARY: Cache Management
  DOCUMENTATION: http://htyp.org/User:Woozle/cache.php
  HISTORY:
    2009-04-12 significant rewrite:
      - not using clsLibMgr
      - using rewritten data.php
    2009-09-07 more rewriting to use new DataSet construction standard
    2009-10-14 using KFP_LIB instead of constructed path
    2010-11-09 another significant rewrite to use queries instead of procedures
    2010-11-10 ...which turned out not to be a good idea. Change back later, or adapt to use either one.
    2010-11-14 finally renamed file from datamgr.php to cache.php
    2011-03-03 clsCacheFile (later renamed vcCacheFile)
    2016-09-25 updated some class names from "cls*" to "fc*"
    2016-10-23 trying to update for db.v2
    2016-10-28 Changed some instances of KeyValue() to GetKeyValue(), but I think I really need to wait for the errors to happen.
    2016-12-02 Extracted data-cache-manager classes to db.v1/data-cache-mgr.php; renamed this to cache-file.php
*/

/*::::
  PURPOSE: dead simple file-based cache for a small number of larger chunks of data
*/
class fcCacheFile {
    protected $fpFolder;

    public function __construct($iFolder) {
	$this->fpFolder = $iFolder;
    }
    protected function Spec($iName) {
	$fs = $this->fpFolder.'/'.$iName.'.txt';
	return $fs;
    }
    public function Exists($iName) {
	$ok = file_exists($this->Spec($iName));
	return $ok;
    }
    public function Read($iName) {
	$fs = $this->Spec($iName);
	$txt = file_get_contents($fs);
	return $txt;
    }
    public function Write($iName,$iText) {
	$fs = $this->Spec($iName);
	$cnt = file_put_contents($fs,$iText);
	return $cnt;
    }
}
