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

/* @var $ci \OP\UNIT\CI\CI_Config */
$ci = OP::Unit('CI')::Config();

//	Template
$method = 'Template';
$args   = ['ci.txt'];
$result = 'OK';
$ci->Set($method, $result, $args);

//	...
return $ci->Get();
