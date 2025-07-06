<?php
/** op-unit-ci:/include/GetStashSave.php
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
require_once(__DIR__.'/../function/Display.php');
require_once(__DIR__.'/../function/GetSubmoduleConfig.php');

//	...
$current_dir = getcwd();

//	...
$git_root = \OP\RootPath('git');

//	...
chdir($git_root);
if( self::Git()->Stash()->Save() ){
	//	...
	Display("git stash save : {$git_root}");
}

//	...
$configs = GetSubmoduleConfig();

//	...
foreach( $configs as $config ){
	//	...
	GIT_STASH\Save("{$git_root}/{$config['path']}");
}

//	op-core
chdir("{$git_root}/asset/core/");
$configs = \OP\UNIT\GIT\SubmoduleConfig();
foreach( $configs as $config ){
	GIT_STASH\Save("{$git_root}/asset/core/{$config['path']}");
}

//	...
chdir($current_dir);

/**	namespace
 *
 */
namespace OP\UNIT\CI\GIT_STASH;

/**	Do git stash save.
 *
 * @created    2025-07-06
 * @param      string     $path
 */
function Save( string $path )
{
	//	...
	chdir($path);

	//	...
	if( OP()->Unit()->Git()->Stash()->Save() ){
		//	...
		\OP\UNIT\CI\Display("git stash save : {$path}");
	}
}
