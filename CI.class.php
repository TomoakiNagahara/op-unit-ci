<?php
/**	op-unit-ci:/CI.class.php
 *
 * @created    2023-01-30
 * @author     Tomoaki Nagahara
 * @copyright  Tomoaki Nagahara All right reserved.
 */

/**	Declare strict
 *
 */
declare(strict_types=1);

/**	namespace
 *
 */
namespace OP\UNIT;

/**	use
 *
 */
use OP\IF_UNIT;
use OP\IF_CI;
use OP\OP_CORE;
use OP\UNIT\CI\CI_Client;
use function OP\RootPath;

/**	CI
 *
 * @created    2023-11-21
 * @version    1.0
 * @package    op-unit-ci
 * @author     Tomoaki Nagahara
 * @copyright  Tomoaki Nagahara All right reserved.
 */
class CI implements IF_UNIT, IF_CI
{
	/**	use
	 *
	 */
	use OP_CORE;

	/**	Config
	 *
	 * @deprecated 2024-03-10 Should be separated to CI_Config.
	 * @created    2022-10-15
	 * @moved      2024-03-20  CI --> CI_Config
	 * @var        array
	 */
	private $_config = [];

	/**	Automatically code inspection.
	 *
	 * @created     2023-11-21
	 */
	function Auto() : bool
	{
		/* move to index.php
		//	Save
		self::GitStashSave();
		*/

		//	...
		if( OP()->Request('all') ?? 1 ){
			$io = self::All();
		}else{
			$io = self::Single();
		}

		/* move to index.php
		//	Pop
		self::GitStashPop();
		*/

		//	...
		return $io;
	}

	/**	Git stash save to all repositories.
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

	/**	Git stash pop to saved repositories.
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

	/**	All submodules code inspection.
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

	/**	Single submodule code inspection.
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

	/**	Check dry-run argument value.
	 *
	 * @created	 2023-11-22
	 * @return	 boolean
	 */
	static function Dryrun()
	{
		return CI_Client::Dryrun();
	}

	/**	Return OP\UNIT\Git
	 *
	 * @created     2023-11-21
	 * @return      Git
	 */
	static function Git() : Git
	{
		return OP()->Unit('Git');
	}

	/**	Generate inspection file name.
	 *
	 * @created	 2023-11-21
	 * @param	 string		 $branch
	 * @return	 string
	 */
	static function GenerateFilename(string $branch='') : string
	{
		return CI_Client::GenerateFilename($branch);
	}

	/**	Return CI Config instance.
	 *
	 * <pre>
	 * //  Get CI Config instance.
	 * $ci = OP()->Unit('CI')->Config();
	 *
	 * //  Set CI configuration.
	 * $ci->Set('MethodName', 'result', 'args');
	 *
	 * //  Return CI configuration.
	 * return $ci->Get();
	 * </pre>
	 *
	 * @return CI\CI_Config
	 */
	static function Config() : CI\CI_Config
	{
		require_once(__DIR__.'/CI_Config.class.php');
		return new CI\CI_Config();
	}
}
