<?php
/*
  PURPOSE: node object classes
  HISTORY:
    2018-02-05 trying to extricate node logic from node admin stuff
*/
define('KS_CLASS_TF_NODE_BASE',		'fctNodesBase');	// abstract
define('KS_CLASS_TF_NODE_LOGIC',	'fctNodesLogic');	// concrete: logic
define('KS_CLASS_TF_NODE_INDEX',	'fctNodesIndex');	// summon appropriate node-class
define('KS_CLASS_TF_LEAF_INDEX',	'fctLeafsIndex');	// abstract ancestor for logic
define('KS_CLASS_TF_LEAF_VALUE',	'fctLeafValues');
define('KS_CLASS_TF_LEAF_VALUE_TEXT',	'fctLeafValuesText');

$fp = dirname( __FILE__ );
fcCodeModule::BasePath($fp.'/');

$om = new fcCodeModule(__FILE__, 'node.php');
  $om->AddClass(KS_CLASS_TF_NODE_BASE);
  $om->AddClass('ftNodeBaseTable');
$om = new fcCodeModule(__FILE__, 'node.logic.php');
  $om->AddClass(KS_CLASS_TF_NODE_LOGIC);
  $om->AddClass('ftrNodeLogic');
$om = new fcCodeModule(__FILE__, 'node.index.php');
  $om->AddClass(KS_CLASS_TF_NODE_INDEX);
$om = new fcCodeModule(__FILE__, 'leaf.index.php');
  $om->AddClass(KS_CLASS_TF_LEAF_INDEX);
  $om->AddClass('ftTableAccess_LeafIndex');

$om = new fcCodeModule(__FILE__, 'leaf.map.php');
  $om->AddClass('fcMapLeafTypes');
  $om->AddClass('fcMapLeafNames');
$om = new fcCodeModule(__FILE__, 'leaf.value.php');
  $om->AddClass(KS_CLASS_TF_LEAF_VALUE);
$om = new fcCodeModule(__FILE__, 'leaf.value.text.php');
  $om->AddClass(KS_CLASS_TF_LEAF_VALUE_TEXT);
  