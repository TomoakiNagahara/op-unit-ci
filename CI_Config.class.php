<?php
/** op-unit-ci:/CI_Config.class.php
 *
 * @created    2024-02-23
 * @version    1.0
 * @package    op-unit-ci
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

/** use
 *
 */
use OP\OP_CI;
use OP\OP_CORE;
use OP\IF_UNIT;

/** CI_Config
 *
 * @created    2023-11-21
 * @version    1.0
 * @package    op-unit-ci
 * @author     Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright  Tomoaki Nagahara All right reserved.
 */
class CI_Config implements IF_UNIT
{
	/** use
	 *
	 */
	use OP_CORE, OP_CI;

	/** Config
	 *
	 * @created    2022-10-15
	 * @moved      2024-03-20  CI --> CI_Config
	 * @var        array
	 */
	private $_config = [];

	/** Set Config.
	 *
	 * <pre>
	 * //  Get CI Config instance.
	 * $ci = OP()->Unit('CI')->Config();
	 *
	 * //  Set CI configuration.
	 * $ci->Set('MethodName', 'result', 'args');
	 *
	 * //  Return CI configuration.
	 * return $ci->Get();
	 * </pre>
	 *
	 * @created    2022-10-15
	 * @moved      2023-02-22  op-core:/CI.class.php --> op-unt-ci:/CI.class.php
	 * @moved      2024-03-20  CI --> CI_Config
	 * @param      string      $method
	 * @param      array       $args
	 * @param      array       $result
	 */
	function Set($method, $result, $args)
	{
		$this->_config[$method][] = [
			'result' => $result,
			'args'   => $args,
		];
	}
}
