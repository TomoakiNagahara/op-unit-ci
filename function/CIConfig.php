<?php
/** op-unit-ci:/function/CIConfig.php
 *
 * @created    2023-01-13
 * @moved      2023-02-10 op-core:/function/CI.php
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

/** Get CI Config for that class.
 *
 * @created    2022-10-12
 * @param      object
 * @throws    \Exception
 * @return     array
 */
function CIConfig(&$object) : array
{
	//	Get class path and parse it.
	$class_path  = get_class($object);
	$class_parse = explode('\\', $class_path);

	//	Check if the class path is a core class.
    if( count($class_parse) === 2 ){
		//	OP
			$io   = true;
			$meta = 'core';
			$name = $class_parse[1];
    }else{
		//	Get a type
		$type = $class_parse[1];
		if( $type === 'UNIT' or $type === 'MODULE' ){
			$io = true;
			$meta = strtolower($class_parse[1]);
			array_shift($class_parse); // Throw away OP
			array_shift($class_parse); // Throw away UNIT, MODULE, LAYOUT
			$unit = strtolower($class_parse[0]); // APP --> app
			//	...
			if( $type === 'UNIT' ){
				//	Remap unit name.
				if( $unit = OP()->Config('unit')['mapping'][$unit] ?? null ){
					$name = $class_parse[ count($class_parse)-1 ];
				}else{
					$name = join('-', $class_parse);
				}
			}else{
				$name = join('-', $class_parse);
			}
        }
	}

	//	False is not correct namespace.
	if(!$io ?? null ){
		throw new \Exception("Is correct namespace? ($class_path)");
	}

	/*
	//	...
	$path = ($unit ?? null) ? "{$meta}:/{$unit}/ci/{$name}.php" : "{$meta}:/ci/{$name}.php";

	//	...
	if(!file_exists( OP()->MetaPath($path) ) ){
		throw new \Exception("This file does not exist. ($path)");
	}
	*/

	//	Get asset root
	$asset_root = \OP\RootPath('asset');

	//	core or unit
	if( $meta === 'core' ){
		//	core
		$path = "{$asset_root}core/ci/{$name}.php";
	}else{
		//	unit or module
		$path = "{$asset_root}{$meta}/{$unit}/ci/{$name}.php";
		//	If file name is included namespace.
		if(!file_exists($path) ){
			//	Trim namespace from file name.
			if( $pos  = strpos($name, '-') ){
				$name = substr($name, $pos+1); // Unit-Name --> Name
				$path = "{$asset_root}{$meta}/{$unit}/ci/{$name}.php";
			}
		}
	}

	//	...
	if( file_exists($path) ){
		$path = OP()->MetaPath($path);
	}else{
		throw new \Exception("This file does not exist. ($path)");
	}

	//	...
	$config = OP()->Template($path);

	//	...
	if(!is_array($config) ){
		throw new \Exception("Return value is not array. ($path)");
	}

	//	...
	return $config;
}
