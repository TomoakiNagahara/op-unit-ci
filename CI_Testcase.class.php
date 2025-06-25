<?php
/**	op-unit-ci:/CI_Testcase.class.php
 *
 * @created		2023-11-23
 * @author		Tomoaki Nagahara
 * @copyright	Tomoaki Nagahara All right reserved.
 */

/**	Declare strict
 *
 */
declare(strict_types=1);

/**	namespace
 *
 */
namespace OP\UNIT\CI;

/**	use
 *
 */
use Exception;
use OP\IF_UNIT;
use OP\OP_CORE;

/**	CI_Testcase
 *
 * @created		2023-11-23
 * @version		1.0
 * @package		op-unit-ci
 * @author		Tomoaki Nagahara
 * @copyright	Tomoaki Nagahara All right reserved.
 */
class CI_Testcase implements IF_UNIT
{
	/**	use
	 *
	 */
	use OP_CORE;

	/**	PHP Built-in server process resource.
	 *
	 * @var resource
	 */
	private $_server;

	/**	Destruct
	 *
	 * @created		2022-11-10
	 */
	function __destruct()
	{
		//  ...
		$this->_TestcaseServerDown();
	}

	/**	Do the Testcase.
	 *
	 * @created		2023-04-25
	 * @return		boolean
	 */
	function Testcase() : bool
	{
		//  ...
		$fail = false;
		$port = rand(8900,8999);
		$url  = "localhost:{$port}";
		$php  = $_SERVER['_'];
		$app  = OP()->MetaPath('app:/');

		//  ...
		$this->_TestcaseServer($php, $app, $url, $port);

		/*
		//  ...
		$exec = "{$php} -S {$url} {$app}/testcase.php > /dev/null 2>&1 &";

		//  ...
		if( 0 ){
			echo exec($exec)."\n";
		}else{
			$handle = popen($exec, 'r');
		}

		//  Connection test.
		for($i=0; $i<10; $i++){
			//  ...
			if( $io = `curl -Ss http://{$url}` ){
				//  ...
				echo "#{$i} Waiting http://{$url}\n";
				//  ...
				sleep(1);
			}
		}
		if( $io === null ){
			echo "\n";
			echo "hint: HTTP server is not running: ($url)\n";
			echo "hint: cd {$app}\n";
			echo "hint: {$php} -S localhost:{$port} testcase.php";
			throw new Exception("The testcase was failed.");
		}
		*/

		//  ...
		foreach( glob('./testcase/*.php') as $path ){

			//  Skip if dot, underscore, lower case.
			$file   = basename($path);
			$char   = $file[0];
			if( $char === '.' or $char === '_' or $char !== strtoupper($char) ){
				continue;
			}

			//  Do test via Web.
			/*
			 $path   = OP()->MetaToURL($path);
			 */
			$path   = realpath($path);
			$path   = OP()->MetaFromPath($path);
			$result = `curl -Ss {$url}?path={$path}`;

			//  If it returns 1, that passes the test.
			if( $result[0].$result[1] === "1\n" ){
				continue;
			}

			//  ...
			$fail = true;
			break;
		}

		/* @var $handle resource */
		/*
		if( $handle ?? null ){
			$read = fread($handle, 2096);
			echo $read;
			pclose($handle);
		}else{
			//  ...
			foreach(explode("\n", `ps | grep "{$app}/testcase.php"`) as $line){
				if( $pos = strpos($line, 'localhost') ){
					$pos = strpos($line, ' ');
					$str = substr($line, 0, $pos);
					echo `kill {$str}`;
				}
			}
		}
		*/

		//  ...
		if( $fail ){
			D($result);
			throw new Exception("Testcase was failed. ($path)");
		}

		//  ...
		return !$fail;
	}

	/**	Boot testcase web server.
	 *
	 * @created     2023-10-23
	 * @param       string      $php
	 * @param       string      $app
	 * @param       string      $url
	 * @param       integer     $port
	 * @throws      Exception
	 */
	private function _TestcaseServer($php, $app, $url, $port)
	{
		//  ...
		$path = $app . '/testcase.php';
		//  $path = realpath($path);
		$exec = "{$php} -S {$url} {$path} > /dev/null 2>&1 &";
		$this->_TestcaseKill($path);
		$this->_server = popen($exec, 'r');

		//  ...
		if( OP()->Request('debug') ){
			D($php, $app, $url, $port);
		}

		//  Connection test.
		for($i=1; $i<10; $i++){
			//  ...
			$usleep = (20 * $i) * $i;

			//  ...
			usleep($usleep);

			//  ...
			if( $io = `curl -Ss http://{$url}` ){
				break;
			}else{
				//  ...
				echo "#{$i} Waiting({$usleep}) http://{$url}\n";
			}
		}

		//  ...
		if( $io === null ){
			/*
			//  ...
			echo "\n";
			echo "hint: HTTP server is not running: ($url)\n";
			echo "hint: cd {$app}\n";
			echo "hint: {$php} -S localhost:{$port} testcase.php";
			*/
			throw new Exception("app:/testcase.php could not boot.\n");
		}
	}

	/**	Shutdown testcase web server.
	 *
	 * @created     2023-10-23
	 */
	private function _TestcaseServerDown()
	{
		if( $this->_server ){
			echo fread($this->_server, 2096);
			pclose($this->_server);
		}
	}

	/**	Kill zombie processes.
	 *
	 * @created     2023-11-10
	 * @param       string      $path
	 */
	private function _TestcaseKill(string $path)
	{
		$exec = "ps -A | grep $path";
		$list = [];
		$exit = 0;
		$temp = exec($exec, $list, $exit);
		if( $exit ){
			D($exec, $exit, $temp);
			return;
		}
		foreach($list as $line){
			$match = [];
			if(!strpos($line, ' -S localhost:89') ){
				continue;
			}
			if(!preg_match('/^(\d+) .+/', $line, $match) ){
				continue;
			}
			$pnum = $match[1];
			D('Kill --> '.$line);
			`kill {$pnum}`;
		}
	}
}
