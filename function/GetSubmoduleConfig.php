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

/** Get git submodule config.
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
		$git_root = OP()->Path('git:/');

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
		}else if( $module = OP()->Request('module') ){
			$module = strtolower($module);
			$configs = [
				$module => [
					'path' => "asset/module/{$module}",
				],
			];
		}else

		//	...
		if( file_exists("{$git_root}.gitmodules") ){
			//	...
			$configs = OP()->Unit('Git')->SubmoduleConfig();

			/*
			//	...
			foreach(['unit','module','layout','webpack'] as $type ){
				//	...
				$root = _ROOT_GIT_;
				$slen = strlen($root)+1;
				foreach( glob("{$root}/asset/{$type}/*", GLOB_ONLYDIR) as $path ){
					$path = substr($path, $slen);
					$name = explode('/', $path)[2];
					$configs["asset-{$type}-{$name}"] = [
						'path' => $path,
					];
				}
			}
			*/

			//	Non git managed submodules.
			foreach( glob(_ROOT_ASSET_.'config/submodule/*/*.php') as $glob ){
				$temp = explode('/', $glob);
				$name = array_pop($temp);
				$type = array_pop($temp);
				$name = basename($name, '.php');
				$config = (function($glob){ return include($glob); })($glob);
				if( $config['skip'] ?? null ){
					continue;
				}
				switch( $type ){
					case 'public_html':
						$path = $config['path'] ?? "public_html";
						break;
					case 'asset':
						$path = "{$type}/{$config['path']}" ?? "{$type}/{$name}";
						break;
					default:
						//	If a path was specified.
						if( $config['path'] ?? null ){
							$path =  "asset/{$type}/{$config['path']}";
						}else{
							$path =  "asset/{$type}/{$name}";
						}
					break;
				}
				$configs["asset-{$type}-{$name}"] = [
					'path' => $path,
				];
			}

		}else{
			$configs = include(__DIR__.'/../include/GenerateSubmoduleConfig.php');
		}
	}

	//	...
	return $configs;
}
