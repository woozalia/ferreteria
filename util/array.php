<?php
/*
  FILE: array.php -- class for handling array functions
*/

class clsArray {
    static private $sRenderPrefix = '<pre>';
    static private $sRenderSuffix = '</pre>';
    static private $sRenderIndent = '  ';
    static private $sRenderSeparator = " => \t";
    static private $sRenderNewLine = "\n";

    // ++ CALCULATIONS ++ //

    static public function Nz(array $ar=NULL,$key) {
	$out = NULL;
	if (is_array($ar)) {
	    if (array_key_exists($key,$ar)) {
		$out = $ar[$key];
	    }
	}
	return $out;
    }
    static public function NzSum(array &$ar=NULL,$key,$nVal) {
	if (is_array($ar)) {
	    $ar[$key] += $nVal;
	} else {
	    $ar[$key] = $nVal;
	}
	return $ar;
    }
    /*----
      RETURNS: An array that is the result of ar1 + ar2,
	where ar2's values will override those in ar1.
      NOTE: *Somewhere* I had an array merge function already written
	that deals with the cases PHP's array_merge() can't handle...
    */
    static public function Merge(array $ar1, array $ar2=NULL) {
	if (!is_null($ar2)) {
	    foreach ($ar2 as $key => $val) {
		$ar1[$key] = $val;
	    }
	}
	return $ar1;
    }

    // -- CALCULATIONS -- //
    // ++ RENDERING ++ //

    /*----
      INPUT:
	$ar = array to render
	$nDepth = maximum depth to render (0 = no maximum)
    */
    static public function Render(array $ar=NULL,$nDepth=0) {
	$out = self::$sRenderPrefix;
	if (is_null($ar)) {
	    $out .= 'NULL';
	} else {
	    if ($nDepth == 0) {
		$out .= print_r($ar,TRUE);
	    } else {
		$out .= self::RenderLayer($ar,0,$nDepth);
	    }
	}
	$out .= self::$sRenderSuffix;
	return "[$out]";
    }
    static protected function RenderLayer(array $ar,$nDepthCur,$nDepthMax) {
	$nDepthCur++;
	$out = NULL;
	foreach ($ar as $key => $val) {
	    $out .= str_repeat(self::$sRenderIndent,$nDepthCur)
	      .'['.$key.']'
	      .self::$sRenderSeparator
	      .self::RenderElement($val)
	      .self::$sRenderNewLine
	      ;

	    if ($nDepthCur <= $nDepthMax) {
		if (is_array($val) or is_object($val)) {
		    $out .= self::RenderLayer($ar,$nDepthCur,$nDepthMax);
		}
	    }
	}
	return $out;
    }
    static protected function RenderElement($val) {
	$out = '('.gettype($val).')';
	if (is_null($val)) {
	    $out .= '(NULL)';
	} elseif (is_scalar($val)) {
	    $out .= (string)$val;
	} elseif (is_object($val)) {
	    $out .= '(class '.get_class($val).')';
	} elseif (is_callable($val)) {
	    $out .= '(function)';
	} else {
	    $out .= '(type not handled yet)';
	}
	return $out;
    }

    // -- RENDERING -- //
}