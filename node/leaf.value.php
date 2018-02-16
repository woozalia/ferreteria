<?php

/*----
  PURPOSE: abstract base class for Leaf Type classes
  HISTORY:
    2017-08-26 created
    2017-10-07 so... why was fcrLeafValue storing Value locally? It should be referring to the field. Fixed.
*/
abstract class fctLeafValues extends fcTable_keyed_single_standard {
    abstract public function GetTypeString();
    abstract public function SaveValueRecord($idLeaf,$v);
    
    public function GetKeyName() {
	return 'ID_Leaf';
    }
}
abstract class fcrLeafValue extends fcRecord_standard {
    /*----
      API
      NOTE: descendant classes might do some validation on $v before storing it.
    */
    public function SetValue($v) {
	$this->SetFieldValue('Value',$v);
    }
    // PUBLIC because the whole point of a Leaf Value is being able to access its value at some point, y'know?
    public function GetValue() {
	return $this->GetFieldValue('Value');
    }
}