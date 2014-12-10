<?php
/*
  PURPOSE: drop-in module system
  HISTORY:
    2013-11-28 started
    2014-12-27 split into two classes (one for each drop-in, and one for the drop-in manager)
*/

define('KS_DROPIN_FIELD_FEATURES','features');

class clsDropInManager {
    private static $oMenu;		// menu root
    private static $arMods = array();	// modules scanned
    private static $arFeat = array();	// features loaded

    // ++ SETUP ++ //
/*
    public function __construct() {
	self::Me($this);
    }
    static private $me = NULL;
    static public function Me(clsApp $oApp=NULL) {
	if (is_null(self::$me)) {
	    self::$me = $oApp;
	}
	return self::$me;
    }
*/
    // -- SETUP -- //
    // ++ BASIC OPERATIONS ++ //

    static protected function ItemClass() {
	return 'clsDropInModule';
    }
    static protected function SpawnItem(array $arIndex) {
	$sCls = static::ItemClass();
	return new $sCls($arIndex);
    }
    /*----
      ACTION: scans the given folder and adds any drop-in modules found
    */
    static public function ScanDropins($fsFolder,clsMenuItem $oMenu) {
	self::$oMenu = $oMenu;
	$poDir = dir($fsFolder);
	while (FALSE !== ($fnFile = $poDir->read())) {
	    if (($fnFile!='.') && ($fnFile!='..')) {
		$fs = $fsFolder.'/'.$fnFile;
		if (is_dir($fs)) {
		    self::CheckFolder($fs);
		}
	    }
	}
    }
    static protected function CheckFolder($fsFolder) {
	$fsIndex = $fsFolder.'/'.KFN_DROPIN_INDEX;
	if (is_file($fsIndex)) {
	    clsModule::BasePath($fsFolder.'/');
	    $od = self::ProcessIndex($fsIndex);
	    self::$arMods[$od->Name()] = $od;
	}
    }
    static protected function ProcessIndex($fsIndex) {
	require($fsIndex);	// load the module index
	$od = self::SpawnItem($arDropin);	// create object for specs
	$om = $od->MenuObj();
	if (is_object($om)) {	// if there is a menu node...
	    self::$oMenu->NodeAdd($om);	// ...add it to the menu
	}
	if (array_key_exists(KS_DROPIN_FIELD_FEATURES,$arDropin)) {
	    $arAdd = $arDropin[KS_DROPIN_FIELD_FEATURES];
	    self::$arFeat = array_merge(self::$arFeat,$arAdd);
	}
	$od->RegisterClasses();
	return $od;
    }
    /*----
      RETURNS: TRUE iff the named drop-in module is available for use
      TODO: Rename to ModuleLoaded()
    */
    static public function IsReady($sName) {
	$isOk = FALSE;
	if (array_key_exists($sName,self::$arMods)) {
	    if (is_object(self::$arMods[$sName])) {
		$isOk = TRUE;
	    }
	}
	return $isOk;
    }
    static public function ModuleLoaded($sName) {
	return self::IsReady($sName);
    }
    static public function FeatureLoaded($sName) {
	return (in_array($sName,self::$arFeat));
    }

    // -- BASIC OPERATIONS -- //
    // ++ ADMIN API ++ //

    static protected function ModuleArray(array $ar=NULL) {
	if (!is_null($ar)) {
	    self::$arMods = $ar;
	}
	return self::$arMods;
    }

    // -- ADMIN API -- //
}

class clsDropInModule {
    private $arSpec;
    /*----
      INPUT: array of module specifications
	[name]: short name for module
	[descr]: one-line description
	[version]: version number (can be non-numeric)
	[date]: release date in YYYY/MM/DD format
	[URL]: URL for more information about the module
    */
    public function __construct(array $arSpec) {
	$this->arSpec = $arSpec;
    }
    public function SpecArray() {
	return $this->arSpec;
    }
    public function Name() {
	return $this->arSpec['name'];
    }
    protected function ClassArray() {
	return $this->arSpec['classes'];
    }
    /*----
      PUBLIC because Manager calls it
    */
    public function MenuObj() {
	return $this->arSpec['menu'];
    }
    /*----
      PUBLIC because Manager calls it
    */
    public function RegisterClasses() {
	$arCls = $this->ClassArray();
	foreach ($arCls as $sFile => $sClasses) {
	    $om = new clsModule(__FILE__, $sFile);
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

    // ++ UTILITY FUNCTIONS ++ //

    static protected function ClassList($vClasses,$sSep=' ') {
	$out = NULL;
	if (is_array($vClasses)) {
	    // value is an array of class names for file $sFile
	    foreach ($vClasses as $sClass) {
		if (!is_null($out)) {
		    $out .= $sSep;
		}
		if (class_exists($sClass)) {
		    $htCls = $sClass;
		} else {
		    $htCls = "<s>$sClass</s>";
		}
		$out .= $htCls;
	    }
	} else {
	    // assume value is a single class name
	    $out = $vClasses;
	}
	return $out;
    }

    // -- UTILITY FUNCTIONS -- //
}