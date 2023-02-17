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
			self::Display('Found .ci_skip file.');
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
			$namespace = 'OP\MODULE\\';
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

	/** CI each Classes.
	 *
	 * @created    2023-02-10
	 * @param      object      $obj
	 */
	static function CI_Class(object $obj)
	{
		//	...
		if(!isset(class_uses($obj, false)['OP\OP_CI']) ){
			$class_name = get_class($obj);
			throw new Exception("This object has not use OP_CI. ({$class_name})");
		}

		//	You can specify and inspect onnly a specific method.
		if( $method_list = OP()->Request('method') ){
			$methods = [];
			foreach( explode(',', $method_list) as $method_name ){
				$methods[] = trim($method_name);
			}
		}else{
			$methods = $obj->CI_AllMethods();
		}

		//	Get CI Configs for that instance.
		require_once(__DIR__.'/function/CIConfig.php');
		$configs = CI\CIConfig($obj);

		//	...
		foreach( $methods as $method ){
			//	Skip method
			switch( $method ){
				//	Magic method
				case (strpos($method, '__') === 0 ) ? true: false;
				case 'CI':
				case 'CI_AllMethods':
				case 'CI_Inspection':
					continue 2;
			}

			//	Inspect each method.
			self::CI_Method($obj, $method, $configs[$method] ?? [[]]);
		}
	}

	/** CI Class each Methods.
	 *
	 * @created    2023-02-10
	 * @param      object      $obj
	 * @param      string      $method
	 * @param      array       $configs
	 * @throws \Exception
	 */
	static function CI_Method(object $obj, string $method, array $configs)
	{
		//	Inspect each args
		foreach( $configs as $config ){
			//	...
			$expect = $config['result'] ?? null;
			$arg    = $config['args']   ?? null;
			$args   = is_array($arg) ? $arg: [$arg];
			$result = null;
			$traces = null;

			//	Inspect each args.
			self::CI_Args($obj, $method, $args, $result, $traces);

			//	If result is object.
			if( is_object($result) ){
				$result = get_class($result);
			}

			//	...
			if( $result !== $expect ){
				//	...
				echo "\n";
				echo 'Config: ';
				\OP\UNIT\Dump::MarkPlain($config, []);
				echo "\n";
				echo 'Expect: ';
				\OP\UNIT\Dump::MarkPlain($expect, []);
				echo "\n";
				echo 'Result: ';
				\OP\UNIT\Dump::MarkPlain($result, []);

				//	...
				if( $traces ){
					$i = count($traces);
					echo "\n{$result}\n\n";
					foreach( $traces as $trace){
						$i--;
						$n = str_pad((string)$i, 2, ' ', STR_PAD_LEFT);
						echo "$n: ".OP()->DebugBacktraceToString($trace)."\n";
					}
				}

				//	...
				$class = get_class($obj);
				throw new \Exception("{$class}->{$method}(): Unmatch result.");
			}
		}
	}

	/** CI Class Method each Arguments.
	 *
	 * @created    2023-02-10
	 * @param      object      $obj
	 * @param      string      $method
	 * @param      array       $args
	 * @param      array       $result
	 * @param      array       $traces
	 */
	static function CI_Args(object $obj, string $method, array $args, /* php82 null */ &$result, /* php82 null */ &$traces)
	{
		//	...
		$traces = null;

		//	...
		try {
			//	Inspection.
			ob_start();
			if(!$result = $obj->CI_Inspection($method, ...$args) ){
				//	If empty return value, evaluate contents.
				if( $contents = ob_get_contents() ){
					$result   = $contents;
				}
			}
			ob_end_clean();

			//	Overwrite result by Notice.
			if( OP()->Notice()->Has() ){
				$notice = OP()->Notice()->Pop();
				$result = 'Notice: '.$notice['message'];
				$traces = $notice['backtrace'];
			}

		}catch( \Exception $e ){
			//	...
			$result = 'Exception: '.$e->getMessage();
			$traces = $e->getTrace();
		}
	}

	/** Generate Commit ID saved file name.
	 *
	 * @created    2023-02-10
	 * @param      string      $branch
	 * @return     string
	 */
	static function GenerateFilename(string $branch=''):string
	{
		//	...
		if(!$branch ){
			$branch = self::Git()->CurrentBranch();
		}

		//	...
		$version   = PHP_MAJOR_VERSION.PHP_MINOR_VERSION;
		$file_name = ".ci_commit_id_{$branch}_php{$version}";

		return $file_name;
	}

	/** Save inspected branch commit id.
	 *
	 * @created    2023-02-10
	 */
	static function SaveCommitID():void
	{
		//	...
		$branch    = self::Git()->CurrentBranch();
		$commit_id = self::Git()->CurrentCommitID();
		$file_name = self::GenerateFilename();

		//	...
		file_put_contents($file_name, $commit_id);

		//	...
		self::Display("{$branch}:{$commit_id} --> {$file_name}");
	}

	/** Check if current commit id and saved commit id then already checked.
	 *
	 * @created    2023-02-10
	 * @return     boolean
	 */
	static function CheckCommitID():bool
	{
		//	...
		$branch    = self::Git()->CurrentBranch();
		$commit_id = self::Git()->CurrentCommitID();
		$file_name = self::GenerateFilename();

		//	...
		if(!file_exists($file_name) ){
			return false;
		}
		$saved_id  = file_get_contents($file_name);

		//	...
		$io = ($commit_id === $saved_id);

		//	...
		if( $io ){
			self::Display("This branch is already inspected. ($branch)");
		}

		//	...
		return $io;
	}

	/** Display message.
	 *
	 * @created    2023-02-10
	 * @param      string      $message
	 */
	static function Display(string $message)
	{
		//	...
		static $_display = null;

		//	...
		if( $_display === null ){
			$_display = OP()->Request('display') ?? 1;
		}

		//	...
		if(!$_display ){
			return;
		}

		//	...
		$current_dir = OP()->MetaPath()->Encode(getcwd());
		echo "{$current_dir} - {$message}\n";
	}
}
