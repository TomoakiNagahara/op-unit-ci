<?php
/** op-unit-ci:/include/GetStashPop.php
 *
 * @created    2024-02-17
 * @package    op-unit-ci
 * @version    1.0
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
namespace OP\UNIT\CI;

//	...
$current_dir = getcwd();

//	...
$git_root = \OP\RootPath('git');

//	...
require_once(__DIR__.'/../function/Display.php');
require_once(__DIR__.'/../function/GetSubmoduleConfig.php');
$configs = GetSubmoduleConfig();

//	...
foreach( $configs as $config ){
	$path = $config['path'];
	chdir($git_root . $path);
	if( self::Git()->Stash()->Pop() ){
		Display("git stash pop : {$path}");
	}
}

//	...
chdir($current_dir);
