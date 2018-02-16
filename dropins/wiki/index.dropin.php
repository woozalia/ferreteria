<?php
/*
  PURPOSE: TextFerret - drop-in for wiki-like content management
  HISTORY:
    2017-04-01 started
    2017-08-22 renamed KS_CLASS_ADMIN_TF_NODE to KS_CLASS_ADMIN_TF_NODE_DISPATCH to avoid confusion which resulted in loop
    2017-08-26 renamed a bunch of class-name constants
*/

// NODE TYPES

define('KS_TF_NODE_TYPE_PAGE','wpage');

// ACTIONS

define('KS_ACTION_TF_NODE',	'node');
define('KS_ACTION_TF_NODE_PAGE','node_'.KS_TF_NODE_TYPE_PAGE);	// '.' seems to get transformed to '_' when POSTed from a form

// CLASS NAMES

define('KS_CLASS_TF_NODE_PAGE_ADMIN',	'fctNodes_SimpleWikiPage');

// MENU

$om = $oRoot->SetNode(new fcMenuFolder('TextFerret','TextFerret content management','wiki-like content management system'));

  $omi = $om->SetNode(new fcDropinLink(
    KS_ACTION_TF_NODE,
    KS_CLASS_TF_NODE_INDEX,
    'Nodes','root records for content'));
    
   // $omi->SetPageTitle('All Content Nodes');
    $omi->SetRequiredPrivilege(KS_PERM_RAW_DATA_EDIT);

  $omi = $om->SetNode(new fcDropinLink(
    KS_ACTION_TF_NODE_PAGE,
    KS_CLASS_TF_NODE_PAGE_ADMIN,
    'Page Nodes','page nodes'));
    
    //$omi->SetPageTitle('Page Nodes');
    $omi->SetRequiredPrivilege(KS_PERM_RAW_DATA_EDIT);

// MODULE SPEC ARRAY

$arDropin = array(
  'name'	=> 'ferreteria.cms',
  'descr'	=> 'content management',
  'version'	=> '0.0.1',
  'date'	=> '2018-02-05',
  'URL'		=> NULL,	// nothing yet
  'classes'	=> array(	// list of files and the classes they contain
    //'node.php'		=> array(KS_CLASS_TF_NODE_BASE,'ftNodeBaseTable'),
    //'node.index.php'		=> array(KS_CLASS_TF_NODE_INDEX),
    //'node.logic.php'		=> array(KS_CLASS_TF_NODE_LOGIC,'ftrNodeLogic'),
    'node.type.php'		=> array('fctNodeTypesBase'),
    'node.type.wiki.php' 	=> array(KS_CLASS_TF_NODE_PAGE_ADMIN),
    //'leaf.value.php'		=> array(KS_CLASS_TF_LEAF_VALUE),
    //'leaf.value.text.php'	=> array(KS_CLASS_TF_LEAF_VALUE_TEXT),
    //'leaf.map.php'		=> array('fcMapLeafTypes','fcMapLeafNames'),
     ),
  'menu'	=> $om,
  'features'	=> array(
    ),
  );
