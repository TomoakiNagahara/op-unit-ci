<?php
/** op-unit-ci:/CI_Client.class.php
 *
 * @created    2024-03-10
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
use OP\IF_UNIT;
use OP\OP_CORE;
use OP\DebugBacktrace;
use function OP\RootPath;

/** CI_Client
 *
 * @created    2023-01-30
 * @renamed    2023-11-21   CI --> CI_Client
 * @separated  2024-03-10   CI.class.php --> CI_Client.class.php
 * @version    1.0
 * @package    op-unit-ci
 * @author     Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright  Tomoaki Nagahara All right reserved.
 */
class CI_Client implements IF_UNIT
{
	/** use
	 *
	 */
	use OP_CORE;

	/** Git
	 *
	 * @created    2023-02-05
	 * @return    \OP\UNIT\Git
	 */
	static function Git() : \OP\UNIT\Git
	{
		static $_git;
		if(!$_git ){
			$_git = OP()->Unit('Git');
		}
		return $_git;
	}

	/** Automatically CI
	 *
	 * @return	 bool
	 */
	static function Auto() : bool
	{
		if( $io = self::Init() ){
			$io = self::CI();
		}else{
			$io = true;
		}
		return $io;
	}

	/** Init
	 *
	 * @created    2023-02-05
	 * @return     bool       If true, CI is necessary.
	 */
	static function Init() : bool
	{
		//	...
		OP()->MIME('text/plain');

		//	...
		if( file_exists('ci.sh') or file_exists('.ci.sh') ){
			//	CI
		}else{
			self::Display('Does not found ci.sh or .ci.sh file.');
			return false;
		}

		//	...
		if( file_exists('.ci_skip') ){
			self::Display('Found .ci_skip file.');
			self::SaveCommitID();
			return false;
		}

		//	...
		if(!file_exists('.git') ){
			$current = getcwd();
			OP()->Notice("Does not found .git directory.(current={$current})");
			return false;
		}

		//	...
		if( self::CheckCommitID() ){
			return false;
		}

		//	Autoloader
		if( file_exists('autoloader.php') ){
			include_once('autoloader.php');
		}

		//	...
		return true;
	}

	/** CI
	 *
	 * @created    2023-02-05
	 * @return     boolean
	 */
	static function CI() : bool
	{
        //  Init
        $is_core  = null;
        $curr_dir = realpath( getcwd().'/'     );
        $core_dir = realpath( RootPath('core') );
        $unit_dir = realpath( RootPath('unit') );

        //	Get namespace
        if( $curr_dir === $core_dir ){
            $is_core   = true;
            $namespace = 'OP\\';
        }else if( strpos($curr_dir, $unit_dir) === 0 ){
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

			/*
            //  Include class file.
            require_once($file);

            //  Cut of name.
            $name = basename($file, '.class.php');

            //  Instantiate Object from class.
            $class = $namespace . $name;
            $class = str_replace('-', '\\', $class);
            $obj = new $class();

            //  Include config file.
        //  $configs = OP()->Template("./ci/{$name}.php");

			//	Inspect each instantiate object.
            if(!self::CI_Class($obj) ){
                return false;
            }
            */

			/** Join namespace to class name.
			 *
			 *  If included namespace in file name.
			 *  1. Foo-Bar.class.php
			 *  1. Foo-Bar
			 *  1. Foo-Bar --> Foo, Bar
			 *  1. Foo --> FOO
			 *  1. \OP\UNIT\FOO\Bar
			 */
			$name  = basename($file, '.class.php');
			//	If included namespace in file name.
			if( $is_core ){
				$names = '';
			}else
			if( strpos($name,'-') !== false ){
			$names = explode('-', $name);
			$name  = array_pop($names);
			$names = $names ? join('\\', $names).'\\': '';
			$names = strtoupper($names);
			}else{
				/** If not included namespace in file name.
				 *
				 *  # main class
				 *  1. Foo.class.php
				 *  1. \OP\UNIT\Foo
				 *
				 *  # sub class
				 *  1. Bar.class.php --> Bar
				 *  1. asset:/unit/foo --> FOO
				 *  1. \OP\UNIT\FOO\Bar
				 */
				$basename = basename(getcwd());
				if( $basename === strtolower($name) ){
					$names = '';
				}else{
					$names = strtoupper($basename).'\\';
				}
			}
			$class = $namespace . $names . $name;
			/*
			$class = str_replace('-', '\\', $class);
			*/

			//	Include class file if not load.
			if(!class_exists($class, false) ){
				require_once($file);
			}

			//	Instantiate Object from namespace class.
			$obj = new $class();

			//	Inspect that instantiate object.
			if(!self::CI_Class($obj) ){
				return false;
			}
		}

        /*
		//	Do testcase.
	//	OP::Template('core:/include/ci_testcase.php', $config);
		// TODO: Remove core:/include/ci_testcase.php later.
        if( OP()->Request('testcase') ){
            $this->Testcase();
        }
        */

		//	Save Commit ID.
		self::SaveCommitID();

        //  ...
        return true;
	}

	/** CI each Classes.
	 *
	 * @created    2023-02-10
	 * @param      object      $obj
	 * @return     boolean     $io
	 */
	static function CI_Class(object $obj) : bool
	{
		//	...
		if(!isset(class_uses($obj, false)['OP\OP_CI']) ){
			$class_name = get_class($obj);
			/*
			throw new Exception("This object has not use OP_CI. ({$class_name})");
			*/
			echo "This object has not use OP_CI. ({$class_name})\n";
			return false;
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
		$configs = CIConfig($obj);
		/** If $configs is empty, throw Exception in CIConfig().
		 * @deprecated 2024-03-10
		if( empty($configs) ){
			return false;
		}
		*/

        //  Get skip method.
        $skip = OP()->Request('skip');

		//	...
		foreach( $methods as $method ){
			//	Skip method
			switch( $method ){
                //  Can skip method only once by argument.
                case ($method === $skip):

                //	CI relative method is skip.
				case (strpos($method, '__') === 0 ) ? true: false;
				case 'CI':
				case 'CI_AllMethods':
				case 'CI_Inspection':
				case 'Auto':
					continue 2;
			}

			//	If Config() method.
			if( $method === 'Config' ){
				//	OP_UNIT::Config()
				if( _OP_APP_BRANCH_ < 2024 ){
					//	Under 2024
					$class_name = get_class($obj);
					if( strpos($class_name, 'OP\\UNIT\\')   === 0 or
						strpos($class_name, 'OP\\MODULE\\') === 0
					){
						//	Unit only
						self::Display("Starting from the 2024 branch, CI for the config method will be required.");
						continue;
					}
				}
			}

			//	Check has config.
			if( empty($configs[$method]) ){
				$class_name = get_class($obj);
				echo "This method config is not set. ({$class_name} -> {$method})\n";
				return false;
			}

			//	$configs[$method] is configs by each args.
			foreach( $configs[$method] as $config ){
				//	Inspect each args
				if(!self::CI_Method($obj, $method, $config) ){
					return false;
				}
			}
		}

        //  ...
        return true;
	}

	/** CI Class each Methods.
	 *
	 * @created    2023-02-10
	 * @param      object      $obj
	 * @param      string      $method
	 * @param      array       $configs
	 * @return     boolean     $io
	 */
	static function CI_Method(object $obj, string $method, array $config) : bool
	{
			//	...
			$trace  = $config['trace']  ?? null;
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
				include_once(__DIR__.'/function/Serialize.php');
				$class = get_class($obj);
				$pson  = is_null($config['args']) ? null: Serialize($args);
				echo "\n{$class}->{$method}({$pson}) is not match expect and result.\n";

				//	...
				if( $trace ){
					echo " --> {$trace[0]} #{$trace[1]}\n";
				}else{
					$class_name = get_class($obj);
					echo "\n{$class_name} -> {$method}() is not define in ci config.\n";
				}

				//	...
				/*
				echo "\n";
				echo 'Config: ';
				\OP\UNIT\Dump::MarkPlain($config, []);
				*/
				echo "\n";
				echo 'Expect: ';
				var_dump($expect);
				/*
				\OP\UNIT\Dump::MarkPlain($expect, []);
				*/
				echo "\n";
				echo 'Result: ';
				var_dump($result);
				/*
				\OP\UNIT\Dump::MarkPlain($result, []);
				*/

				echo "\n";

				//	...
				if( is_array($expect) ){
					$expect = json_encode($expect);
					$expect = str_replace('\/', '/', $expect);
				}
				if( is_array($result) ){
					$result = json_encode($result);
					$result = str_replace('\/', '/', $result);
				}

                //  ...
                if( is_string($expect) and is_string($result) ){
                    $match = '            "';
                    for($i=0, $len=strlen($expect); $i<$len; $i++){
                        if( $expect[$i] === $result[$i] ){
                            $match .= $expect[$i];
                        }else{
                            $match .= '^';
                            break;
                        }
                    }
                    echo "Hint..: {$match}\n\n";
                }

                //	...
                DebugBacktrace::Auto($traces);

				//  ...
				return false;
			}

		//  ...
		return true;
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

		}catch( \Throwable $e ){
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
			$branch = self::Git()->Branch()->Current();
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
		if( self::Dryrun() ){
			return;
		}

		//	...
		$branch    = self::Git()->Branch()->Current();
		$commit_id = self::Git()->Commit()->ID();
		$file_name = self::GenerateFilename();

		//	...
		if( file_exists($file_name) ){
			$saved_id  = file_get_contents($file_name);
			if( $commit_id === $saved_id ){
				return;
			}
		}

		//	...
		$io = file_put_contents($file_name, $commit_id);
		if( $io ){
			self::Display("{$branch}:{$commit_id} --> {$file_name}");
		}else{
			self::Display("Failed to save commit ID: `$branch` -> `$file_name`");
		}
	}

	/** Check if current commit id and saved commit id then already checked.
	 *
	 * @created    2023-02-10
	 * @return     boolean		true is already inspected.
	 */
	static function CheckCommitID():bool
	{
		//	...
		if( OP()->Request('force') ?? 0 ){
			return false;
		}

		//	...
		$branch    = self::Git()->Branch()->Current();
		$commit_id = self::Git()->Commit()->ID();
		$file_name = self::GenerateFilename();

		//	...
		if(!file_exists($file_name) ){
			self::Display("The file does not exist: `$file_name`");
			return false;
		}
		$saved_id  = file_get_contents($file_name);

		//	...
		$io = ($commit_id === $saved_id);

		//	...
		if( $io ){
			self::Display("This branch has already been inspected: `$branch`");
		}else{
			self::Display("Does not match the commit ID: $commit_id != $saved_id");
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
		static $_padding = 15;

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
		if( $_padding < strlen($current_dir) ){
			$_padding = strlen($current_dir);
		}
		$current_dir = str_pad($current_dir, $_padding, ' ', STR_PAD_RIGHT);
		echo "{$current_dir} - {$message}\n";
	}

	/** Dry run check.
	 *
	 * @return boolean
	 */
	static function Dryrun() : bool
	{
		//	..
		static $io;

		//	...
		if( $io === null ){
			$io = false;

		//	...
		$request = OP()->Request();

		//	If set unit.
		if( $request['unit'] ?? null ){
			//	If set dry-run
			if( $request['dry-run'] ?? null ){
				//	Already set.
			}else{
				//	Set to dry-run is true.
				$request['dry-run'] =  true;
				self::Display("Found unit args. change to dry-run=1.");
			}
		}

		//	...
		foreach( ['dry-run', 'dryrun', 'test'] as $key ){
			//	...
			if( $request[$key] ?? null ){
				$io = true;
			}
		}
		}

		//	...
		return $io ?? false;
	}
}
