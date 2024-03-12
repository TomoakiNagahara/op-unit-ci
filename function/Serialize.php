<?php
/** op-unit-ci:/function/Serialize.php
 *
 * @created    2024-02-18
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
function Serialize(array $args) : string
{
	//	...
	$results = [];

	//	...
	foreach( $args as $arg ){
		//	...
		switch( $type = strtolower(gettype($arg)) ){
			case 'boolean':
				$result = $arg ? 'true':'false';
				break;
			case 'string':
				$result = '"'.$arg.'"';
				break;
			case 'array':
				$result = json_encode($arg);
				break;
			case 'integer':
				$result = $arg;
				break;
			case '':
				break;
			case '':
				break;
			default:
				$result = $type;
			break;
		}
		//	...
		$results[] = $result;
	}

	//	...
	return join(',', $results);
}
