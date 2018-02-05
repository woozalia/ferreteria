<?php
/*
  STRUCTURE:
    fctqSMW_Blob is the query version that brings in s and p tables
    fctSWM_Blob is the straight-up table version
*/
class fctqSMW_Blob extends fctqSMW_exact {

    // ++ SETUP ++ //
    
    // CEMENT
    protected function SingularName() {
	return 'fcrqSMW_Blob';
    }
    // OVERRIDE
    /*
    protected function FieldsString_forSelect() {
	return <<<__END__
s_id, p_id
, s.smw_namespace AS s_namespace
, CAST(s.smw_title AS char) AS s_title
, p.smw_namespace AS p_namespace
, CAST(p.smw_title AS char) AS p_title
, CAST(o_blob AS CHAR) as text
, CAST(o_hash AS CHAR) as hash
__END__;
    }*/

    // -- SETUP -- //
    // ++ DATA READ ++ //
    
    public function SelectPropertyRecords_forTitle($sqlPageKey,$intNSpace) {
	$sqlFilt = "(s.smw_title=$sqlPageKey) AND (s.smw_namespace=$intNSpace)";
	
	$this->SetSourceString_forSelect('
(smw_di_blob AS r
  LEFT JOIN smw_object_ids AS s ON r.s_id=s.smw_id)
  LEFT JOIN smw_object_ids AS p ON r.p_id=p.smw_id
');

	$this->SetFieldsString_forSelect('
s_id, p_id
, s.smw_namespace AS s_namespace
, CAST(s.smw_title AS char) AS s_title
, p.smw_namespace AS p_namespace
, CAST(p.smw_title AS char) AS p_title
, CAST(o_blob AS CHAR) as value
, CAST(o_hash AS CHAR) as hash
');

	$rs = $this->SelectRecords($sqlFilt);
	return $rs;
    }
    public function SelectTitleRecords_forPropertyID_andValue($id,$sValue) {
	//$snVal = fcDataConn_MW::NormalizeTitle($sValue,SMW_NS_PROPERTY);
	$sqlVal = $this->GetConnection()->SanitizeValue($sValue);
	
	// TODO: also MW-normalize it, here and in di-wikipage
	$sqlFilt = "p_id=$id AND o_hash=$sqlVal";	// TODO: if o_blob isn't NULL, compare with that instead
	
	$this->SetFieldsString_forSelect('
s_id,
s.smw_namespace AS s_namespace,
CAST(s.smw_title AS CHAR) AS s_title
');

	$this->SetSourceString_forSelect('
smw_di_blob AS r
LEFT JOIN
smw_object_ids AS s
ON r.s_id = s.smw_id
');

	$rs = $this->SelectRecords($sqlFilt);
	return $rs;
    }

    // -- DATA READ -- //
}
class fcrqSMW_Blob extends fcrqSMW {
    public function GetPropertyName() {
	$sText = $this->GetFieldValue('p_title');
	return $sText;
    }
    public function GetPropertyValue() {
	$sText = $this->GetFieldValue('value');
	if (is_null($sText)) {
	    $sValue = $this->GetFieldValue('hash');
	} else {
	    $sValue = $sText;
	}
	return $sValue;
    }
}