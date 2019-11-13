<?php

if (!defined('ZEND_SEARCH_LUCENE_BASE_PATH')) 
	define('ZEND_SEARCH_LUCENE_BASE_PATH', realpath(dirname(__FILE__)));

set_include_path(
    get_include_path() . PATH_SEPARATOR . dirname(__FILE__).'/thirdparty'
);

if (!class_exists('SS_Object')) class_alias('Object', 'SS_Object');