<?php
/** op-unit-ci:/function/Display.php
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

/** Display
 *
 * @return array
 */
function Display(string $message)
{
	//	...
	static $_display;

	//	...
	if( empty($_display) ){
		$_display = OP()->Request('display');
	}

	//	...
	if( $_display ){
		echo "{$message}\n";
	}
}
