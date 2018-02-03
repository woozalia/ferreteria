<?php
/*
  PURPOSE: Semantic MediaWiki interface classes
    The existing class library is poorly documented, lacking a stable API, and difficult to use.
    This class set goes directly to the data structures -- which may change over time, but the changes
      should be easier to puzzle out than changes to the SMW class library.
  VERSION: SMW v2
  REQUIRES: Ferreteria db
  HISTORY:
    2012-01-22 started
    2012-09-17 useful pieces working
    2014-12-15 no longer invoking config-libs from library files
    2015-03-19 modified to use Ferreteria db v2
    2018-01-23 tweaks in a probably futile attempt to make the code still run in psycrit
    2018-01-24 renamed w3smwPage to fcPageData_SMW
      No longer requires a w3tpl Module in order to get the database.
    2018-01-26 apparently this is still using SMW's v2 schema, but it has been modified to work with current Ferreteria,
      so I am copying it over to the SMWv2 folder before rewriting for SMW schema v3.
*/
//if (!defined('SMW_NS_PROPERTY')) {
//    define('SMW_NS_PROPERTY',102);	// just for debugging without SMW actually installed; normally commented out
//}

/*::::
  PURPOSE: SMW-specific data functions
*/
class fcDataConn_SMW extends fcDataConn_MW {

    // ++ FRAMEWORK ++ //

    public function Normalize_PropertyName($sName) {
	return static::NormalizeTitle($sName,SMW_NS_PROPERTY);
    }
    
    // -- FRAMEWORK -- //


    /*----
      RETURNS: array of page names where the given property has the given value
	(or, if iPropValue is left out, just having the given property)
	Each array element contains the following in an array, keyed to "s_namespace:s_id":
	  * s_id
	  * s_namespace
	  * s_title
	NULL if property is not used anywhere (or possibly if it has never been used -- not sure if disused properties are kept)
      INPUT:
	$iPropName: name of a property to be searched
	$iPropValue: property value to be searched for
	$iPage (optional): page object to be loaded
      USED BY: psycrit.w3f_Show_Response_Header()
      HISTORY:
	2012-11-01 revised to look up SMW ID first, reducing CPU. Return array format has SMW ID as key.
	2012-12-17 look up property's type first, to find out which table to check for the value
	2018-01-27 This seems to have been updated for SMW dbv3, but I don't know if it works.
	2018-02-02 maybe working now?
    */
    public function GetTitleObjects_forPropertyValue($sPropName,$sPropValue) {
	$oProp = new fcPropertyData_SMW($sPropName);
	$id = $oProp->GetPropertyID();
	$rs = $oProp->GetTitleRecords_forID($id,$sPropValue);
	
	$ar = NULL;
	while ($rs->NextRow()) {
	    $ar[$rs->GetTitleID()] = $rs->GetTitleObject();
	}
	return $ar;
    }
}
