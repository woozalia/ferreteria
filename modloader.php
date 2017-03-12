<?php
/*
  FILE: modloader.php
  PURPOSE: class for managing library loading
  NOTES:
    Libaries and Modules don't actually know about each other. Loading a module loads the library definition file (typically @libs.php
      or config-libs.php), which may cause (additional) libraries to be registered. When a library is loaded, it adds more Modules.
  HISTORY:
    2009-07-05 Trying to make this usable: shortened method names, added Path(), AddLog()
    2009-10-06 IsLoaded()
    2010-10-06 Log shows if update is not a change
    2011-09-15 Add() now has a "force" parameter to override an existing entry.
      By default, it does not replace existing entries.
    2012-04-18 class autoload registration
    2013-01-02 substantially rewritten - new clsModule class interface; clsLibMgr deprecated
    2013-08-28 moved clsModule to modloader.php (discarded clsLibMgr)
      The clsModule and clsLibMgr ways of doing things are incompatible, so they should be separate.
      Adding class management to clsModule -- renaming methods to specify class or module
    2013-09-12 Added function indexing ability.
      ...which should probably be deprecated in favor of static class functions.
    2015-03-14 Adding support for "libraries" (clsLibrary), i.e. collections of modules
      whose location is known but which are only loaded into the index on request
    2016-10-01 some documentation and reorganization; debugging function
    2017-01-01 renaming clsModule -> fcCodeModule, clsLibrary -> fcCodeLibrary
      Moved into Ferreteria folder, so it will be included in the repository
      rather than needing to be managed separately. It's unlikely to be used without Ferreteria --
      but if it is, it can always be extracted.
*/

class fcCodeModule {
    private static $arMods;	// list of modules
    private static $arCls;	// index of classes: arCls[class] => module name
    private static $arFx;	// index of functions: arFx[fx name] => module name
    private static $fpBase;	// base path for all module specs
    private static $doDebug;
    private static $nDepth = 0;	// recursion depth for loading modules

    private $strName;	// name by which to refer to module
    private $fsModule;	// filespec to loadable module
    private $fsCaller;	// filespec of caller
    private $isLoaded;	// TRUE = this module has already been loaded (included)

    // ++ DEPRECATED ++ //

      //++functions++//
      
      // These are deprecated because I'm trying to get rid of function registration. Use static class methods instead, and register the class.
    
    public function AddFunc($iName) {
	self::_AddFunc($this, $iName);
    }
    /*----
      ASSUMES: static stuff has been initialized, i.e. Register() has been called at least once
      USED BY: clsLibMgr::Path()
    */
    public static function ByName($iName) {
	if (self::Exists($iName)) {
	    return self::$arMods[$iName];
	} else {
	    return NULL;
	}
    }
    private static function _AddFunc(fcCodeModule $iMod, $iFunc) {
	$sMod = $iMod->Key();
	self::$arFx[$iFunc] = $sMod;
    }
    private static function HasFunc($iName) {
	return self::ArrayHas(self::$arFx,$iName);
    }
    public static function LoadFunc($iName) {
	if (self::HasFunc($iName)) {
	    $sMod = self::$arFx[$iName];
	    $oMod = self::$arMods[$sMod];
	    $oMod->Load();
	    return TRUE;
	} else {
	    // for debugging
	    echo "Attempting to load unknown function <b>$iName</b>.<br>";
	    throw new exception('Request for unknown function "'.$iName.'".');
	}
    }

      //--functions--//

    // -- DEPRECATED -- //
    // ++ SETUP ++ //

    /*----
      USAGE: new fcCodeModule(__FILE__, <module filespec>);
      HISTORY:
	2013-08-29 no longer has a keyname, so can no longer check for duplicates
    */
    public function __construct($iCallerSpec, $iModuleSpec) {
	self::Init();
	$this->strName = count(self::$arMods);
	$this->fsModule = self::BasePath().$iModuleSpec;
	$this->fsCaller = $iCallerSpec;
	$this->isLoaded = FALSE;

	self::Register($this);
    }
    /*----
      PUBLIC because... it's probably a bit of a kluge, but I haven't been able to sort it out.
	At least one library-registration file calls this.
    */
    public static function BasePath($iPath=NULL) {
	if (!is_null($iPath)) {
	    self::$fpBase = $iPath;
	}
	if (isset(self::$fpBase)) {
	    return self::$fpBase;
	} else {
	    return '';
	}
    }
    /*----
      PURPOSE: Sometimes this needs to be called explicitly because the class has not
	been instantiated; I'm not sure why.
    */
    public static function Init() {
	if (!isset(self::$arMods)) {
	    self::DebugLine('INITIALIZING MOD LOADER');
	    spl_autoload_register(__NAMESPACE__ .'\fcCodeModule::LoadClass');
	    self::$arMods = NULL;
	}
	if (!isset(self::$arCls)) {
	    self::$arCls = NULL;
	}
    }

    // -- SETUP -- //
    // ++ UTILITY ++ //
    
    /*----
      PURPOSE: like array_key_exists(), but parameters are in a sensible order,
	and doesn't choke if array is NULL or not set.
      NOTE:
	* We can't deprecate this and use fcArray::Exists(), because that's in a module.
	* However, it is currently only used by two other methods, one of which is deprecated,
	  so it may soon make more sense to roll it back into that method rather than being
	  a separate one.
    */
    static protected function ArrayHas(array $ar=NULL,$sKey) {
	if (is_null($ar)) {
	    return FALSE;
	} else {
	    return array_key_exists($sKey,$ar);
	}
    }
    
    // -- UTILITY -- //
    // ++ CLASS MANAGEMENT ++ //

    public function AddClass($iName) {
	self::_AddClass($this, $iName);
    }
    private static function HasClass($iName) {
	return self::ArrayHas(self::$arCls,$iName);
    }
    private static function _AddClass(fcCodeModule $iMod, $iClass) {
	$sMod = $iMod->Key();
	if (self::DebugMode()) {
	    self::DebugLine('ADDING CLASS <b>'.$iClass.'</b>');
	    if (array_key_exists($iClass,self::$arCls)) {
		$sModFnd = self::$arCls[$iClass];
		$oModFnd = self::ByName($sModFnd);
		self::DebugLine("<font color=red>WARNING</font>: class <b>$iClass</b> has already been registered for module \"".$oModFnd->Path().'".');
	    }
	}
	self::$arCls[$iClass] = $sMod;
    }
    public static function LoadClass($iName) {
	$doDbg = self::DebugMode();
	self::DebugLine('LOADING CLASS: <b>'.$iName.'</b>','');
	if (self::HasClass($iName)) {
	    $sMod = self::$arCls[$iName];
	    $oMod = self::$arMods[$sMod];
	    if ($doDbg) {
		echo ' - loading '.$oMod->Path().' {<br>';
	    }
	    self::$nDepth++;
	    $ok = $oMod->Load();
	    if ($ok) {
		$sStat = 'ok';
		if (!class_exists($iName)) {
		    $sStat .= ' but class still not found!';
		}
	    } else {
		$sStat = 'ERROR';
	    }
	    self::$nDepth--;
	    if ($doDbg) {
		self::DebugLine('}: '.$sStat);
	    }
	    return TRUE;
	} else {
	    if ($doDbg) {
		echo ' - class unknown. Available classes:<br><pre>';
		echo print_r(self::$arCls,TRUE);
		throw new exception("\n".'Request for unknown class "'.$iName.'".');
	    } else {
		return FALSE;
	    }
	}
    }

    // -- CLASS MANAGEMENT -- //
    // ++ MODULE MANAGEMENT ++ //

    public function Key() {
	return $this->strName;
    }
    public function Path() {
	return $this->fsModule;
    }
    /*----
      ASSUMES: static stuff has been initialized, i.e. Register() has been called at least once
    */
    protected static function Exists($iName) {
	if (isset(self::$arMods)) {
	    return array_key_exists($iName,self::$arMods);
	} else {
	    return FALSE;
	}
    }
    /*----
      USAGE: Should only be called internally to register new classes
      HISTORY:
	2013-08-28 removed initialization of self::$arMods
    */
    protected static function Register(fcCodeModule $iModule) {
	self::$arMods[$iModule->Key()] = $iModule;
	self::DebugLine('Registering module "'.$iModule->Path().'"');
    }
    public static function Load_byName($iName) {
	$ok = FALSE;
	if (self::Exists($iName)) {
	    $objMod = self::ByName($iName);
	    $ok = $objMod->Load();
	} else {
	    if (self::DebugMode()) {
		self::DebugLine("Attempting to load undefined module <b>$iName</b>.");
		throw new exception('Request for undefined module"'.$iName.'".');
	    }
	}
	return $ok;
    }
    /*----
      ACTION: Loads the code pointed to by the current Module object
      NOTES:
	* We want to prevent modules from being loaded more than once, but this is not an error if it happens.
	  Multiple plugins or independent libraries may be dependent on the same module.
    */
    protected function Load() {
	if ($this->isLoaded) {
	    $ok = TRUE;
	} else {
	    $ok = FALSE;
	    try {
		$fsMod = $this->Path();
		$strName = $this->Key();
		if (file_exists($fsMod)) {
		    require_once $fsMod;
		    $ok = TRUE;
		} else {
		    $fsCaller = $this->fsCaller;
		    //$intCaller = $this->intCaller;
		    echo "Module <b>$strName</b> could not be loaded because source file <b>$fsMod</b>, registered in <b>$fsCaller</b>, is not found.<br>";
		}
	    } catch(Exception $e) {
		echo "ModLoader could not load module [$strName] from [$fsMod]; error: <b>".$e->getMessage().'</b>';

		throw new exception('Module file could not be loaded.');
	    }
	}
	return $ok;
    }

    // -- MODULE MANAGEMENT -- //
    // ++ DEBUGGING ++ //

    public static function DebugMode($iOn=NULL) {
	if (!is_null($iOn)) {
	    self::$doDebug = $iOn;
	}
	return isset(self::$doDebug)?self::$doDebug:FALSE;
    }
    private static function DebugLine($iTxt,$iEnd='<br>') {
	if (self::DebugMode()) {
	    $out = str_repeat(' -',self::$nDepth);
	    echo $out.$iTxt.$iEnd;
	}
    }

    // -- DEBUGGING -- //
    
}

/*::::
  PURPOSE: This class loads libraries, which are *indexes* to files containing class definitions.
    Load() does not load the classes themselves, but merely loads the indexes so that the class
    autoloader knows where to find the classes when they are needed. This reduces the loading of
    unneeded libraries without the caller having to know where the libraries are located.
*/
class fcCodeLibrary {
    private static $arLibs;	// list of libraries
    private static $fpBase;	// base path for index specs
    private $sName,$fsIndex;
    private $isLoaded;

    // ++ SETUP ++ //

    public function __construct($sName,$fsIndex) {
	$this->NameString($sName);
	$this->IndexSpec($fsIndex);
	
	// make sure the resulting filespec actually exists:
	$fs = $this->IndexSpec();
	if (!file_exists($fs)) {
	    echo "Trying to register <b>$fs</b> as a module index:<pre>";
	    throw new exception('Ferreteria usage error: Specified module index file does not exist. Has it been renamed?');
	}
	
	$this->isLoaded = FALSE;
	self::Add($this);
    }
    public function NameString($sName=NULL) {
	if (!is_null($sName)) {
	    $this->sName = $sName;
	}
	return $this->sName;
    }
    public function IndexSpec($fsIndex=NULL) {
	if (!is_null($fsIndex)) {
	    $this->fsIndex = self::$fpBase.$fsIndex;
	}
	return $this->fsIndex;
    }

    // -- SETUP -- //
    // ++ ACTIONS ++ //

    protected function Load() {
	$fs = $this->IndexSpec();
	if (file_exists($fs)) {
	    require_once($fs);
	    $this->isLoaded = TRUE;
	} else {
	    throw new exception('2017-01-05 This should never happen anymore, since filespecs are checked when the module object is created.');
	}
    }

    // -- ACTIONS -- //
    // ++ STATIC: PUBLIC ++ //

    static public function Load_byName($sName) {
	if (self::Lib_exists($sName)) {
	    self::Lib_byName($sName)->Load();
	} else {
	    throw new exception('Library "'.$sName.'" has not been registered.');
	}
    }
    static public function BasePath($fpBase=NULL) {
	if (!is_null($fpBase)) {
	    self::$fpBase = $fpBase;
	}
	return self::$fpBase;
    }

    // -- STATIC: PUBLIC -- //
    // ++ STATIC: INTERNAL ++ //

    static protected function Lib_byName($sName,$oLib=NULL) {
	if (!is_null($oLib)) {
	    self::$arLibs[$sName] = $oLib;
	}
	return self::$arLibs[$sName];
    }
    static protected function Add(fcCodeLibrary $oLib) {
	self::Lib_byName($oLib->NameString(),$oLib);
    }
    static protected function Lib_exists($sName) {
	if (empty(self::$arLibs)) {
	    throw new exception('Attempting to query library list before adding any libraries.');
	} else {
	    return array_key_exists($sName,self::$arLibs);
	}
    }

    // -- STATIC: INTERNAL -- //
    // ++ DEBUGGING ++ //

    public function IsLoaded() {
	return $this->isLoaded;
    }
    public static function DumpList() {
	$out = NULL;
	if (isset(self::$arLibs)) {
	    if (is_array(self::$arLibs)) {
		if (count(self::$arLibs > 0)) {
		    $out = "Registered Libraries:\n<ul>";
		    foreach (self::$arLibs as $sName => $oLib) {
			$sStatus = $oLib->IsLoaded()?' LOADED':'';
			$out .= "<li>$sName$sStatus</li>\n";
		    }
		    $out .= "</ul>\n";
		} else {
		    $out = '$arLibs is an empty array.';
		}
	    } else {
		$out = '$arLibs is not an array';
	    }
	} else {
	    $out = '$arLibs is not set.';
	}
	return $out;
    }
    
    // -- DEBUGGING -- //
}

