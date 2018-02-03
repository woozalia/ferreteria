<?php
class fctqSMW_Time extends fctqSMW {

    // ++ SETUP ++ //
    
    // CEMENT
    protected function SingularName() {
	return 'fcrqSMW_Time';
    }
    // OVERRIDE
    protected function FieldsString_forSelect() {
	return <<<__END__
s_id,
p_id,
CAST(s.smw_title AS CHAR) AS s_title,
CAST(p.smw_title AS CHAR) AS p_title,
CAST(o_serialized AS CHAR) AS value
__END__;
    }
    // OVERRIDE
    protected function SourceString_forSelect() {
	return <<<__END__
(smw_di_time AS t
LEFT JOIN smw_object_ids AS s ON t.s_id = s.smw_id)
LEFT JOIN smw_object_ids AS p ON t.p_id = p.smw_id
__END__;
    }

    // -- SETUP -- //
    // ++ DATA READ ++ //
    
    public function SelectPropertyRecords_forTitle($sqlPageKey,$intNSpace) {
	$sqlFilt = "(s.smw_title=$sqlPageKey) AND (s.smw_namespace=$intNSpace)";
	$rs = $this->SelectRecords($sqlFilt);
	return $rs;
    }
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

    // -- DATA READ -- //
}
class fcrqSMW_Time extends fcrqSMW {
    public function GetPropertyName() {
	$sText = $this->GetFieldValue('p_title');
	return $sText;
    }
    // TODO: there probably should be a flexible Time class which gracefully handles missing fields
    public function GetPropertyValue() {
	$sEncoded = $this->GetFieldValue('value');
	$arTime = preg_split('/\//',$sEncoded);
	@list($unk,$yr,$mo,$dy,$hr,$min,$sec) = $arTime;
	$sValue = sprintf('%04d/%02d',$yr,$mo);
	if (!is_null($dy)) {
	    $sValue .= sprintf('/%02d',$dy);
	    if (!is_null($hr)) {
		$sValue .= sprintf(' %02d:%02d',$hr,$min);
		if (!is_null($sec)) {
		    $sValue .= sprintf(':%02d',$sec);
		}
	    }
	}
	return $sValue;
    }
}