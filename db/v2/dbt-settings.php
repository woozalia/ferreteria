<?php
/*
  PURPOSE: class for managing persistent settings stored in a database table
    This is a table type which can be used as-is or further specialized.
  HISTORY:
    2016-11-03 Adapting from clsGlobalVars in VbzCart
*/

// TODO: Filtering by user should be available, either as an option or a descendant class, because it will eventually be needed.
abstract class fcSettingsTable_core extends fcTable_keyed_single_standard {
    
    // ++ SETUP ++ //
    
    protected function InitVars() {
	$this->InitCache();
    }
    
    // -- SETUP -- //
    // ++ CALLABLE API ++ //

    public function Exists($sName) {
	$rc = $this->GetRecord_forKey($sName);
	if ($rc->HasRows()) {
	    $this->SetCached($sName,$rc->GetFieldValue('Value'));
	    return TRUE;
	} else {
	    $this->DeleteCached($sName);
	    return FALSE;
	}
    }
    public function GetValue($sName) {
	if ($this->Exists($sName)) {
	    return $this->GetCached($sName); 	// Exists() loads it into the cache
	} else {
	    return NULL;
	}
    }
    public function SetValue($sName,$sValue) {
	$db = $this->GetConnection();
	$sqlVal = $db->Sanitize_andQuote($sValue);
	$sqlName = $db->Sanitize_andQuote($sName);

	if ($this->Exists($sName)) {
	    $sCode = 'VAR-U';
	    $sText = 'global edited';
	    $arUpd = array(
	      'Value' => $sqlVal,
	      'WhenUpdated' => 'NOW()'
	      );
	    $this->Update($arUpd,'Name='.$sqlName);
	} else {
	    $sCode = 'VAR-I';
	    $sText = 'global added';
	    $arIns = array(
	      'Name' => $sqlName,
	      'Value' => $sqlVal,
	      'WhenCreated' => 'NOW()'
	      );
	    $this->Insert($arIns);
	}
	$this->LogChange($sCode,$sText,$sName,$this->GetCached($sName),$sValue);
    }

    // -- CALLABLE API -- //
    // ++ CACHE ++ //

    private $arCache;
    protected function InitCache() {
	$this->arCache = array();	// so we don't have to check before using array fx()
    }
    protected function IsCached($sName) {
	return array_key_exists($sName,$this->arCache);
    }
    protected function SetCached($sName,$sValue) {
	$this->arCache[$sName] = $sValue;
    }
    protected function GetCached($sName) {
	return $this->arCache[$sName];
    }
    protected function DeleteCached($sName) {
	$this->SetCached($sName,NULL);
    }
    
    // -- CACHE -- //
    // ++ LOGGING ++ //
    
    protected function LogChange($sCode,$sText,$sName,$sValueOld,$sValueNew) {
	// stub function - does nothing in this implementation
    }
    
    // -- LOGGING -- //

}
abstract class fcSettingsTable_logged extends fcSettingsTable_core {

    // ++ LOGGING ++ //

    abstract protected function LogEvent($sCode,$sText,array $arParams);
    protected function LogChange($sCode,$sText,$sName,$sValueOld,$sValueNew) {
	$sWhere = __METHOD__;		// should return class::function
	
	$arParams = array(
	  'name'	=> $sName,
	  'old'		=> $sValueOld,
	  'new'		=> $sValueNew
	  );
	$this->LogEvent($sCode,$sText,$arParams);

    }
    
    // -- LOGGING -- //
}
class fcSettingsTable_standard extends fcSettingsTable_logged {
    use ftLoggableTable;

    // ++ CEMENTING ++ //

    protected function TableName() {
	return 'var_global';
    }
    protected function SingularName() {
	return 'fcSettingRecord';
    }
    public function KeyName() {
	return 'Name';
    }
    public function GetActionKey() {
	return 'set';
    }
    protected function LogEvent($sCode,$sText,array $arParams) {
	$arEv = array(
	  fcrEvent::KF_CODE		=> $sCode,
	  fcrEvent::KF_DESCR_START	=> $sText,
	  fcrEvent::KF_PARAMS		=> $arParams,
	  );
	$this->CreateEvent($arEv);
    }

    // -- CEMENTING -- //
}

//// RECORDSET ////

class fcSettingRecord extends fcRecord_keyed_single_string {
}