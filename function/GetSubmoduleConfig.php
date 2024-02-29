<?php
/** op-unit-ci:/function/GetSubmoduleConfig.php
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

/** Get gitsubmodule config.
 *
 * @return array
 */
function GetSubmoduleConfig() : array
{
	//	...
	static $configs = [];

	//	...
	if( empty($configs) ){
		//	...
		$git_root = \OP\RootPath('git');

		//	If the unit name specified.
		if( $unit = OP()->Request('unit') ){
			$unit = strtolower($unit);

			//	...
			if( $unit === 'core' ){
				//	core
				$configs = [
					'core' => [
						'path' => "asset/core",
					],
				];
			}else{
				//	unit
				$configs = [
					$unit => [
						'path' => "asset/unit/{$unit}",
					],
				];
			}

		}else

		//	...
		if( file_exists("{$git_root}.gitmodules") ){
			$configs = OP()->Unit('Git')->SubmoduleConfig();
		}else{
			$configs = include(__DIR__.'/../include/GenerateSubmoduleConfig.php');
		}
	}

	//	...
	return $configs;
}
