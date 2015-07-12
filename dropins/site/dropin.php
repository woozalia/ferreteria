<?php
/*
  FILE: dropins/site/dropin.php -- VbzCart drop-in module for managing drop-in modules
  HISTORY:
    2014-02-24 started
*/
class VCM_DropIn_Manager extends clsDropInManager {

    // // ++ STATIC ++ // //

    static protected function ItemClass() {
	return KS_CLASS_SITE_DROPIN_MODULE;
    }
    /*----
      ACTION: Converts all drop-in objects to admin class (VC_DropIns)
    */
    protected static function ConvertToAdmin() {
	$arOld = self::ModuleArray();
	$arNew = NULL;
	foreach ($arOld as $sName => $oOld) {
	    $oNew = self::SpawnItem($oOld->SpecArray());
	    $arNew[$oNew->Name()] = $oNew;
	}
	self::ModuleArray($arNew);
    }

    // // -- STATIC -- // //
    // ++ SETUP ++ //

/*
    public function __construct(clsDatabase_abstract $db,array $arSpec=array()) {	// needed for menu-based initialization
	parent::__construct($arSpec);
    }
*/
    public function __construct(clsDatabase_abstract $db) {
    }

    // -- SETUP -- //
    // ++ DROP-IN API ++ //

    /*----
      PURPOSE: execution method called by dropin menu
    */
    public function MenuExec(array $arArgs=NULL) {
	$this->arArgs = $arArgs;
	self::ConvertToAdmin();
	$out = $this->AdminPage();
	return $out;
    }

    // -- DROP-IN API -- //
    // ++ ADMIN API ++ //

    protected function AdminPage() {
	$arMods = static::ModuleArray();
	$out = "\n<table class=listing>";
	foreach ($arMods as $sName => $oMod) {
	    $out .=
	      "\n  <tr>"
	      .$oMod->AdminRow()
	      ."\n</tr>";
	}
	$out .= "\n</table>";
	return $out;
    }

    // -- ADMIN API -- //
}

class VCI_DropIn_Module extends clsDropInModule {
    /*----
      PUBLIC because it is called from a parent object
    */
    public function AdminRow() {
	$sName = $this->Name();
	$arCls = $this->ClassArray();
	$sClss = NULL;
	$sFiles = NULL;
	foreach ($arCls as $sFile => $vClasses) {
	    $sClss = static::ClassList($vClasses);
	    $sFiles .= "<b>$sFile</b>: $sClss<br>";
	}

	$out = "\n    <td valign=top>$sName</td><td>$sFiles</td>";
	return $out;
    }

    // -- ADMIN API -- //
}