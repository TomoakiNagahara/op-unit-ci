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
	//	...
	$class_path  = get_class($object);
	$class_parse = explode('\\', $class_path);

	//	...
    if( count($class_parse) === 2 ){
		//	OP
			$io   = true;
			$meta = 'core';
			$name = $class_parse[1];
    }else{
        //  UNIT
        if( $io = $class_parse[1] === 'UNIT' ? true: false ){
            array_shift($class_parse);
            array_shift($class_parse);
            $meta = 'unit';
            $unit = strtolower($class_parse[0]);
            $name = join('-', $class_parse);
        }
	}

	//	...
	if(!$io ){
		throw new \Exception("Is correct namespace? ($class_path)");
	}

	//	...
	$path = ($unit ?? null) ? "{$meta}:/{$unit}/ci/{$name}.php" : "{$meta}:/ci/{$name}.php";

	//	...
	return OP()->Template($path);
}
