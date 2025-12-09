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

	/**	Check if the current process is running on GitHub Actions
	 *
	 * @created    2025-12-09
	 * @return     bool
	 */
	static function isGitHubActions() : bool
	{
		return getenv('GITHUB_ACTIONS') === 'true';
	}

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
		$git_root = RootPath('git');

		//	...
		try{
			//	Get config from .gitmodules
			require_once(__DIR__.'/function/GetSubmoduleConfig.php');
			$configs = CI\GetSubmoduleConfig();

			//	...
			if( $configs ){
				include_once(_ROOT_GIT_."/asset/unit/git/function/SubmoduleConfig.php");

			//	Each submodule repositories.
			foreach( $configs as $config ){
				//	...
				$path = $git_root . $config['path'];

				//	...
				if(!is_dir($path) ){
					OP()->Error("This path is not directory: {$path}");
					continue;
				}

				//	...
				chdir($path);

				//	...
				if(!$io = self::Single() ){
					break;
				}
			}

			//	op-core's submodules.
			if( $io and !self::Dryrun() ){
			chdir(_ROOT_CORE_);
			foreach( \OP\UNIT\GIT\SubmoduleConfig() as $config ){
				chdir( _ROOT_CORE_ . "/{$config['path']}" );
				CI_Client::SaveCommitID();
			}
			}

			//	Non git managed submodules
			foreach( glob(_ROOT_ASSET_.'/config/submodule/*/*.php') as $glob ){
				//	Init
				$temp = explode('/', $glob);
				$name = array_pop($temp);
				$type = array_pop($temp);
				$name = substr($name, 0, -4);
				//	Check if public_html
				if( $type === 'public_html' ){
					$conf = ( function($path){ return include($path); } )($glob);
					$path = _ROOT_GIT_ . $conf['path'] ?? $name;
				}else{
					$path = _ROOT_ASSET_ . "{$type}/{$name}/";
				}
				//	Check
				if(!file_exists("{$path}/.gitmodules") ){
					continue;
				}
				if(!chdir($path) ){
					continue;
				}
				//	Nested submodules
				foreach( \OP\UNIT\GIT\SubmoduleConfig() as $config ){
					if(!chdir( $path . $config['path'] )){
						continue;
					}
					CI_Client::SaveCommitID();
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
