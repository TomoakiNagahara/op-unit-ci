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
use OP\IF_UNIT;
use OP\OP_CORE;
use OP\UNIT\CI\CI_Client;
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
	 * @deprecated 2024-03-10 Should be separated to CI_Config.
	 * @created    2022-10-15
	 * @moved      2024-03-20  CI --> CI_Config
	 * @var        array
	 */
	private $_config = [];

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
	 */
	static function GitStashSave()
	{
		if( self::Dryrun() ){
			return;
		}
		include(__DIR__.'/include/GitStashSave.php');
	}

	/** Git stash pop to saved repositories.
	 *
	 * @created		2023-11-24
	 */
	static function GitStashPop()
	{
		if( self::Dryrun() ){
			return;
		}
		include(__DIR__.'/include/GitStashPop.php');
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
			require_once(__DIR__.'/function/GetSubmoduleConfig.php');
			$configs = CI\GetSubmoduleConfig();

			//	...
			if( $configs ){

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
				/*
				$io = self::Single();
				*/
				CI_Client::SaveCommitID();
			}

			}
		}catch( \Throwable $e ){
			OP()->Notice($e);
		}

		//	...
		chdir($save_dir);

		//	...
		return $io ?? false;
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
		}catch( \Throwable $e ){
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
