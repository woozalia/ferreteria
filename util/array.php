<?php
/*
  FILE: array.php -- class for handling array functions
  HISTORY:
    2015-09-13 Removed the [brackets] from the output of clsArray::Render().
    2016-01-19 Fixed bug in Render() recursion.
    2017-02-22 auto-detection of CLI mode (this eventually belongs somewhere else)
*/

class fcArray {
    static private $sRenderIndent = '  ';
    static private $sRenderSeparator = " => \t";
    static private $sRenderNewLine = "\n";

    static protected function GetRenderPrefix() {
	if (php_sapi_name() == "cli") {
	    return '';
	} else {
	    return '<pre>';
	}
    }
    static protected function GetRenderSuffix() {
	if (php_sapi_name() == "cli") {
	    return '';
	} else {
	    return '</pre>';
	}
    }
    
    // ++ CALCULATIONS ++ //

    static public function Exists(array $ar=NULL,$key) {
	if (is_array($ar)) {
	    return array_key_exists($key,$ar);
	} else {
	    return FALSE;
	}
    }
    static public function Nz(array $ar=NULL,$key,$default=NULL) {
	$out = NULL;
	if (self::Exists($ar,$key)) {
	    $out = $ar[$key];
	} else {
	    $out = $default;
	}
	return $out;
    }
    // possibly this should be renamed NzInc()
    static public function NzSum(array &$ar=NULL,$key,$nVal=1) {
	if (self::Exists($ar,$key)) {
	    $ar[$key] += $nVal;
	} else {
	    $ar[$key] = $nVal;
	}
	return $ar;
    }
    static public function NzAppend(array &$ar=NULL,$key,$sVal) {
	if (self::Exists($ar,$key)) {
	    $ar[$key] .= $sVal;
	} else {
	    $ar[$key] = $sVal;
	}
	return $ar;
    }
    /*----
      ACTION: concatenates ar[sKey] with val. If both are not NULL, separates them with sSep.
	If val is blank ('') but not NULL, sSep will still be used. (If it frequently comes up
	that we don't want this to happen, then that behavior might be changed later.)
      HISTORY:
	2016-01-24 created for VbzCart title listings where we need to summarize item options
    */
    static public function Concat(array &$ar=NULL,$sKey,$sVal,$sSep) {
	if (!is_null($sVal)) {
	    if (self::Exists($ar,$sKey)) {
		$ar[$sKey] .= $sSep.$sVal;
	    } else {
		$ar[$sKey] = $sVal;
	    }
	}
    }
    /*----
      RETURNS: An array that is the result of ar1 + ar2,
	where ar2's values will override those in ar1.
      RULES: A NULL value will never overwrite a non-NULL. If we need NULLs
	to overwrite, we'll deal with that when there's an actual usage case.
      NOTE: *Somewhere* I had an array merge function already written
	that deals with the cases PHP's array_merge() can't handle...
      HISTORY:
	2015-08-27 Ran into a case where ar1 was NULL, so decided that should be handled as well.
	2016-05-01 NULLs in ar2 no longer overwrite existing values in ar1.
    */
    static public function Merge(array $ar1=NULL, array $ar2=NULL) {
	if (is_null($ar1)) {
	    $ar1 = $ar2;
	} elseif (!is_null($ar2)) {
	    foreach ($ar2 as $key => $val) {
		if (array_key_exists($key,$ar1) && is_null($val)) {
		    // for now, NULLs do not overwrite existing values
		} else {
		    $ar1[$key] = $val;
		}
	    }
	}
	return $ar1;
    }
    /*----
      ACTION: Merge an array of multiple arrays.
      INPUT:
	$ar: array of arrays -- each entry is an array to merge
	  Entries can also be NULL; those will be skipped.
    */
    static public function MergeMulti(array $ars) {
	$arOut = NULL;
	foreach ($ars as $ar) {
	    if (!is_null($ar)) {
		if (is_null($arOut)) {
		    $arOut = $ar;
		} else {
		    // this fails for numeric keys...
		    //$arOut = array_merge($arOut,$ar);
		    // ...so we have to do this:
		    foreach ($ar as $key => $val) {
			$arOut[$key] = $val;
		    }
		}
	    }
	}
	return $arOut;
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
    static public function Pivot(array $iArray) {
	foreach ($iArray as $row => $col) {
	    if (is_array($col)) {
		foreach ($col as $key => $val) {
		    $arOut[$key][$row] = $val;
		}
	    }
	}
	return $arOut;
    }
    // -- CALCULATIONS -- //
    // ++ RENDERING ++ //

    static public function RenderList($ar,$sSep=', ') {
	if (is_scalar($ar)) {
	    return $ar;
	} elseif (is_null($ar)) {
	    return NULL;
	} else {
	    $out = '';
	    foreach ($ar as $val) {
		if ($out != '') {
		    $out .= $sSep;
		}
		$out .= $val;
	    }
	    return $out;
	}
    }
    /*----
      INPUT:
	$ar = array to render
	$nDepth = maximum depth to render (0 = no maximum)
    */
    static public function Render(array $ar=NULL,$nDepth=0) {
	$out = self::GetRenderPrefix();
	if (is_null($ar)) {
	    $out .= 'NULL';
	} else {
	    //if ($nDepth == 0) {
		//$out .= print_r($ar,TRUE);
	    //} else {
		$out .= self::RenderLayer($ar,0,$nDepth);
	    //}
	}
	$out .= self::GetRenderSuffix();
	return $out;
    }
    static protected function RenderLayer($ar,$nDepthCur,$nDepthMax) {
	$nDepthCur++;
	$out = NULL;
	foreach ($ar as $key => $val) {
	    $out .= str_repeat(self::$sRenderIndent,$nDepthCur)
	      .'['.$key.']'
	      .self::$sRenderSeparator
	      .self::RenderElement($val)
	      .self::$sRenderNewLine
	      ;

	    if (($nDepthMax == 0) || ($nDepthCur <= $nDepthMax)) {
		if (is_array($val) || is_object($val)) {
		    $out .= self::RenderLayer($val,$nDepthCur,$nDepthMax);
		}
	    }
	}
	return $out;
    }
    /*----
      HISTORY:
	2015-11-24 Changing this from protected to public, because it's also useful in debugging.
	  Maybe later there should be a class for debugging variables, or debugging in general.
    */
    static public function RenderElement($val) {
	$out = '('.gettype($val).') : ';
	if (is_null($val)) {
	    // Null is null; nothing else to say here
	} elseif (is_scalar($val)) {
	    $out .= (string)$val;
	} elseif (is_object($val)) {
	    $out .= 'class '.get_class($val);
	} elseif (is_callable($val)) {
	    $out .= 'function';
	} elseif (is_array($val)) {
	    $cnt = count($val);
	    $out .= $cnt.' element'.fcString::Pluralize($cnt);
	} else {
	    $out .= 'type not handled yet';
	}
	return $out;
    }

    // -- RENDERING -- //
}
class clsArray extends fcArray {}	// deprecated; alias for now