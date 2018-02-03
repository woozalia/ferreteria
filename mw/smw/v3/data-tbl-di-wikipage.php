<?php
/*
  STRUCTURE:
    fctqSMW_WikiPage is the query version that brings in s, p, and o tables
    fctSWM_WikiPage is the straight-up table version
*/
class fctqSMW_WikiPage extends fctqSMW_exact {

    // ++ SETUP ++ //
    
    // CEMENT
    protected function SingularName() {
	return 'fcrqSMW_WikiPage';
    }

    // -- SETUP -- //
    // ++ DATA READ ++ //
    
    // NOTE: Same as blob class; maybe should be consolidated
    public function SelectPropertyRecords_forTitle($sqlPageKey,$intNSpace) {
	$sqlFilt = "(s.smw_title=$sqlPageKey) AND (s.smw_namespace=$intNSpace)";
	
	$this->SetFieldsString_forSelect(<<<__END__
s_id,p_id,o_id
, s.smw_namespace AS s_namespace
, CAST(s.smw_title AS char) AS s_title
, p.smw_namespace AS p_namespace
, CAST(p.smw_title AS char) AS p_title
, o.smw_namespace AS o_namespace
, CAST(o.smw_title AS char) AS o_title
__END__
);
	
	$this->SetSourceString_forSelect(<<<__END__
((smw_di_wikipage AS r
LEFT JOIN smw_object_ids AS s ON r.s_id=s.smw_id)
LEFT JOIN smw_object_ids AS p ON r.p_id=p.smw_id)
LEFT JOIN smw_object_ids AS o ON r.o_id=o.smw_id
__END__
);

	$rs = $this->SelectRecords($sqlFilt);
	return $rs;
    }
    public function SelectTitleRecords_forPropertyID_andValue($id,$sValue) {
	$db = $this->GetConnection();
	$snVal = $db->Normalize_PropertyName($sValue);
	$sqlVal = $db->SanitizeValue($snVal);
	$sqlFilt = "p_id=$id AND o.smw_title=$sqlVal";	// TODO: if o_blob isn't NULL, compare with that instead

	$this->SetSourceString_forSelect('
(smw_di_wikipage AS r
    LEFT JOIN
smw_object_ids AS o ON r.o_id = o.smw_id)
    LEFT JOIN
smw_object_ids AS s ON r.s_id = s.smw_id
');
	
	$this->SetFieldsString_forSelect(<<<__END__
s_id,p_id,o_id
, s.smw_namespace AS s_namespace
, CAST(s.smw_title AS char) AS s_title
__END__
);

	$rs = $this->SelectRecords($sqlFilt);
	return $rs;
    }
    // NOTE: Same as blob class; maybe should be consolidated
    /* 2018-01-28 probably not a good way to do it; appears unused
    public function SelectRecords_forTitle_andPropName($sqlPageKey,$intNSpace,$sqlPropKey) {
	$sqlFilt = "(s.smw_title=$sqlPageKey)"
	  ." AND (s.smw_namespace=$intNSpace)"
	  ." AND (p.smw_title=$sqlPropKey)"
	  .' AND (p.smw_namespace='.SMW_NS_PROPERTY.')'
	  ;
	$rs = $this->SelectRecords($sqlFilt);
	return $rs;
    } */
    /* 2018-01-29 seems unlikely to be needed
    public function SelectTitleRecords_forProperty($sqlPropKey) {
	$sqlFilt = 
	  "(p.smw_title=$sqlPropKey) AND "
	  .'(p.smw_namespace='.SMW_NS_PROPERTY.')'
	  ;
	$rs = $this->SelectRecords($sqlFilt);
	return $rs;
    } */
}

class fcrqSMW_WikiPage extends fcrqSMW {
    public function GetPropertyName() {
	$sText = $this->GetFieldValue('p_title');
	return $sText;
    }
    public function GetPropertyValue() {
	$sValue = $this->GetFieldValue('o_title');
	// TODO: remove underscores?
	return $sValue;
    }
}