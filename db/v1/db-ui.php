<?php
/*
  PURPOSE: Traits that give Data objects a user interface
  NOTE: This *should* work with dbv2, but it was written for dbv1 and has not been tested with dbv2.
  HISTORY:
    2015-10-28 Adapted from methods in menu-data.php: clsDataRecord_admin
    2016-01-04 Added AdminRow_CSSclass() to allow row-striping.
    2016-01-12 $arFields and $arOptions are now set as local members, so they don't have to be
      passed to everything. Also, $arFields is optional because some overrides don't need it.
*/

trait ftShowableRecord {
    public function AdminRows(array $arFields=NULL,array $arOptions=NULL) {
	$this->AdminRows_settings_options($arOptions);
	if ($this->HasRows()) {
	    if (!is_null($arFields)) {
		$this->AdminRows_settings_columns($arFields);
	    }
	    $out =
	      $this->AdminRows_start()
	      .$this->AdminRows_head()
	      .$this->AdminRows_rows()
	      .$this->AdminRows_finish()
	      ;
	} else {
	    $out = $this->AdminRows_none();
	}
	$out .= $this->AdminRows_after();
	return $out;
    }
    private $arAdminRows_fields;
    protected function AdminRows_settings_columns(array $ar=NULL) {
	if (!is_null($ar)) {
	    $this->arAdminRows_fields = $ar;
	}
	if (empty($this->arAdminRows_fields)) {
	    // descendant must implement this method if it wants a default column set
	    if (method_exists($this,'AdminRows_settings_columns_default')) {
		$this->arAdminRows_fields = $this->AdminRows_settings_columns_default();
	    } else {
		$sClass = get_class($this);
		throw new exception("Class $sClass needs to define function AdminRows_settings_columns_default().");
	    } 	
	}
	return $this->arAdminRows_fields;
    }
    private $arAdminRows_options;
    protected function AdminRows_settings_options(array $ar=NULL) {
	if (!is_null($ar)) {
	    $this->arAdminRows_options = $ar;
	}
	return $this->arAdminRows_options;
    }
    protected function AdminRows_settings_option($sName,$val=NULL,$vDef=NULL) {
	if (!is_null($val)) {
	    $this->arAdminRows_options[$sName] = $val;
	}
	return clsArray::Nz($this->arAdminRows_options,$sName,$vDef);
    }
    protected function AdminRows_none() {
	return clsArray::Nz($this->AdminRows_settings_options(),'no.rows.html','<i>none</i>');
    }
    protected function AdminRows_start() {
	return "\n<table>";
    }
    protected function AdminRows_finish() {
	return "\n</table>";
    }
    protected function AdminRows_after() {
	// by default, nothing
    }
    /*----
      ACTION: Just render the table header, if there are data rows.
    */
    protected function AdminRows_head() {
	$out = $this->AdminRows_head_fromArray($this->AdminRows_settings_columns());
	return $out;
    }
    protected function AdminRows_head_fromArray(array $arFields) {
	$out = "\n  <tr>";
	foreach ($arFields as $sField => $sLabel) {
	    $out .= "\n    <th>$sLabel</th>";
	}
	$out .= "\n</tr>";
	return $out;
    }
    /*----
      ACTION: Just render the data rows, if any. Return NULL if none.
    */
    protected function AdminRows_rows() {
	$out = NULL;
	while ($this->NextRow()) {
	    $out .= $this->AdminRows_row();
	}
	return $out;
    }
    /*----
      ACTION: render a single data row
    */
    protected function AdminRows_row() {
	$cssClass = $this->AdminRow_CSSclass();
	$arFields = $this->AdminRows_settings_columns();
	$out = "\n  <tr class='$cssClass'>";
	foreach ($arFields as $sField => $sLabel) {
	    $htVal = $this->AdminField($sField);
	    $out .= "\n    $htVal";
	}
	$out .= "\n  </tr>";
	return $out;
    }
    /*----
      RETURNS: CSS class to use for the current row. This is typically to allow checkbook-style striping.
    */
    protected function AdminRow_CSSclass() {
	static $isOdd = FALSE;
	
	$isOdd = !$isOdd;
	return $isOdd?'odd':'even';
    }
    /*----
      PURPOSE: This is basically a stub - override to provide formatting
    */
    protected function AdminField($sField) {
	$val = $this->Value($sField);
	return "<td>$val</td>";
    }

}