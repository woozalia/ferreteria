<?php
/*
  PURPOSE: Deprecated class and functions from data.php
  HISTORY:
    2016-07-14 extracted from data.php
*/

class clsSQLFilt {
    private $arFilt;
    private $strConj;
    private $sVerb;	// NOT YET IMPLEMENTED
    private $sSort;
    private $nMax;

    public function __construct($iConj) {
	$this->strConj = $iConj;
	$this->arFilt = NULL;
	$this->sVerb = NULL;
	$this->sSort = NULL;
	$this->nMax = NULL;
    }
    public function Verb($sVerb) {	// NOT YET IMPLEMENTED
	$this->sVerb = $sVerb;
    }
    public function Order($sSort=NULL) {
	if (!empty($sSort)) {
	    $this->sSort = $sSort;
	}
	return $this->sSort;
    }
    protected function HasOrder() {
	return (!is_null($this->sSort));
    }
    protected function RenderOrder() {
	if ($this->HasOrder()) {
	    return ' ORDER BY '.$this->Order();
	} else {
	    return NULL;
	}
    }
    public function Limit($nMax=NULL) {
	if (!empty($nMax)) {
	    $this->nMax = $nMax;
	}
	return $this->nMax;
    }
    protected function HasLimit() {
	return (!is_null($this->nMax));
    }
    protected function RenderLimit() {
	if ($this->HasLimit()) {
	    return ' LIMIT '.$this->Limit();
	} else {
	    return NULL;
	}
    }
    /*-----
      ACTION: Add a condition
    */
    public function AddCond($iSQL) {
	$this->arFilt[] = $iSQL;
    }
    public function RenderFilter($sqlPrefix=NULL) {
	$out = NULL;
	if (is_array($this->arFilt)) {
	    foreach ($this->arFilt as $sql) {
		if ($out != '') {
		    $out .= ' '.$this->strConj.' ';
		}
		$out .= '('.$sql.')';
	    }
	}
	if (is_null($out)) {
	    return '';
	} else {
	    return $sqlPrefix.$out;
	}
    }
    // TODO: render the selection part as well
    public function RenderQuery() {
	$sql = $this->RenderFilter('WHERE ')
	  .$this->RenderOrder()
	  .$this->RenderLimit()
	  ;
	return $sql;
    }
}
/* ========================
 *** UTILITY FUNCTIONS ***
*/
/*----
  PURPOSE: This gets around PHP's apparent lack of built-in object type-conversion.
  ACTION: Copies all public fields from iSrce to iDest
*/
function CopyObj(object $iSrce, object $iDest) {
    foreach($iSrce as $key => $val) {
	$iDest->$key = $val;
    }
}
if (!function_exists('Pluralize')) {
    function Pluralize($iQty,$iSingular='',$iPlural='s') {
	  if ($iQty == 1) {
		  return $iSingular;
	  } else {
		  return $iPlural;
	  }
  }
}

function SQLValue($iVal) {
    throw new exception('SQLValue() is deprecated. Use... umm, something else.');
    if (is_array($iVal)) {
	foreach ($iVal as $key => $val) {
	    $arOut[$key] = SQLValue($val);
	}
	return $arOut;
    } else {
	if (is_null($iVal)) {
	    return 'NULL';
	} else if (is_bool($iVal)) {
	    return $iVal?'TRUE':'FALSE';
	} else if (is_string($iVal)) {
	    $oVal = '"'.mysql_real_escape_string($iVal).'"';
	    return $oVal;
	} else {
    // numeric can be raw
    // all others, we don't know how to handle, so return raw as well
	    return $iVal;
	}
    }
}
function SQL_for_filter(array $iVals) {
    $sql = NULL;
    foreach ($iVals as $name => $val) {
	if (!is_null($sql)) {
	    $sql .= ' AND ';
	}
	$sql .= '('.$name.'='.SQLValue($val).')';
    }
throw new exception('How did we get here?');
    return $sql;
}
function NoYes($iBool,$iNo='no',$iYes='yes') {
    if ($iBool) {
	return $iYes;
    } else {
	return $iNo;
    }
}

function nz(&$iVal,$default=NULL) {
    return empty($iVal)?$default:$iVal;
}
/*-----
  FUNCTION: nzAdd -- NZ Add
  RETURNS: ioVal += iAmt, but assumes ioVal is zero if not set (prevents runtime error)
  NOTE: iAmt is a reference so that we can pass variables which might not be set.
    Need to document why this is better than being able to pass constants.
*/
function nzAdd(&$ioVal,$nAmt=NULL) {
    $intAmt = is_null($nAmt)?0:$nAmt;
    if (empty($ioVal)) {
	$ioVal = $intAmt;
    } else {
	$ioVal += $intAmt;
    }
    return $ioVal;
}
/*-----
  FUNCTION: nzApp -- NZ Append
  PURPOSE: Like nzAdd(), but appends strings instead of adding numbers
*/
function nzApp(&$ioVal,$iTxt=NULL) {
    if (empty($ioVal)) {
	$ioVal = $iTxt;
    } else {
	$ioVal .= $iTxt;
    }
    return $ioVal;
}
/*----
  HISTORY:
    2012-03-11 iKey can now be an array, for multidimensional iArr
*/
function nzArray(array $iArr=NULL,$iKey,$iDefault=NULL) {
    $out = $iDefault;
    if (is_array($iArr)) {
	if (is_array($iKey)) {
	    $out = $iArr;
	    foreach ($iKey as $key) {
		if (array_key_exists($key,$out)) {
		    $out = $out[$key];
		} else {
		    return $iDefault;
		}
	    }
	} else {
	    if (array_key_exists($iKey,$iArr)) {
		$out = $iArr[$iKey];
	    }
	}
    }
    return $out;
}
function nzArray_debug(array $iArr=NULL,$iKey,$iDefault=NULL) {
    $out = $iDefault;
    if (is_array($iArr)) {
	if (is_array($iKey)) {
	    $out = $iArr;
	    foreach ($iKey as $key) {
		if (array_key_exists($key,$out)) {
		    $out = $out[$key];
		} else {
		    return $iDefault;
		}
	    }
	} else {
	    if (array_key_exists($iKey,$iArr)) {
		$out = $iArr[$iKey];
	    }
	}
    }
//echo '<br>IARR:<pre>'.print_r($iArr,TRUE).'</pre> KEY=['.$iKey.'] RETURNING <pre>'.print_r($out,TRUE).'</pre>';
    return $out;
}
/*----
  PURPOSE: combines the two arrays without changing any keys
    If entries in arAdd have the same keys as arStart, the result
      depends on the value of iReplace
    If entries in arAdd have keys that don't exist in arStart,
      the result depends on the value of iAppend
    This probably means that there are some equivalent ways of doing
      things by reversing order and changing flags, but I haven't
      worked through it yet.
  INPUT:
    arStart: the starting array - key-value pairs
    arAdd: additional key-value pairs to add to arStart
    iReplace:
      TRUE = arAdd values replace same keys in arStart
  RETURNS: the combined array
  NOTE: You'd think one of the native array functions could do this...
    array_merge(): "Values in the input array with numeric keys will be
      renumbered with incrementing keys starting from zero in the result array."
      (This is a problem if the keys are significant, e.g. record ID numbers.)
  HISTORY:
    2011-12-22 written because I keep needing it for cache mapping functions
    2012-03-05 added iReplace, iAppend
*/
function ArrayJoin(array $arStart=NULL, array $arAdd=NULL, $iReplace, $iAppend) {
    if (is_null($arStart)) {
	$arOut = $arAdd;
    } elseif (is_null($arAdd)) {
	$arOut = $arStart;
    } else {
	$arOut = $arStart;
	foreach ($arAdd as $key => $val) {
	    if (array_key_exists($key,$arOut)) {
        	if ($iReplace) {
		    $arOut[$key] = $val;
    		}
	    } else {
    		if ($iAppend) {
		    $arOut[$key] = $val;
    		}
	    }
	}
    }
    return $arOut;
}
/*----
  RETURNS: an array consisting only of keys from $arKeys
    plus the associated values from $arData
*/
function ArrayFilter_byKeys(array $arData, array $arKeys) {
    foreach ($arKeys as $key) {
	if (array_key_exists($key,$arData)) {
	    $arOut[$key] = $arData[$key];
	} else {
	    echo 'KEY ['.$key,'] not found.';
	    echo ' Array contents:<pre>'.print_r($arData,TRUE).'</pre>';
	    throw new exception('Expected key not found.');
	}
    }
    return $arOut;
}
function ifEmpty(&$iVal,$iDefault) {
    if (empty($iVal)) {
	return $iDefault;
    } else {
	return $iVal;
    }
}
function FirstNonEmpty(array $iList) {
    foreach ($iList as $val) {
	if (!empty($val)) {
	    return $val;
	}
    }
}
/*----
  ACTION: Takes a two-dimensional array and returns it flipped diagonally,
    i.e. each element out[x][y] is element in[y][x].
  EXAMPLE:
    INPUT      OUTPUT
    +---+---+  +---+---+---+
    | A | 1 |  | A | B | C |
    +---+---+  +---+---+---+
    | B | 2 |  | 1 | 2 | 3 |
    +---+---+  +---+---+---+
    | C | 3 |
    +---+---+
*/
function ArrayPivot($iArray) {
    foreach ($iArray as $row => $col) {
	if (is_array($col)) {
	    foreach ($col as $key => $val) {
		$arOut[$key][$row] = $val;
	    }
	}
    }
    return $arOut;
}
/*----
  ACTION: convert an array to SQL for filtering
  INPUT: iarFilt = array of filter terms; key is ignored
*/
function Array_toFilter($iarFilt) {
    $out = NULL;
    if (is_array($iarFilt)) {
	foreach ($iarFilt as $key => $cond) {
	    if (!is_null($out)) {
		$out .= ' AND ';
	    }
	    $out .= '('.$cond.')';
	}
    }
    return $out;
}
