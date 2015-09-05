<?php
/*
  PURPOSE: menu-aware data base class
  HISTORY:
    2014-06-10 Extracting useful non-vbz-specific bits from vbz-data.php
    2015-07-12 resolving conflicts with other edited version
*/
class clsDataTable_Menu extends clsTable {
    /*----
      NOTE: Not sure if this is really necessary, but leaving it for now.
	It may be helpful during development. Perhaps the base method should
	be modified. (It includes everything except the default to the
	singular class-name.)
    */
    public function ActionKey($iVal=NULL) {
	if (!is_null($iVal)) {
	    $this->ActionKey = $iVal;
	}
	if (isset($this->ActionKey)) {
	    return $this->ActionKey;
	} else {
	    return $this->ClassSng();
	}
    }
}
/*%%%%
  PURPOSE: intermediate recordset class that doesn't assume a standalone application implemented with Ferreteria
*/
class clsDataRecord_admin extends clsDataSet {

    // ++ BOILERPLATE ++ //
      // table classes that don't descend from this class can just copy/paste these methods

      // ++ BOILERPLATE: event logging ++ //

    private $oLogger;
    protected function Log() {
	if (empty($this->oLogger)) {
	    $tLog = $this->Engine()->App()->Events();
	    // alternative way to create Events object:
	    //$tLog = VCM_Syslog::SpawnTable($this->Engine());
	    $this->oLogger = new clsLogger_DataSet($this,$tLog);
	}
	return $this->oLogger;
    }
    public function StartEvent(array $iarArgs) {
	return $this->Log()->StartEvent($iarArgs);
    }
    public function FinishEvent(array $iarArgs=NULL) {
	return $this->Log()->FinishEvent($iarArgs);
    }
    public function CreateEvent(array $arArgs) {
	return $this->Log()->CreateEvent($arArgs);
    }
    public function EventListing() {
	return $this->Log()->EventListing();
    }

      // ++ BOILERPLATE: self-linkage ++ //

    /*----
      USED BY: VbzAdmin::VbzAdminStkItems::Listing_forItem()
      HISTORY:
	2010-10-06 Disabled, because it wasn't clear if anyone was using it.
	  Thought I checked VbzAdmin, WorkFerret, and AudioFerret
	2010-10-13 VbzAdmin::VbzAdminStkItems::Listing_forItem() calls it
	2013-12-14 Moved from mw/admin.php to vbz-data.php
    */
    public function AdminLink($iText=NULL,$iPopup=NULL,array $iarArgs=NULL) {
	return clsMenuData_helper_admin::_AdminLink($this,$iText,$iPopup,$iarArgs);
    }
    public function AdminRedirect(array $iarArgs=NULL,$sText=NULL) {
	return clsMenuData_helper_admin::_AdminRedirect($this,$iarArgs,$sText);
    }
    public function AdminURL($arArgs=NULL) {
	return clsMenuData_helper_admin::_AdminURL($this,$arArgs);
    }

    // -- BOILERPLATE -- //
    // ++ HELPER CALLBACKS ++ //

    public function IdentityValues() {
	$ar = array(
	  'page'	=> $this->Table()->ActionKey(),
	  'id'		=> $this->KeyValue(),
	  );
	return $ar;
    }
    public function BaseURL_rel() {
	return $this->Engine()->App()->Page()->BaseURL_rel();
    }

    // -- HELPER CALLBACKS -- //
    // ++ ADMIN UI ++ //

    public function AdminRows(array $arFields) {
	return
	  $this->AdminRows_start()
	  .$this->AdminRows_head($arFields)
	  .$this->AdminRows_rows($arFields)
	  .$this->AdminRows_finish()
	  ;
    }
    protected function AdminRows_start() {
	return "\n<table>";
    }
    protected function AdminRows_finish() {
	return "\n</table>";
    }
    /*----
      ACTION: Just render the table header, if there are data rows.
    */
    protected function AdminRows_head(array $arFields) {
	$out = NULL;
	if ($this->HasRows()) {
	    $out .= "\n  <tr>";
	    foreach ($arFields as $sField => $sLabel) {
		$out .= "\n    <th>$sLabel</th>";
	    }
	    $out .= "\n</tr>";
	}
	return $out;
    }
    /*----
      ACTION: Just render the data rows, if any. Return NULL if none.
    */
    protected function AdminRows_rows(array $arFields) {
	$out = NULL;
	if ($this->HasRows()) {
	    while ($this->NextRow()) {
		$out .= "\n  <tr>";
		foreach ($arFields as $sField => $sLabel) {
		    $htVal = $this->AdminField($sField);
		    $out .= "\n    $htVal";
		}
		$out .= "\n</tr>";
	    }
	}
	return $out;
    }
    /*----
      PURPOSE: This is basically a stub - override to provide formatting
    */
    protected function AdminField($sField) {
	$val = $this->Value($sField);
	return "<td>$val</td>";
    }

    // -- ADMIN UI -- //
}
/*%%%%
  PURPOSE: not sure. The implementation of BaseURL_rel() only works within Ferreteria's app framework,
    but surely there's a more general way of handling this.
*/
class clsDataRecord_Menu extends clsDataRecord_admin {

    // ++ HELPER CALLBACKS ++ //

    public function BaseURL_rel() {
	return $this->Engine()->App()->Page()->BaseURL_rel();
    }

    // -- HELPER CALLBACKS -- //
}