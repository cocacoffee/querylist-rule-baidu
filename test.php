<?php

require 'vendor/autoload.php';

use QL\QueryList;
use QL\Ext\Baidu;

$queryList = QueryList::getInstance();
$queryList->use(Baidu::class);
$searcher = $queryList->baidu()->search('翟天临');
$results = $searcher->getSuggestions();

print_r($results);