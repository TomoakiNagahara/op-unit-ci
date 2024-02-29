<?php
/** op-unit-ci:/include/request_check.php
 *
 * @created    2024-02-29
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
$request = OP()->Request();
$unit    = $request['unit']   ?? null;
$class   = $request['class']  ?? null;
$method  = $request['method'] ?? null;

//	...
if( $method ){
	if( empty($class) ){
		throw new \Exception("A class name was not specified.");
	}
}

//	...
if( $class ){
	if( empty($unit) ){
		throw new \Exception("A unit name was not specified.");
	}
}
