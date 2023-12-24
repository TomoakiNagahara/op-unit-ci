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
use OP\DebugBacktrace;
use function OP\RootPath;

/** ci
 *
 * @created    2023-11-21
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
	use OP_CORE;

	/** Config
	 *
	 * @created   2022-10-15
	 * @var       array
	 */
	private $_config;

	/** PHP Built-in server process resource.
	 *
	 * @deprecated	2023-11-23
	 * @var resource
	 */
//	private $_server;

    /** Save git stash saved.
     *
	 * @deprecated	2023-11-23
     * @created     2022-11-12
     * @var         boolean
     */
//  private $_git_stash_save;

    /** Construct
     *
     * @created     2022-11-12
     */
    function __construct()
    {
    }

    /** Destruct
     *
     * @created     2022-11-10
     */
    function __destruct()
    {
    }

	/** Automatically code inspection.
	 *
	 * @created     2023-11-21
	 */
	function Auto() : bool
	{
		//	Save
		$status = self::GitStashSave();

		//	...
		if( OP()->Request('all') ?? 1 ){
			$io = self::All();
		}else{
			$io = self::Single();
		}

		//	Pop
		self::GitStashPop($status);

		//	...
		return $io;
	}

	/** Git stash save to all repositories.
	 *
	 * @created		2023-11-24
	 * @return		array
	 */
	static function GitStashSave(): array
	{
		//	...
		$status = [];

		//	...
		if( self::Dryrun() ){
			return $status;
		}

		//	...
		try{

		//	...
		$current_dir = getcwd();

		//	...
		$git_root = RootPath('git');

		//	...
		$configs = self::Git()->SubmoduleConfig();

		//	...
		foreach( $configs as $config ){
			//	...
			$path = $config['path'];
			//	...
			chdir($git_root . $path);
			//	...
			if( self::Git()->Stash()->Save() ){
				//	...
				CI_Client::Display("git stash save : {$path}");

				//	...
				$temp['path'] = $path;
				$status[] = $temp;
			}
		}
		}catch( \Throwable $e ){
			echo $e->getMessage() . "\n";
			echo $e->getTraceAsString() . "\n";
		}

		//	...
		chdir($current_dir);

		//	...
		return $status;
	}

	/** Git stash pop to saved repositories.
	 *
	 * @created		2023-11-24
	 * @param		array		$status
	 */
	static function GitStashPop(array $status) : void
	{
		try{
		//	...
		$current_dir = getcwd();

		//	...
		$git_root = RootPath('git');

		//	...
		foreach( $status as $state ){
			$path = $state['path'];
			chdir($git_root . $path);
			self::Git()->Stash()->Pop();
			CI_Client::Display("git stash pop : {$path}");
		}
		}catch( \Throwable $e ){
			echo $e->getMessage() . "\n";
			echo $e->getTraceAsString() . "\n";
		}

		//	...
		chdir($current_dir);
	}

	/** All submodules code inspection.
	 *
	 * @created     2023-11-20
	 * @return      bool
	 */
	static function All() : bool
	{
		//	...
		$save_dir = getcwd();

		//	...
		try{
			//	Get config from .gitmodules
			$configs = self::Git()->SubmoduleConfig();

			//	Each submodule repositories.
			foreach( $configs as $config ){
				$path = $config['path'];
				chdir(RootPath('git') . $path);

				//	...
				if(!$io = self::Single() ){
					break;
				}
			}

			//	Main repository.
			if( $io ){
				chdir(RootPath('git'));
				$io = self::Single();
			}
		}catch( \Exception $e ){
			OP()->Notice($e);
		}

		//	...
		chdir($save_dir);

		//	...
		return $io;
	}

	/** Single submodule code inspection.
	 *
	 * @created     2023-11-20
	 * @return      bool
	 */
	static function Single() : bool
	{
		//	...
		try{
			$io = CI_Client::Auto();
		}catch( \Exception $e ){
			OP()->Notice($e);
		}

		//	...
		return $io ?? false;
	}

	/** Check dry-run argument value.
	 *
	 * @created	 2023-11-22
	 * @return	 boolean
	 */
	static function Dryrun()
	{
		return CI_Client::Dryrun();
	}

	/** Return OP\UNIT\Git
	 *
	 * @created     2023-11-21
	 * @return      Git
	 */
	static function Git() : Git
	{
		return OP()->Unit('Git');
	}

	/** Set Config.
	 *
	 * @created    2022-10-15
	 * @moved      2023-02-22 op-core:/CI.class.php
	 * @param      string     $method
	 * @param      array      $args
	 * @param      array      $result
	 */
	function Set($method, $result, $args)
	{
		$this->_config[$method][] = [
			'result' => $result,
			'args'   => $args,
		];
	}

	/** Generate Config.
	 *
	 * @created    2022-10-15
	 * @moved      2023-02-22 op-core:/CI.class.php
	 * @return     array      $config
	 */
	function GenerateConfig() : array
	{
		//	Swap config.
		$config = $this->_config;
		//	Reset config.
		$this->_config = [];
		//	Return config.
		return $config;
	}

	/** Generate inspection file name.
	 *
	 * @created	 2023-11-21
	 * @param	 string		 $branch
	 * @return	 string
	 */
	static function GenerateFilename(string $branch='') : string
	{
		return CI_Client::GenerateFilename($branch);
	}
}

/** ci
 *
 * @created    2023-01-30
 * @renamed    2023-11-21   CI --> CI_Client
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

		/*
		//	...
		if( self::CheckCommitID() ){
			return false;
		}
		*/

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
        $curr_dir = realpath( getcwd().'/'     );
        $core_dir = realpath( RootPath('core') );
        $unit_dir = realpath( RootPath('unit') );

        //	Get namespace
        if( $curr_dir === $core_dir ){
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

            //  Include class file.
            require_once($file);

            //  Cut of name.
            $name = basename($file, '.class.php');

            //  Instantiate Object from class.
            $class = $namespace . $name;
            $obj = new $class();

            /*
            //  Include config file.
            $configs = OP()->Template("./ci/{$name}.php");
            */

			//	Inspect each instantiate object.
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
		$configs = CI\CIConfig($obj);

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
					continue 2;
			}

			//	Inspect each method.
            if(!self::CI_Method($obj, $method, $configs[$method] ?? [[]]) ){
                return false;
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
	static function CI_Method(object $obj, string $method, array $configs) : bool
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
                //  ...
                $class = get_class($obj);
                echo "\n{$class}->{$method}(".serialize($args).") is unmatch expect and result.\n";

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

                //  ...
                if( is_string($expect) and is_string($result) ){
                    $match = '';
                    for($i=0, $len=strlen($expect); $i<$len; $i++){
                        if( $expect[$i] === $result[$i] ){
                            $match .= $expect[$i];
                        }else{
                            $match .= '^';
                            break;
                        }
                    }
                    echo "Hint..: \"{$match}\"\n\n";
                }

				//	...
				if( $traces ){
					$i = count($traces);
					/*
					echo "\n{$result}\n\n";
					*/
					foreach( $traces as $trace){
						$i--;
						$n = str_pad((string)$i, 2, ' ', STR_PAD_LEFT);
						/*
						echo "$n: ".OP()->DebugBacktraceToString($trace)."\n";
						*/
						echo "$n: ".DebugBacktrace::Numerator($trace)."\n";
					}
				}

				//  ...
				return false;
			}
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
        //  ...
        if( self::Dryrun() ){
            return;
        }

		//	...
		$branch    = self::Git()->CurrentBranch();
		$commit_id = self::Git()->CurrentCommitID();
		$file_name = self::GenerateFilename();

		//	...
		if( file_exists($file_name) ){
			$saved_id  = file_get_contents($file_name);
			if( $commit_id === $saved_id ){
				/*
				self::Display("This branch is already inspected. ($branch)");
				*/
				return;
			}
		}

		//	...
		$io = file_put_contents($file_name, $commit_id);
		if( $io ){
			self::Display("Saved current commit id. ($branch, $file_name)");
		}else{
			self::Display("Failed save commit id. ($branch, $file_name)");
		}

		//	...
		self::Display("{$branch}:{$commit_id} --> {$file_name}");
	}

	/** Check if current commit id and saved commit id then already checked.
	 *
	 * @created    2023-02-10
	 * @return     boolean		true is already inspected.
	 */
	static function CheckCommitID():bool
	{
		//	...
		if( OP()->Request('force') ){
			return false;
		}

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
        return (OP()->Request('test', false) or OP()->Request('dry-run', false)) ? true : false;
    }
}
