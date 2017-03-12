<?php
/*
  PURPOSE: file operations, espcially those not directly supported by PHP native fx().
  HISTORY:
    2017-02-19 Started for Ferreteria Reports.
*/

/*::::
  PURPOSE: Partly so I don't have to keep stumbling around the PHP docs trying to find the path-parsing fx()...
    They are here, mixed in with some other things: http://php.net/manual/en/ref.filesystem.php
*/
class fcFileSpec {

    static public function ParentPathOf($fs,$nLevels=1) {
	return dirname($fs,$nLevels);
    }
    static public function NameAndExtOf($fs,$fsExt=NULL) {
	return basename($fs);
    }
}
/*::::
  NOTE: These system fx() already exist, so we don't need methods for them:
    file_exists($fs)
    copy($fsSource,$fsDest)
    rename($fsOld,$fsNew)
*/
class fcFileSystem {

    /*----
      ACTION: Make a backup copy of a file - or, optionally, just move it to the backup name.
      INPUT:
	$bCopy: if TRUE, copy the file to the backup name; if FALSE, just move it.
    */
    static public function MakeBackup($fs,$bCopy=TRUE) {
	$fsBak = self::MakeBackupName($fs);
	
	if (file_exists($fsBak)) {
	    // backup file already exists, so delete old one
	    $ok = unlink($fsBak);
	    if (!$ok) {
		throw new exception("Ferreteria file error: could not delete [$fs].");
	    }
	}
	if ($bCopy) {
	    copy($fs,$fsBak);
	} else {
	    rename($fs,$fsBak);
	}
    }

    // ACTION: Creates a folder for temporary use
    static public function CreateTemporaryFolder($sPrefix=NULL) {
	if (defined('FP_TEMPORARY_STORAGE')) {
	    $fpTemp = FP_TEMPORARY_STORAGE;
	} else {
	    $fpTemp = sys_get_temp_dir();
	}
	$fpOut = $fpTemp.'/ferreteria';
	if (!is_null($sPrefix)) {
	    $fpOut .= '/'.$sPrefix;
	}
	//$fpOut .= '/'.rand();	// random number for final folder, to minimize collisions
	
	if (file_exists($fpOut)) {
	    // if the folder exists, clear it out:
	    self::ClearFolder($fpOut);
	} else {
	    $ok = mkdir($fpOut,0700,TRUE);
	    if (!$ok) {
		throw new exception("Ferreteria file error: could not create temporary folder [$fpOut].");
	    }
	}
	return $fpOut;
    }
    static public function ClearFolder($fp) {
	$poDir = dir($fp);
	while (FALSE !== ($fn = $poDir->read())) {
	    if (($fn != '.') && ($fn != '..')) {
		$fs = $fp.'/'.$fn;
		if (is_dir($fs)) {
		    self::ClearFolder($fs);
		    rmdir($fs);
		} else {
		    unlink($fs);
		}
	    }
	}
    }
    
    // ++ BEHAVIORS ++ // -- for now, override by descending from this class

    static protected function MakeBackupName($fs) {
	$fp = fcFileSpec::ParentPathOf($fs);
	$fn = fcFileSpec::NameAndExtOf($fs);
	return $fp.'/~'.$fn;
    }
   
    // -- BEHAVIORS -- //
}