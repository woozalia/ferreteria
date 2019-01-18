<?php
/*
  PURPOSE: drop-in module system
  HISTORY:
    2013-11-28 started
    2014-12-27 split into two classes (one for each drop-in, and one for the drop-in manager)
    2017-01-01 classes now descend from fcTable_wRecords (was fcDataSource) and fcSourcedDataRow
    2017-04-09 evidently this was later changed to fcDataTable_array / fcDataRow_array
*/

define('KS_DROPIN_FIELD_FEATURES','features');

class fcDropInManager extends fcDataTable_array {

    // ++ STATIC ++ //
    
    // METHOD: Goes through App object-factory so we don't get more than one regardless of how it's requested.
    static public function Me() {
	return fcApp::Me()->GetDropinManager();
    }

    // -- STATIC -- //
    // ++ CEMENT ++ //
    
    protected function SingularName() {
	return 'fcrDropInModule';
    }

    // -- CEMENT -- //
    // ++ MODULES ++ //
    
    protected function SetModule(fcrDropInModule $oMod) {
	$this->SetRow($oMod->Name(),$oMod->GetFieldValues());
    }
    protected function HasModules() {
	return ($this->RowCount() > 0);	// 2018-02-05 not sure why HasRows() isn't available
    }
    // PUBLIC because outside callers sometimes need to know if a module exists or not
    public function HasModule($sName) {
	if (!$this->HasModules()) {
	    throw new exception('Ferreteria usage error: attempting to query dropin modules when none have been found.');
	}
	return $this->HasRow($sName);
    }
    
    // -- MODULES -- //
    // ++ BASIC OPERATIONS ++ //
    
    /*----
      ACTION: scans the given folder and adds any drop-in modules found
    */
    static public function ScanDropins($fsFolder,fcTreeNode $oMenu) {
	if (!file_exists($fsFolder)) {
	    throw new exception('Dropins folder "'.$fsFolder.'" does not exist.');
	}
	$oMgr = self::Me();
	$oMgr->CheckFolders($fsFolder,$oMenu);
    }
    protected function CheckFolders($fsFolder,fcTreeNode $oMenu) {
	$poDir = dir($fsFolder);
	$ar = NULL;
	while (FALSE !== ($fnFile = $poDir->read())) {
	    if (($fnFile!='.') && ($fnFile!='..')) {
		$fs = $fsFolder.'/'.$fnFile;
		if (is_dir($fs)) {
		    // save in an array so we can sort before loading
		    $ar[$fnFile] = $fs;
		}
	    }
	}
	if (is_null($ar)) {
	    throw new exception("No subfolders were found in Dropin folder '$fsFolder'.");
	}
	ksort($ar);
	foreach ($ar as $fn => $fs) {
	    $this->CheckFolder($fs,$oMenu);
	}
	if (!$this->HasModules()) {
	    throw new exception("No Dropins were found in '$fsFolder'.");
	}
    }
    protected function CheckFolder($fsFolder,fcTreeNode $oMenu) {
	$fsIndex = $fsFolder.'/'.KFN_DROPIN_INDEX;
	if (is_file($fsIndex)) {
	    fcCodeModule::BasePath($fsFolder.'/');
	    $od = $this->ProcessIndex($fsIndex,$oMenu);
	    $this->SetModule($od);
	}
    }
    
    protected function ProcessIndex($fsIndex,fcTreeNode $oMenu) {
	// set up environment

	$oRoot = $oMenu;			// the module index expects this

	// INPUT: $oRoot
	require($fsIndex);			// load the module index
	// OUTPUT: $arDropin

	$od = $this->SpawnRecordset();
	if (isset($arDropin)) {
	    $od->SetSpecs($arDropin);		// create dropin object to hold module's specs
	} else {
	    throw new error("Dropin error: $fsIndex does not define \$arDropin.");
	}
	
	return $od;
    }

    // -- BASIC OPERATIONS -- //
}

/*::::
  FIELDS:
    [name]: short name for module
    [descr]: one-line description
    [version]: version number (can be non-numeric)
    [date]: release date in YYYY/MM/DD format
    [URL]: URL for more information about the module
*/
class fcrDropInModule extends fcDataRow_array {

    // ++ SETUP ++ //
    
    // ACTION: Load specs array and do any additional per-module processing.
    public function SetSpecs(array $arSpecs) {
	$this->SetFieldValues($arSpecs);
	$this->RegisterClasses();
    }
    /*----
      ACTION: register any classes defined within the dropin.
    */
    protected function RegisterClasses() {
	$arCls = $this->ClassArray();
	foreach ($arCls as $sFile => $sClasses) {
	    $om = new fcCodeModule(__FILE__, $sFile);
	    if (is_array($sClasses)) {
		// value is an array of class names for file $sFile
		foreach ($sClasses as $sClass) {
		    $om->AddClass($sClass);
		}
	    } else {
		// assume value is a single class name
		$om->AddClass($sClasses);
	    }
	}
    }

    // -- SETUP -- //
    // ++ FIELD VALUES ++ //

    public function Name() {
	return $this->GetFieldValue('name');
    }
    protected function ClassArray() {
	return $this->GetFieldValue('classes');
    }

    // -- FIELD VALUES -- //
}