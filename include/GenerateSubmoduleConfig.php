<?php
/** op-unit-ci:/include/GetSubmoduleConfig.php
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
$configs = [];

//	...
$git_root   = \OP\RootPath('git');
$asset_root = "{$git_root}asset";

//	...
$list = array_merge( ["{$asset_root}/core"], glob("{$asset_root}/unit/*"),  glob("{$asset_root}/layout/*"), glob("{$asset_root}/module/*"), glob("{$asset_root}/webpack/*") );

//	...
foreach( $list as $path ){
	//	...
	if( strpos($path, $git_root) !== 0 ){
		OP()->Notice("This path is not git root. ($path)");
		continue;
	}
	//	...
	$path = substr($path, strlen($git_root));
	/** Conflict to name.
	 * asset/unit/develop --> asset/module/develop
	$name = basename($path);
	$configs[$name] = [
	*/
	$configs[$path] = [
		'path' => $path,
	];
}

//	...
return $configs;
