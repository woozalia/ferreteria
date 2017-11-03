<?php
/*
  PURPOSE: classes solely for debugging purposes
  HISTORY:
    2016-12-22 started, with lap-warming by Kestra
    2017-01-02 fcRowArray has now been replaced by fcDataRow_array
*/

trait ftInstanceCounter {
    private static $nInstance = 0;
    
    protected function ConstructInstance() {
	self::$nInstance++;
	return self::$nInstance;
    }
    protected function InstanceCount() {
	return self::$nInstance;
    }
}

/*::::
  SAMPLE USAGE:
    $oTrace = new fcStackTrace();
    echo $oTrace->RenderAllRows();
*/  
class fcStackTrace extends fcDataRow_array {

    public function __construct() {
	$this->LoadTrace(1);
    }

    public function LoadTrace($nRemove=0) {
	// get the backtrace array
	$ar = debug_backtrace();
	// knock off the first n items (calls within this class)
	for ($idx = 0; $idx < $nRemove; $idx++) {
	    array_shift($ar);
	}
	$this->SetAllRows($ar);
    }

    // ++ FIELD VALUES ++ //
    
    protected function FileSpec() {
	return $this->GetFieldValue('file');
    }
    protected function LineNumber() {
	return $this->GetFieldValue('line');
    }
    protected function FunctionName() {
	return $this->GetFieldValue('function');
    }
    protected function ClassName() {
	return $this->GetFieldValue('class');
    }
    protected function Object() {	// not sure exactly what this field means
	return $this->GetFieldValue('object');
    }
    protected function MethodTypeString() {	// '::' if static call, '->' if dynamic call
	return $this->GetFieldValue('type');
    }
    protected function ArgsArray() {
	return $this->GetFieldValue('args');
    }
    
    // -- FIELD VALUES -- //
    // ++ FIELD CALCULATIONS ++ //
    
    protected function ArgsString() {
	$out = NULL;
	foreach ($this->ArgsArray() as $arg) {
	    if (!is_null($out)) {
		$out .= ',';
	    }
	    if (is_scalar($arg)) {
		$out .= $arg;
	    } elseif (is_array($arg)) {
		$nElem = count($arg);
		$out .= "(array: $nElem".fcString::Pluralize($nElem).')';
	    } else {
		$out .= '('.get_class($arg).' object)';
	    }
	}
	return $out;
    }
    protected function FunctionString() {
	return
	  $this->ClassName()
	  .$this->MethodTypeString()
	  .$this->FunctionName().'('.$this->ArgsString().')'
	  ;
    }
    
    // -- FIELD CALCULATIONS -- //
    // ++ UI OUTPUT ++ //
    
    public function RenderAllRows() {
	$out = "\n<table>";
	$this->RewindRows();
	while ($this->NextRow()) {
	    $sRow = $this->RenderRow();
	    $out .= "\n<tr>\n$sRow\n</tr>";
	}
	$out .= "\n</table>";
	return $out;
    }
    protected function RenderRow() {
	return "\n<td align=right>".$this->FileSpec()."</td>"
	  .'<td> '.$this->LineNumber().'</td>'
	  .'<td>'.$this->FunctionString().'</td>'
	  .'</tr>'
	  ;
    }
    
    // -- UI OUTPUT ++ //
}