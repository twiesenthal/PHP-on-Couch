<?php
/**
 * @author twiesenthal
 */

class CouchClientTestCase  extends PHPUnit_Framework_TestCase{

	/**
	 * @var $couch_server array
	 */
	protected static $couch_server = array("scheme" => "http://", "hostname" => "localhost", "port" => "5984");

	/**
	 * @var $test_database_name string
	 */
	protected static $test_database_name = 'couchclienttest';

	/**
	 * @var $client couchClient
	 */
	protected $client = null;

	/**
	 * @var $databaseIsLocked boolean
	 */
	protected static $databaseIsLocked = false;

	public function setUp()
	{
		parent::setUp();
		//
		if(static::$databaseIsLocked){
			$this->markTestSkipped('database was locked');
		}
		$connectionString = static::$couch_server['scheme'] .
			static::$couch_server['hostname'] . ':' .
			static::$couch_server['port'] . '/';
		$this->client = new couchClient($connectionString, static::$test_database_name);
	}

	public function tearDown()
	{
		parent::tearDown();
		$this->client = null;
	}

	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();
		$connectionString = static::$couch_server['scheme'] . static::$couch_server['hostname'] . ':' . static::$couch_server['port'] . '/';
		// get a couchAdmin using a client without username and password
		$client = new couchClient($connectionString, static::$test_database_name);
		$admin = new couchAdmin($client);
		//try if the couch is readable with that couchAdmin. if not mark all the database as locked.
		try{
			$admin->getAllUsers();
		} catch (couchException $e) {
			if($e->getCode() == 302){
				self::$databaseIsLocked = true;
			} else {
				throw $e;
			}
		}
	}
}
