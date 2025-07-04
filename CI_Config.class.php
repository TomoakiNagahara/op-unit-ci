<?php
/**	op-unit-ci:/CI_Config.class.php
 *
 * @created    2024-02-23
 * @author     Tomoaki Nagahara
 * @copyright  Tomoaki Nagahara All right reserved.
 */

/**	Declare strict
 *
 */
declare(strict_types=1);

/**	namespace
 *
 */
namespace OP\UNIT\CI;

/**	use
 *
 */
use OP\OP_CI;
use OP\OP_CORE;
use OP\IF_CI_Config;

/**	CI_Config
 *
 * @created    2023-11-21
 * @version    1.0
 * @package    op-unit-ci
 * @author     Tomoaki Nagahara
 * @copyright  Tomoaki Nagahara All right reserved.
 */
class CI_Config implements IF_CI_Config
{
	/**	use
	 *
	 */
	use OP_CORE, OP_CI;

	/**	Config
	 *
	 * @created    2022-10-15
	 * @moved      2024-03-20  CI --> CI_Config
	 * @var        array
	 */
	private $_config = [];

	/**	Set Config.
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
	 * @param     'function'   $prepare
	 * @param     'function'   $cleanup
	 */
	function Set($method, $result, $args, $prepare=null, $cleanup=null)
	{
		//	...
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
		$file  = $trace['file'];
		$line  = $trace['line'];

		//	...
		foreach( ['prepare','cleanup'] as $name ){
			if( ${$name} ){
				if(!is_callable(${$name}) ){
					OP()->Error("{$name} is not function.");
				}
			}
		}

		//	...
		$this->_config[$method][] = [
			'result' => $result,
			'args'   => $args,
			'trace'  => [$file, $line],
			'prepare'=> $prepare, // Run the pre-function during CI.
			'cleanup'=> $cleanup, // Run the post-function during CI.
		];
	}

	/**	Generate Config.
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
	 * @return     array       $config
	 */
	function Get() : array
	{
		//	Swap config.
		$config = $this->_config;

		//	Reset config.
		$this->_config = [];

		//	Return config.
		return $config;
	}
}
