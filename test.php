<?php

require 'vendor/autoload.php';

use QL\QueryList;
use QL\Ext\Baidu;

$queryList = QueryList::getInstance();
$queryList->use(Baidu::class);
$searcher = $queryList->baidu()->search('翟天临');
$data = $searcher->getRelatedSearches();

print_r($data);