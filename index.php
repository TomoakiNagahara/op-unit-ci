<?php
/** op-unit-ci:/index.php
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

/** Request check.
 *
 */
require_once(__DIR__.'/include/request_check.php');

/** Include
 *
 */
require_once(__DIR__.'/CI.class.php');
require_once(__DIR__.'/CI_Client.class.php');

//	Do a `git stash save` for all repositories.
\OP\UNIT\CI::GitStashSave();

/** Register shutdown function.
 *
 *  This shutdown function is called only when there is the Notice.
 *  If not have Notice, this file will not be called.
 *
 * @created    2024-09-07
 * @version    1.0
 * @package    core
 * @author     Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright  Tomoaki Nagahara All right reserved.
 */
register_shutdown_function(function(){
	//	Do a `git stash pop` for all repositories.
	\OP\UNIT\CI::GitStashPop();
});
