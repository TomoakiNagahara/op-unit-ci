<?php
/** op-unit-ci:/ci/CI.php
 *
 * @created    2023-01-30
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
namespace OP;

//	...
$ci = new CI();

//	Test
$result =  null;
$args   = ['a','b'];
$ci->Set('Test', $result, $args);

//	...
return $ci->GenerateConfig();
