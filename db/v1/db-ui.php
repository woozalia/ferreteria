<?php
/*
  PURPOSE: Traits that give Data objects a user interface
  NOTE: This *should* work with dbv2, but it was written for dbv1 and has not been tested with dbv2.
  HISTORY:
    2015-10-28 Adapted from methods in menu-data.php: clsDataRecord_admin
*/

trait ftShowableRecord {
    public function AdminRows(array $arFields,array $arOptions=NULL) {
	if ($this->HasRows()) {
	    $out =
	      $this->AdminRows_start($arOptions)
	      .$this->AdminRows_head($arFields,$arOptions)
	      .$this->AdminRows_rows($arFields,$arOptions)
	      .$this->AdminRows_finish($arOptions)
	      ;
	} else {
	    $out = $this->AdminRows_none($arOptions);
	}
	$out .= $this->AdminRows_after($arOptions);
	return $out;
    }
    protected function AdminRows_none(array $arOptions=NULL) {
	return clsArray::Nz($arOptions,'no.rows.html','<i>none</i>');
    }
    protected function AdminRows_start(array $arOptions=NULL) {
	return "\n<table>";
    }
    protected function AdminRows_finish(array $arOptions=NULL) {
	return "\n</table>";
    }
    protected function AdminRows_after(array $arOptions=NULL) {
	// by default, nothing
    }
    /*----
      ACTION: Just render the table header, if there are data rows.
    */
    protected function AdminRows_head(array $arFields,array $arOptions=NULL) {
	$out = NULL;
//	if ($this->HasRows()) {
	    $out .= "\n  <tr>";
	    foreach ($arFields as $sField => $sLabel) {
		$out .= "\n    <th>$sLabel</th>";
	    }
	    $out .= "\n</tr>";
//	}
	return $out;
    }
    /*----
      ACTION: Just render the data rows, if any. Return NULL if none.
    */
    protected function AdminRows_rows(array $arFields,array $arOptions=NULL) {
	$out = NULL;
//	if ($this->HasRows()) {
	    while ($this->NextRow()) {
		$out .= "\n  <tr>";
		foreach ($arFields as $sField => $sLabel) {
		    $htVal = $this->AdminField($sField,$arOptions);
		    $out .= "\n    $htVal";
		}
		$out .= "\n</tr>";
	    }
//	}
	return $out;
    }
    /*----
      PURPOSE: This is basically a stub - override to provide formatting
    */
    protected function AdminField($sField,array $arOptions=NULL) {
	$val = $this->Value($sField);
	return "<td>$val</td>";
    }

}