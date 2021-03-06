<?php
/*
  FILE: dropins/site/dropin.php -- drop-in module for viewing loaded drop-in modules
    Basically, these are the admin UI versions of the drop-in manager and module classes.
  HISTORY:
    2014-02-24 started
    2016-12-05 this was moved back into Ferreteria awhile ago; renaming accordingly
    2017-01-03 A bit of class reorganization is required. We need to have two separate table classes - one for the loading, and one for the admin UI.
      This one should just grab data from the singleton loader-version.
      Tentatively, the recordset admin class can be descended from the recordset loading class as before.
    2017-03-28 y2017 remediation; not sure what I meant about the class reorg. Maybe the interfaces accomplished this?
    2018-03-25 moved ClassList() and RenderClassName() here, because this is the only place they're used.
*/
class fctDropInManager extends fcDataTable_array implements fiEventAware, fiLinkableTable {
    use ftLinkableTable;
    use ftExecutableTwig;
    
    // ++ SETUP ++ //

    // OVERRIDE
    protected function SingularName() {
	return 'fcrAdminDropInModule';
    }
    // CEMENT
    public function GetActionKey() {
	return KS_ACTION_KEY_DROPINS;
    }

    // -- SETUP -- //
    // ++ EVENTS ++ //
  
    protected function OnCreateElements() {}
    protected function OnRunCalculations() {
	$oPage = fcApp::Me()->GetPageObject();
	$oPage->SetPageTitle('Installed Drop-ins');
	//$oPage->SetBrowserTitle('Suppliers (browser)');
	//$oPage->SetContentTitle('Suppliers (content)');
    }
    public function Render() {
	return $this->AdminPage();
    }

    // -- EVENTS -- //
    // ++ ADMIN UI ++ //

    /*----
      NOTE: The reason we have to get another dropin manager and copy its data
	is that the non-dropin version of the dropin manager always loads first --
	so if we're going to do admin on it, we have to explicitly load the admin
	version (which is $this) and copy from the existing object (the one from
	the App object).
    */
    protected function AdminPage() {
	$tbl = fcApp::Me()->GetDropinManager();
	$this->SetAllRows($tbl->GetAllRows());	// copy data from loader-table to admin-table
	$rs = $this->GetAllRecords();		// copy data from admin-table to admin-recordset
	return $rs->AdminRows();		// render the dropin list
    }

    // -- ADMIN IU -- //
}

class fcrAdminDropInModule extends fcrDropInModule {

    // ++ WEB OUTPUT ++ //

    /*----
      PUBLIC because it is called from the Table wrapper object
    */
    public function AdminRows() {
	$nRows = $this->RowCount();
	if ($nRows > 0) {
	    $out = "<div class=content>$nRows dropin"
	      .fcString::Pluralize($nRows)
	      .'</div>'
	      ."\n<table class=listing>"
	      ;
	    $this->RewindRows();
	    
	    $odd = FALSE;
	    while ($this->NextRow()) {
		$odd = !$odd;
		$sClass = $odd?'odd':'even';
		$out .=
		  "\n  <tr class=$sClass>"
		  .$this->AdminRow()
		  ."\n</tr>"
		  ;
	    }
	    $out .= "\n</table>";
	} else {
	    $out = 'Apparently there are no dropins currently available.';
	}
	return $out;
    }
    /*----
      PUBLIC because it is called from the Table wrapper object
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
    
    // -- WEB OUTPUT -- //
    // ++ UTILITY FUNCTIONS ++ //

    static protected function ClassList($vClasses,$sSep=' ') {
	$out = NULL;
	if (is_array($vClasses)) {
	    // value is an array of class names for file $sFile
	    foreach ($vClasses as $sClass) {
		if (!is_null($out)) {
		    $out .= $sSep;
		}
		$out .= static::RenderClassName($sClass);
	    }
	} else {
	    // assume value is a single class name
	    $out = static::RenderClassName($vClasses);
	}
	return $out;
    }
    static protected function RenderClassName($sClass) {
	if (class_exists($sClass)) {
	    $out = "<b>C:</b>$sClass";
	} elseif (trait_exists($sClass)) {
	    $out = "<b>T:</b>$sClass";
	} elseif (interface_exists($sClass)) {
	    $out = "<b>I:</b>$sClass";
	} else {
	    $out = "<s title='class, trait, or interface not found'>$sClass</s>";
	}
	return $out;
    }

    // -- UTILITY FUNCTIONS -- //

}