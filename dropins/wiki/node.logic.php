<?php
/*
  PURPOSE: classes for managing basic node records
  HISTORY:
    2017-08-22 created because we need something instantiable
    2017-10-15 added ftrNodeLogic
*/
trait ftrNodeLogic {

    // ++ FIELD ACCESS ++ //
/*    
    public function GetKeyValue() {
	return $this->GetFieldValue('ID');
    }*/
    /*----
      USAGE: for when we're loading the base record of an unknown Node Type
	Classes for specific Node Types will override this and return a constant value.
    */
    public function GetTypeString() {
	return $this->GetFieldValue('Type');
    }

    // -- FIELD ACCESS -- //
}

class fctNodesLogic extends fctNodesBase {
    protected function SingularName() {
	return 'fcrNodeLogic';
    }
}
class fcrNodeLogic extends fcrNodeBase {
    use ftrNodeLogic;
}
