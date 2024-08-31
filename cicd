#!/usr/bin/env php
<?php
/** op-core:/cicd
 *
 * @created    2023-02-11
 * @version    2.2.0
 * @package    op-core
 * @author     Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright  Tomoaki Nagahara All right reserved.
 */

/** Declare strict
 *
 */
declare(strict_types=1);

/** namespace
 *
 */
namespace OP;

//  Inherit the PHP version of the execution source.
$php = $_SERVER['_'];
if( $php === './cicd' ){
    $php = 'php';
}

//	Get git root.
if(!$git_root = trim(`git rev-parse --show-superproject-working-tree` ?? '') ){
	echo 'Get git root is failed #'.__LINE__;
	exit(__LINE__);
}

//	...
$argv1 = $_SERVER['argv'][1] ?? '';
$argv2 = $_SERVER['argv'][2] ?? '';
$argv3 = $_SERVER['argv'][3] ?? '';
$argv4 = $_SERVER['argv'][4] ?? '';
$argv5 = $_SERVER['argv'][5] ?? '';

//	...
foreach( ['ci','cd'] as $cicd ){
	$output = null;
	$status = null;
	$comand = "{$php} {$git_root}/{$cicd}.php {$argv1} {$argv2} {$argv3} {$argv4} {$argv5}";
	exec($comand, $output, $status);
	//	...
	if( $output ){
		echo join("\n", $output)."\n";
	}
	//	...
	if( $status ){
		exit($status);
	}
}
