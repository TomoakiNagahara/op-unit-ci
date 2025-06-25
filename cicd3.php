#!/usr/bin/env php
<?php
/**	op-unit-ci:/cicd
 *
 * @created    2025-06-24
 * @version    3.0
 * @package    op-unit-ci
 * @author     Tomoaki Nagahara
 * @copyright  Tomoaki Nagahara All right reserved.
 */

/**	namespace
 *
 */
namespace OP;

/**	Measure the execution time of this app.
 *
 */
define('_OP_APP_START_', microtime(true));

/**	CI flag
 *
 */
define('_IS_CI_', true);

//	...
try {
	//	Set app root.
	$_SERVER['APP_ROOT'] = realpath(__DIR__.'/../../../');

	//	Change to app directory.
	chdir($_SERVER['APP_ROOT']);

	//	Bootstrap process.
	if( file_exists( $file = './asset/bootstrap/index.php' ) ){
		//	Execute
		include_once($file);
	}else{
		//	Git submodules have not been initialized.
		include_once('./asset/init/guidance.php');
	}

	//	Time is frozen - ICE AGE
	OP::Time(false, '2024-01-01 23:45:60');

	//	...
	if(!OP::Unit('CI')->Auto() ){
		$exit = __LINE__;
		return;
	}

	//	...
	if( OP::Unit('CI')->Dryrun() ){
		return;
	}

	//	...
	OP::Unit('CD')->Auto();
	if( OP::Request('display') ){
		echo "\n";
	}

} catch ( \Throwable $e ){
	//	...
	$message = $e->getMessage();
	$file    = $e->getFile();
	$line    = $e->getLine();
	$file    = OP()->MetaPath($file);

	//	...
	echo "\n";
	echo "Exception: ".$message."\n\n";
	echo "{$file} #{$line}\n";
	DebugBacktrace::Auto($e->getTrace());
	echo "\n";

	//	...
	$exit = __LINE__;
}

//	exit
exit($exit ?? 0);
