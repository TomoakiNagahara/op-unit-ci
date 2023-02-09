<?php
/** op-unit-ci:/CI.class.php
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
namespace OP\UNIT;

/** use
 *
 */
use Exception;
use OP\IF_UNIT;
use OP\OP_CORE;
use OP\OP_CI;

/** ci
 *
 * @created    2023-01-30
 * @version    1.0
 * @package    op-unit-ci
 * @author     Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright  Tomoaki Nagahara All right reserved.
 */
class CI implements IF_UNIT
{
	/** use
	 *
	 */
	use OP_CORE, OP_CI;

	/** Git
	 *
	 * @created    2023-02-05
	 * @return     Git
	 */
	static function Git()
	{
		static $_git;
		if(!$_git ){
			$_git = OP()->Unit('Git');
		}
		return $_git;
	}

	/** Automatically CI
	 *
	 */
	static function Auto()
	{
		if( self::Init() ){
			self::CI();
		}
	}

	/** Init
	 *
	 * @created    2023-02-05
	 */
	static function Init()
	{
		//	...
		if( file_exists('.ci_skip') ){
			self::SaveCommitID();
			return;
		}

		//	...
		if(!file_exists('.git') ){
			$current = getcwd();
			throw new Exception("Does not found .git directory.(current={$current})");
		}

		//	...
		if( self::CheckCommitID() ){
			return;
		}

		//	...
		return true;
	}

	/** CI
	 *
	 * @created    2023-02-05
	 */
	static function CI()
	{
		//	Get namespace
		$current = getcwd().'/';
		if( $current === OP()->MetaRoot('core') ){
			$namespace = 'OP\\';
		}else if( dirname($current).'/' === OP()->MetaRoot('unit') ){
			$namespace = 'OP\UNIT\\';
		}else{
			throw new Exception("Does not found namespace. ($current)");
		}

		//	You can specify and inspect only a specific class.
		if( $class_list = OP()->Request('class') ){
			$globed = [];
			foreach( explode(',', $class_list) as $class_name ){
				$globed[] = trim($class_name).'.class.php';
			}
		}else{
			//	Inspect all classes.
			$globed = glob('*.class.php');
		}

		//	Get each class file.
		foreach( $globed as $file ){
			//	Under bar file.
			if( $file[0] === '_' ){
				continue;
			}

			//	Instantiate Object from class.
			$class = $namespace.basename($file, '.class.php');
			$obj = new $class();

			//	Inspect each instantiate object.
			self::CI_Class($obj);
		}

		//	Do testcase.
	//	OP::Template('core:/include/ci_testcase.php', $config);

		//	Save Commit ID.
		self::SaveCommitID();
	}
}
