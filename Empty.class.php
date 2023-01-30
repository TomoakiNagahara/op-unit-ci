<?php
/** op-unit-empty:/Empty.class.php
 *
 * @created    2023-01-30
 * @version    1.0
 * @package    op-unit-empty
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
namespace OP\UNIT;

/** use
 *
 */
use OP\IF_UNIT;
use OP\OP_CORE;
use OP\OP_CI;

/** Empty
 *
 * @created    2023-01-30
 * @version    1.0
 * @package    op-unit-empty
 * @author     Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright  Tomoaki Nagahara All right reserved.
 */
class _Empty implements IF_UNIT
{
	use OP_CORE, OP_CI;
}
