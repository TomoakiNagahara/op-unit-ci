#!/usr/bin/env php
<?php
/**	op-unit-ci:/cicd3.php
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

//	Start CI/CD process
try {
	//	Exit code
	$exit = 0;

	//	Calc app root from current working directory.
	$pwd = $_SERVER['PWD'];
	do{
		//	Search for app root by detecting app.php
		if( file_exists("{$pwd}/app.php") ){
			//	Found
			break;
		}
		//	Move to parent directory.
		$pwd = dirname($pwd);
		//	Not found.
	}while( $pwd !== '/' );

	//	...
	if( $pwd === '/' ){
		exit(__LINE__);
	}

	//	Set app root path.
	$_SERVER['APP_ROOT'] = $pwd . '/';

	//	Change to app directory.
	chdir($_SERVER['APP_ROOT']);

	//	Bootstrap application.
	if( file_exists( $file = './asset/bootstrap/index.php' ) ){
		//	Execute bootstrap
		include_once($file);
	}else{
		//	Display guidance if git submodules have not been initialized.
		include_once('./asset/init/guidance.php');
	}

	//	Time is frozen - ICE AGE
	OP::Time(false, '2024-01-01 23:45:60');

	//	Run CI Auto process.
	if(!OP::Unit()->CI()->Auto() ){
		$exit = __LINE__;
	}

	//	CI is clear.
	if( empty($exit) ){
	//	Run CD Auto process if not dry-run.
	if(!OP::Unit()->CI()->Dryrun() ){
		//	Run CD only if `cd` parameter is true or not set.
		if( OP::Request('cd') ?? true ){
			OP::Unit()->CD()->Auto();
		}
	}
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

//	...
if( OP::Request('display') ){
	echo "\n";
	echo "exit={$exit}\n\n";
}

//	exit
exit($exit);
