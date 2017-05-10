<?php
include_once '../src/Tree.php';
use zlatov\tree\Tree;

$tests = [
	'flat_empty' => 'Преобразование пустого массива',
	'childrens' => 'Создан [] childrens',
];

foreach ($tests as $test => $header) {
	echo $test() . ' - ' . $header . PHP_EOL;
}

function flat_empty()
{
	$flat = [];
	$nested = Tree::to_nested($flat);
	if (is_array($nested)&&empty($nested)) {
		return true;
	}
	return false;
}

function childrens()
{
	$flat = [
		[
			'id' => 2,
			'pid' => 1,
			'header' => '2',
		],
		[
			'id' => 10,
			'pid' => 0,
			'header' => '10',
		],
	];
	$nested = Tree::to_nested($flat);
	if (isset($nested[10]['childrens'])) {
		return true;
	}
	return false;
}
