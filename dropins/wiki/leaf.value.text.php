<?php

/*::::
  PURPOSE: handles the node values table
  TODO: should probably be renamed fctNodeLeaf_Text
*/
class fctLeafValuesText extends fctLeafValues {

    // ++ SETUP ++ //

    protected function SingularName() {
	return 'fcrLeafValueText';
    }
    protected function TableName() {
	return 'tf_leaf_text';
    }
    public function GetTypeString() {
	return 'text';
    }

    // -- SETUP -- //
    // ++ DATA WRITE ++ //

    /*----
      ACTION: saves the value for the given leaf, either creating or updating the Leaf Value record as needed
      INPUT:
	$idLeaf = ID of Leaf to which this value should be attached
	$sqlVal = value for Leaf, in STORAGE format
      RETURNS: nothing
    */
    public function SaveValueRecord($idLeaf,$sqlVal) {
	$rc = $this->SelectRecords('ID_Leaf='.$idLeaf);
	if ($rc->RowCount() == 0) {
	    // create new record
	    $ar = array(
	      'ID_Leaf'	=> $idLeaf,
	      'Value'	=> $sqlVal
	      );
	    $this->Insert($ar);
	} else {
	    // update existing record
	    $rc->SetKeyValue($idLeaf);
	    $ar = array(
	      'Value'	=> $sqlVal
	      );
	    $rc->Update($ar);
	}
    }

    // -- DATA WRITE -- //
}
class fcrLeafValueText extends fcrLeafValue {
}