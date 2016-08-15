<?php
/*
  PURPOSE: db-engine-agnostic routines for calculating SQL
  STRUCTURE:
    A QUERY is a SELECT plus TERMS.
    A SELECT is 'SELECT' + FIELDS + SOURCE.
    A SOURCE is either a single table or a JOIN.
    A JOIN includes two or more TABLE SOURCEs.
  NAMING CONVENTIONS:
    SQL always means text in SQL format
    SQO is a Structured Query Object (fcSQL_Query or descendant) - an object that can generate SQL
  RULES:
    fcSQL_Select classes represent the SELECT [fields] FROM [Source] part of a query
      Each one has (or can generate) a Source object of class fcSQL_Source
    fcSQL_Source classes are equivalent to a table
    fcSQL_Query encapsulates all the pieces needed to generate a SELECT query
      Each one has (or can generate) a Select object of class fcSQL_Select
  HISTORY
    2015-12-26 needed because Item lookup in Needed Restocks was difficult, because the existing query
      does not include ID_Item.
      Subsequently found clsSQLFilt in data.php and moved it here.
    2016-02-11 major reshuffling of classes to allow outside modification of returned query objects
*/
define('KSQL_FORMAT_DATE','Y-m-d');	// date() format for dates in SQL
define('KSQL_FORMAT_DATIME','Y-m-d H:i:s');	// date format for timestamps in SQL

trait QueryableTable {
    public function SQO_Source($sAbbr=NULL) {
	return new fcSQL_TableSource($this->Name(),$sAbbr);
    }
    public function SQO_Select($sAbbr=NULL) {
	return new fcSQL_Select($this->SQO_Source($sAbbr));
    }
}

abstract class fcSQL_base_element {
    abstract public function Render();
    abstract public function Trace();
}

// ++++ QUERY ++++ //

class fcSQL_Query extends fcSQL_base_element {
    private $oSelect;	// SELECT query object
    private $oTerms;

    // ++ SETUP ++ //
    
    public function __construct(fcSQL_Select $oSelect, fcSQL_Terms $oTerms=NULL) {
	$this->Select($oSelect);
	$this->SetTerms($oTerms);
    }
    
    // -- SETUP -- //
    // ++ PIECES ++ //

    public function Select(fcSQL_Select $oSelect=NULL) {
	if (!is_null($oSelect)) {
	    $this->oSelect = $oSelect;
	}
	return $this->oSelect;
    }
    public function SetTerms(fcSQL_Terms $oTerms=NULL) {
	$this->oTerms = $oTerms;
    }
    public function Terms(fcSQL_Terms $oTerms=NULL) {
	if (!is_null($oTerms)) {
	    $this->oTerms = $oTerms;
	}
	return $this->oTerms;
    }
    protected function HasTerms() {
	return !is_null($this->Terms());
    }
    
    // -- PIECES -- //
    // ++ OUTPUT ++ //
    
    public function Render() {
	$src = $this->Select()->Render();
	$trm = $this->HasTerms()?
	  $this->Terms()->Render()
	  :
	  NULL
	  ;
	return fcString::ConcatArray("\n",array($src,$trm));
    }
    public function Trace() {	// for debugging
	$out = '<ul><li> <b>Renders to</b>: '
	  .$this->Render()
	  .'</li><li><b>Select</b>:<ul>'
	  .$this->Select()->Trace()
	  .'</ul></li><li><b>Terms</b>:<ul>'
	  .($this->HasTerms()?
	    $this->Terms()->Trace()
	    :
	    '<li><i>(no terms set)</i></li>')
	  .'</ul>'
	  .'</ul>'
	  ;
	return $out;
    }
    
    // -- OUTPUT -- //
}

// ---- QUERY ---- //
// ++++ SELECT ++++ //

class fcSQL_Select extends fcSQL_base_element {
    
    // ++ SETUP ++ //

    public function __construct(fcSQL_Source $oSource,fcSQL_Fields $oFields=NULL) {
	$this->Source($oSource);
	$this->oFields = $oFields;
    }
    
    // -- SETUP -- //
    // ++ PIECES ++ //
    
    private $oSource;
    public function Source($o=NULL) {
	if (!is_null($o)) {
	    $this->oSource = $o;
	}
	return $this->oSource;
    }
    private $oFields;
    public function Fields($o=NULL) {
	if (!is_null($o)) {
	    $this->oFields = $o;
	}
	if (is_null($this->oFields)) {
	    $this->oFields = new fcSQL_Fields();
	}
	return $this->oFields;
    }

    // -- PIECES -- //
    // ++ CALCULATIONS ++ //


    // -- CALCULATIONS -- //
    // ++ RENDERING ++ //

    public function Render() {
	$sql = 'SELECT '
	  .$this->Fields()->Render()
	  ."\n FROM "
	  ."\n".$this->Source()->Render()
	  ;
	return $sql;
    }
    public function Trace() {
	$out = '<li><b>Fields</b>:<ul>'
	  .$this->Fields()->Trace()
	  .'</ul><b>Source</b>:</ul>'
	  .$this->Source()->Trace()
	  ;
	return $out;
    }

    // -- RENDERING -- //

}
/*----
  HISTORY:
    2016-03-04 Changing the way fields are stored. It used to be ar[name] = alias,
      but that allowed duplicate columns and made it difficult to override fields
      selected earlier. It clearly should be ar[alias]=name, where 'alias' defaults
      to 'name'.
      
      OLD WAY:
	"array('a' => 'b')" will result in "a AS b"
	"array('a' => NULL)" will result in just "a"
      NEW WAY:
	  "array('a' => 'b')" will result in "b AS a"
	  "array('a' => NULL)" will result in just "a"
	  
	  or in other words
	  
	  array(alias => source) or array(source)
*/
class fcSQL_Fields extends fcSQL_base_element {

    public function __construct(array $arFields=NULL) {
	$this->Values($arFields);
    }
    
    // ++ CONFIG ++ //
    
    /*----
      INPUT:
	$ar: array of fields to SELECT --
	  "array(alias => source)" or "array(source)"
    */
    private $ar;
    protected function Values(array $ar=NULL) {
	if (!is_null($ar)) {
	    $this->ar = $ar;
	}
	return $this->ar;
    }
    protected function HasValues() {
	return (!is_null($this->Values()));
    }
    public function ClearField($sName) {
	unset($this->ar[$sName]);
    }
    public function ClearFields() {
	$this->ar = NULL;
    }
    public function SetField($sSource,$sAlias=NULL) {
	if (is_null($sAlias)) {
	    $sAlias = $sSource;
	}
	$this->ar[$sAlias] = $sSource;
    }
    public function SetFields(array $ar) {
	foreach ($ar as $sAlias => $sSource) {
	    if (is_numeric($sAlias)) {
		$this->SetField($sSource);
	    } else {
		$this->SetField($sSource,$sAlias);
	    }
	}
    }

    // -- CONFIG -- //
    // ++ RENDERING ++ //
    
    public function Render() {
	$sql = NULL;
	if ($this->HasValues()) {
	    $arFields = $this->Values();
	    foreach ($arFields as $alias => $source) {
		if (!is_null($sql)) {
		    $sql .= ', ';
		}
		$sql .= "\n    ".$source;
		if ($alias != $source) {
		    $sql .= ' AS '.$alias;
		}
	    }
	} else {
	    $sql = '*';
	}
	return $sql;
    }
    public function Trace() {
	$out = NULL;
	if ($this->HasValues()) {
	    $arFields = $this->Values();
	    foreach ($arFields as $key => $alias) {
		$out .= "<li> <b>Key</b>: [$key] <b>Alias</b>: [$alias]</li>";
	    }
	} else {
	    $out = '<li><i>(no values)</i></li>';
	}
	return $out;
    }
    
    // -- RENDERING -- //

}

// ---- SELECT ---- //
// ++++ SOURCES ++++ //

abstract class fcSQL_Source extends fcSQL_base_element {
    //abstract public function Render();
}
abstract class fcSQL_SourceSingle extends fcSQL_Source {
    abstract protected function RenderCode();
    abstract protected function TraceCode();

    // ++ SETUP ++ //

    private $sAlias;
    public function Alias($s=NULL) {
	if (!is_null($s)) {
	    $this->sAlias = $s;
	}
	return $this->sAlias;
    }
    
    // -- SETUP -- //
    // ++ CEMENTING ++ //
    
    public function Render() {
	return $this->RenderCode().$this->RenderAlias();
    }
    public function Trace() {
	$out = '<li><b>Code</b>:<ul>'.$this->TraceCode().'</ul></li><li><b>Alias</b>: '.$this->Alias().'</li>';
	return $out;
    }
    
    // -- CEMENTING -- //
    // ++ DETAILS ++ //
    
    protected function RenderAlias() {
	return $this->HasAlias()?(' AS '.$this->Alias()):NULL;
    }
    protected function HasAlias() {
	return !is_null($this->Alias());
    }

    // -- DETAILS -- //

}
class fcSQL_TableSource extends fcSQL_SourceSingle {

    // ++ SETUP ++ //

    public function __construct($sCode,$sAlias=NULL) {
	$this->Code($sCode);
	$this->Alias($sAlias);
    }
    private $sCode;
    protected function Code($s=NULL) {
	if (!is_null($s)) {
	    $this->sCode = $s;
	}
	return $this->sCode;
    }
    
    // -- SETUP -- //
    // ++ CEMENTING ++ //

    protected function RenderCode() {
	return '`'.$this->Code().'`';
    }
    protected function TraceCode() {
	return '<li><b>Table Code</b>: ['.$this->Code().']</li>';
    }
    
    // -- CEMENTING -- //

}
class fcSQL_JoinSource extends fcSQL_Source {
    
    // ++ SETUP ++ //
    
    public function __construct(array $arJoins = NULL) {
	$this->ar = $arJoins;
    }
    private $ar;
    public function JoinArray($ar=NULL) {
	if (!is_null($ar)) {
	    $this->ar = $ar;
	}
	return $this->ar;
    }
    public function AddElement(fcSQL_JoinElement $oJoin) {
	$this->ar[] = $oJoin;
    }
    public function AddElements(array $ar) {
	$this->ar = array_merge($this->ar,$ar);
    }
    
    // -- SETUP -- //
    // ++ CEMENTING ++ //
    
    public function Render() {
	$out = NULL;
    
	foreach ($this->ar as $jt) {
	    if (!is_a($jt,'fcSQL_JoinElement')) {
		throw new exception('Element in Join Source is a '.get_class($jt).', needs to be fcSQL_JoinElement.');
	    }
	    if (!is_null($out)) {
		$sVerb = $jt->Verb();
		$out .= " $sVerb ";
	    }
	    $out .= $jt->Render();
	}
	return $out;
    }
    public function Trace() {
	$out = '<li><b>Join</b>:<ul>';
	foreach ($this->ar as $jt) {
	    $out .= $jt->Trace();
	}
	return $out;
	
    }
    
    // -- CEMENTING -- //

}
// USED: For wrapping an entire query in (parenthesis) to use it as a subquery
class fcSQL_SubQuerySource extends fcSQL_SourceSingle {

    // ++ SETUP ++ //

    public function __construct(fcSQL_Query $o,$sAlias=NULL) {
	$this->Query($o);
	$this->Alias($sAlias);
    }
    private $oQuery;
    public function Query(fcSQL_Query $o=NULL) {
	if (!is_null($o)) {
	    $this->oQuery = $o;
	}
	return $this->oQuery;
    }
    protected function RenderCode() {
	return '('.$this->Query()->Render().')';
    }
    protected function TraceCode() {
	return '<li><b>Query Code</b>: <ul>'.$this->Query()->Trace().'</ul></li>';
    }
} //*/

// ---- SOURCES ---- //
// ++++ TERMS ++++ //

define('KS_SQL_TERM_TYPE_FILT','filt');
define('KS_SQL_TERM_TYPE_SORT','sort');
define('KS_SQL_TERM_TYPE_GROUP','group');

class fcSQL_Terms extends fcSQL_base_element {
    private $ar;
    
    // ++ SETUP ++ //
    
    public function __construct(array $arTerms=NULL) {
	$this->UseTerms($arTerms);
    }
    public function UseTerm(fcSQL_Term $o=NULL) {
	$sName = $o->TypeString();
	$this->ar[$sName] = $o;
    }
    public function UseTerms(array $arTerms=NULL) {
	if (is_null($arTerms)) {
	    $this->ar = NULL;
	} else {
	    foreach ($arTerms as $o) {
		$this->UseTerm($o);
	    }
	}
    }
    
    // -- SETUP -- //
    
    public function Render() {
	if (is_null($this->ar)) {
	    $out = NULL;
	} else {
	    foreach ($this->ar as $oTerm) {
		if (is_object($oTerm)) {
		    $arOut[] = $oTerm->Render();
		} else {
		    throw new exception('Term in array is '.gettype($oTerm).', needs to be fcSQL_Term object.');
		}
	    }
	    $out = fcString::ConcatArray("\n",$arOut);
	}
	return $out;
    }
    public function Trace() {
	if (is_null($this->ar)) {
	    $out = '<li><i>(no terms set)</i></li>';
	} else {
	    $out = NULL;
	    foreach ($this->ar as $oTerm) {
		$out .= '<li><b>Term</b>:<ul>'.$oTerm->Trace().'</ul></li>';
	    }
	}
	return $out;
    }
    
    // ++ TYPES ++ //
    
    protected function GetTerm($sType) {
	if (array_key_exists($sType,$this->ar)) {
	    return $this->ar[$sType];
	} else {
	    return NULL;
	}
    }
    protected function RemoveTerm($sType) {
	unset($this->ar[$sType]);
    }
    public function Filters() {
	$ot = $this->GetTerm(KS_SQL_TERM_TYPE_FILT);
	if (is_null($ot)) {
	    $ot = new fcSQLt_Filt();
	}
	return $ot;
    }
    // TODO: similar behavior to Filters()
    public function Sorting() {
	return $this->GetTerm(KS_SQL_TERM_TYPE_SORT);
    }
    // TODO: similar behavior to Filters()
    public function Grouping() {
	return $this->GetTerm(KS_SQL_TERM_TYPE_GROUP);
    }
    
    // -- TYPES -- //
}
abstract class fcSQL_Term extends fcSQL_base_element {
    abstract public function TypeString();	// unique identifier for this type of term
    abstract protected function RenderName();
    abstract protected function RenderValue();
    abstract protected function HasValue();
    public function Render() {
	if ($this->HasValue()) {
	    $out = fcString::ConcatArray(' ',array($this->RenderName(),$this->RenderValue()));
	} else {
	    $out = NULL;
	}
	return $out;
    }
    public function Trace() {
	if ($this->HasValue()) {
	    $out = '<li> <b>Name</b>: ['.$this->RenderName().'] <b>Value</b>: ['.$this->RenderValue().']</li>';
	} else {
	    $out = '<li> <i>(no value set)</i></li>';
	}
	return $out;
    }
}
/*----
  RULES:
    $arTerms is a simple array of terms which can either be strings or Renderable objects.
*/
class fcSQLt_Filt extends fcSQL_Term {
    private $sConj;
    private $arTerms;

    // ++ SETUP ++ //
    
    public function __construct($sConj=NULL,array $arTerms=NULL) {
	$this->sConj = $sConj;
	$this->arTerms = $arTerms;
	if (!is_string($sConj) && !is_null($sConj)) {
	    throw new InvalidArgumentException('Internal error: expecting a string or NULL for $sConj; got something else.');
	}

    }
    public function TypeString() {
	return KS_SQL_TERM_TYPE_FILT;
    }
    
    // -- SETUP -- //
    // ++ INPUT ++ //
    
    // NOTE: Although NULL conditions will be added to the array, they will be skipped at rendering time.
    public function AddCond($sCond) {
	$this->arTerms[] = $sCond;
    }
    
    // -- INPUT -- //
    // ++ OUTPUT ++ //
   
    protected function HasValue() {
	return !is_null($this->arTerms);
    }
    protected function RenderName() {
	return 'WHERE';
    }
    // PUBLIC so we can get the rendered filter without the WHERE
    public function RenderValue() {
	if (!is_array($this->arTerms)) {
	    throw new InvalidArgumentException('Object needs some terms to render.');
	}
	$out = self::Array_toFilter($this->arTerms,$this->sConj);
	return $out;
    }
    
    // -- OUTPUT -- //
    // ++ CALCULATIONS ++ //

    /*----
      ACTION: convert an array to SQL for filtering
      INPUT:
	arFilt[key]->cond = array of filter terms
	  key is ignored
	  cond is either a string or another fcSQLt_Filt object
	sOper = operator to use (AND or OR)
      TODO: Rename this to ConditionArray_toFilter()
    */
    static public function Array_toFilter(array $arFilt,$sOper='AND') {
	$out = NULL;
	foreach ($arFilt as $key => $cond) {
	    if (!is_null($cond)) {
		if (is_string($cond)) {
		    if (!is_null($out)) {
			$out .= " $sOper ";
		    }
		    $out .= "($cond)";
		} else {
		    // if $cond is not a string, it should be a Term object
		    if (!is_object($cond)) {
			throw new exception('filter term condition is a '.gettype($cond).'; needs to be either a string or a Term object.');
		    }
		    $out .= '('.$cond->Render().')';
		}
	    }
	}
	return $out;
    }
    // ASSUMES: Field values are SQL-sanitized
    static public function ValueArray_to_ConditionArray(array $arData,$sOper='=') {
	$arCond = NULL;
	foreach ($arData as $sqlName => $sqlVal) {
	    $arCond[] = "$sqlName$sOper$sqlVal";
	}
	return $arCond;
    }

    // -- CALCULATIONS -- //
}
class fcSQLt_Sort extends fcSQL_Term {
    private $arTerms;

    public function __construct(array $arTerms) {	// array of strings
	$this->SetValues($arTerms);
    }
    protected function HasValue() {
	return !is_null($this->arTerms);
    }
    public function SetValue($sTerm) {
	$this->arTerms[] = $sTerm;
    }
    public function SetValues(array $ar) {
	$this->arTerms = $ar;
    }
    public function TypeString() {
	return KS_SQL_TERM_TYPE_SORT;
    }
    protected function RenderName() {
	return 'ORDER BY';
    }
    protected function RenderValue() {
	return fcString::ConcatArray(',',$this->arTerms);
    }
}

class fcSQLt_Group extends fcSQL_Term {
    private $arTerms;
    
    public function __construct(array $arTerms) {
	$this->arTerms = $arTerms;
    }
    public function TypeString() {
	return KS_SQL_TERM_TYPE_GROUP;
    }
    protected function HasValue() {
	return !is_null($this->arTerms);
    }
    protected function RenderName() {
	return 'GROUP BY';
    }
    protected function RenderValue() {
	return fcString::ConcatArray(',',$this->arTerms);
    }
}

// ++++ JOIN ELEMENT ++++ //

// NOTE: sCond and Verb are ignored for the first element
class fcSQL_JoinElement extends fcSQL_base_element {
    private $qoSource;
    private $sCond;

    public function __construct(fcSQL_Source $qoSource,$sCond=NULL,$sVerb='JOIN') {
	$this->qoSource = $qoSource;
	$this->sCond = $sCond;
	$this->Verb($sVerb);
    }

    // NOTE: This is ignored for the first term.
    private $sVerb;
    public function Verb($sVerb=NULL) {
	if (!is_null($sVerb)) {
	    $this->sVerb = $sVerb;
	}
	return $this->sVerb;
    }
    
    // ++ CEMENTING ++ //
    
    public function Render() {
	$out = $this->qoSource->Render();
	if (!is_null($this->sCond)) {
	    $out .= ' ON '.$this->sCond;
	}
	return $out;
    }
    public function Trace() {
	$out = '<li><b>Source</b>:<ul>'.$this->qoSource->Trace().'</ul></li>'
	  .'<li><b>Condition</b>: ['.$this->sCond.']</li>'
	  .'<li><b>Verb</b>: ['.$this->Verb().']</li>'
	  ;
	return $out;
    }
    
    // -- CEMENTING -- //
}

// ---- JOIN ELEMENT ---- //
