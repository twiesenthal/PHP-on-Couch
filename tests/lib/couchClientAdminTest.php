<?php

class couchClientAdminTest extends CouchClientTestCase
{
	/**
	 * @var $admin array
	 */
	private $admin = array("login"=>"adm", "password"=>"sometest");

	/**
	 * @var $adminClient couchClient
	 */
	private $adminClient = null;

	public function setUp()
    {
		parent::setUp();
		$connectionString = static::$couch_server['scheme'] .
			$this->admin["login"] . ':' .
			$this->admin["password"] . '@' .
			static::$couch_server['hostname'] . ':' .
			static::$couch_server['port'] . '/';
		$this->adminClient = new couchClient($connectionString, static::$test_database_name);
    }

	public function tearDown()
    {
		parent::tearDown();
		try{
			$this->_removeInitialAdminUser();
		} catch( Exception $e){
			// ignore if user wasn't there because maybe in some tests we don't create one
			// but in most we do :)
		}
		$this->adminClient = null;
    }

	/**
	 * @group couchAdmin
	 */
	public function testCreateAdmin () {
		$authorizedAdmin = new couchAdmin($this->client);
		$result = $authorizedAdmin->createAdmin($this->admin["login"],$this->admin["password"]);
		//check the result of admin creation
		$this->assertTrue($result->ok, 'result was not ok');
		$this->assertEquals('org.couchdb.user:'.$this->admin["login"], $result->id, 'result id was not like expected');
	}

	/**
	 * the thrown exception can't be caught by @expectedException
	 * because we need to remove the admin user after the exception is thrown
	 * @group couchAdmin
	 */
	public function testCreateDatabaseFailsIfAdminIsSet () {
		$this->_createInitialAdminUser();
		$code = 0;
		try {
			$this->client->createDatabase("test");
		} catch ( Exception $e ) {
			$code = $e->getCode();
		}
		//check if the exception is the one we expected
		$this->assertEquals(302,$code);
	}

	/**
	 * @group couchAdmin
	 */
	public function testAdminCanCreateAndDeleteADatabase () {
		$this->_createInitialAdminUser();
		$createResult = null;
		$deleteResult = null;
		try{
			//create and delete a database
			$createResult = $this->adminClient->createDatabase();
			$deleteResult = $this->adminClient->deleteDatabase();
		} catch (Exception $e) {
			$this->fail($e->getMessage());
		}
		//create and delete a database
		$createResult = $this->adminClient->createDatabase();
		$deleteResult = $this->adminClient->deleteDatabase();

		//check result of database creation
		$this->assertInternalType("object", $createResult);
		$this->assertObjectHasAttribute("ok",$createResult);
		$this->assertEquals($createResult->ok,true);

		//check result of database deletion
		$this->assertInternalType("object", $deleteResult);
		$this->assertObjectHasAttribute("ok",$deleteResult);
		$this->assertEquals($deleteResult->ok,true);
	}

	/**
	 * @group couchAdmin
	 */
	public function testCreateDeleteUserAccount () {
		$this->_createInitialAdminUser();
		$authorizedAdmin = new couchAdmin($this->adminClient);
		//create a users
		$resultCreateUser = $authorizedAdmin->createUser("joe","dalton");
		//delete a users
		$resultDeleteUser = $authorizedAdmin->deleteUser("joe");

		//check result of user deletion
		$this->assertInternalType("object", $resultCreateUser);
		$this->assertObjectHasAttribute("ok",$resultCreateUser);
		$this->assertEquals($resultCreateUser->ok,true);

		//check result of user deletion
		$this->assertInternalType("object", $resultDeleteUser);
		$this->assertObjectHasAttribute("ok",$resultDeleteUser);
		$this->assertEquals($resultDeleteUser->ok,true);
	}

	/**
	 * @group couchAdmin
	 */
	public function testAllUsers () {
		$this->_createInitialAdminUser();
		//create some users
		$authorizedAdmin = new couchAdmin($this->adminClient);
		$authorizedAdmin->createUser("joe","dalton");

		//get all users
		$result = $authorizedAdmin->getAllUsers(true);

		//remove users to keep the database clean
		$authorizedAdmin->deleteUser("joe");

		//check results
		$this->assertInternalType("array", $result);
		$this->assertEquals(count($result),2);
	}

	/**
	 * @group couchAdmin
	 */
	public function testGetUser () {
		//create some users
		$this->_createInitialAdminUser();
		$authorizedAdmin = new couchAdmin($this->adminClient);
		$authorizedAdmin->createUser("joe","dalton");
		//get User
		$result = $authorizedAdmin->getUser("joe");
		//remove users to keep the database clean
		$authorizedAdmin->deleteUser("joe");
		//check results
		$this->assertInternalType("object", $result);

		$this->assertObjectHasAttribute("_id",$result);
		$this->assertEquals("org.couchdb.user:joe",$result->_id);

		$this->assertObjectHasAttribute("type",$result);
		$this->assertEquals("user",$result->type);
	}

	/**
	 * @group couchAdmin
	 */
	public function testUserAccountWithRole () {
		$roles = array("badboys","jailbreakers");
		// create some users
		$this->_createInitialAdminUser();
		$authorizedAdmin = new couchAdmin($this->adminClient);
		$createUserResult = $authorizedAdmin->createUser("jack","dalton",$roles);

		// get user "jack"
		$getUserResult = $authorizedAdmin->getUser("jack");

		//remove users to keep the database clean
		$authorizedAdmin->deleteUser("jack");

		//check create user result
		$this->assertInternalType("object", $createUserResult);
		$this->assertObjectHasAttribute("ok",$createUserResult);
		$this->assertEquals($createUserResult->ok,true);

		//check get user result
		$this->assertInternalType("object", $getUserResult);
		$this->assertObjectHasAttribute("_id",$getUserResult);
		$this->assertObjectHasAttribute("roles",$getUserResult);

		//check roles
		$this->assertInternalType("array", $getUserResult->roles);
		$this->assertEquals(count($getUserResult->roles),2);
		foreach ( $getUserResult->roles as $role ) {
			$this->assertEquals(in_array($role,$roles),true);
		}
	}

	/**
	 * @group couchAdmin
	 */
	public function testGetSecurity () {
		// create an admin user which will be "gabage collected" in tearDown()
		$this->_createInitialAdminUser();
		// the database is needed for getSecurity(), which we want to test
		$this->adminClient->createDatabase();
		$authorizedAdmin = new couchAdmin($this->adminClient);

		$security = $authorizedAdmin->getSecurity();

		//remove the database as we don't need it anymore
		$this->adminClient->deleteDatabase();
		//check getSecurity result

		$this->assertObjectHasAttribute("admins",$security);
		$this->assertObjectHasAttribute("readers",$security);
		$this->assertObjectHasAttribute("names",$security->admins);
		$this->assertObjectHasAttribute("roles",$security->admins);
		$this->assertObjectHasAttribute("names",$security->readers);
		$this->assertObjectHasAttribute("roles",$security->readers);
	}

	/**
	 * @group couchAdmin
	 */
	public function testSetSecurity () {
		// create an admin user which will be "gabage collected" in tearDown()
		$this->_createInitialAdminUser();

		// the database is needed for getSecurity(), which we want to test
		$this->adminClient->createDatabase();

		$authorizedAdmin = new couchAdmin($this->adminClient);
		$security = $authorizedAdmin->getSecurity();
		$security->admins->names[] = "joe";
		$security->readers->names[] = "jack";
		$result = $authorizedAdmin->setSecurity($security);
		$security = $authorizedAdmin->getSecurity();
		//remove database.
		$this->adminClient->deleteDatabase();
		//check setSecurity result
		$this->assertInternalType("object", $result);
		$this->assertObjectHasAttribute("ok",$result);
		$this->assertEquals($result->ok,true);
		//check security was set the right way
		$this->assertEquals(count($security->readers->names),1);
		$this->assertEquals(reset($security->readers->names),"jack");
		$this->assertEquals(count($security->admins->names),1);
		$this->assertEquals(reset($security->admins->names),"joe");
	}

	/**
	 * @group couchAdmin
	 */
	public function testDatabaseAdminUser () {
		// create an admin user which will be "gabage collected" in tearDown()
		$this->_createInitialAdminUser();

		$authorizedAdmin = new couchAdmin($this->adminClient);

		// the database is needed for getSecurity(), which we want to test
		$this->adminClient->createDatabase();

		$secBeforeDbAdminCreation = $authorizedAdmin->getSecurity();
		$resultCreateDbAdmin = $authorizedAdmin->addDatabaseAdminUser("joe");
		$secAfterDbAdminCreation = $authorizedAdmin->getSecurity();
		$resultDeleteDbAdmin = $authorizedAdmin->removeDatabaseAdminUser("joe");

		//we don't need the database anymore
		$this->adminClient->deleteDatabase();

		// check everything went as expected
		$this->assertEquals(count($secBeforeDbAdminCreation->admins->names),0);
		$this->assertInternalType("boolean", $resultCreateDbAdmin);
		$this->assertEquals($resultCreateDbAdmin,true);
		$this->assertInternalType("boolean", $resultDeleteDbAdmin);
		$this->assertEquals($resultDeleteDbAdmin,true);
		$this->assertEquals(count($secAfterDbAdminCreation->admins->names),1);
		$this->assertEquals(reset($secAfterDbAdminCreation->admins->names),"joe");
	}

	/**
	 * @group couchAdmin
	 */
	public function testDatabaseReaderUser () {
		// create an admin user which will be "gabage collected" in tearDown()
		$this->_createInitialAdminUser();

		$authorizedAdmin = new couchAdmin($this->adminClient);

		// the database is needed for the test
		$this->adminClient->createDatabase();
		$resultCreateReader = $authorizedAdmin->addDatabaseReaderUser("jack");
		$securityAfterCreateReader = $authorizedAdmin->getSecurity();

		$resultDeleteReader = $authorizedAdmin->removeDatabaseReaderUser("jack");
		$securityAfterDeleteReader = $authorizedAdmin->getSecurity();

		//we don't need the database anymore
		$this->adminClient->deleteDatabase();

		//check if creation went well
		$this->assertInternalType("boolean", $resultCreateReader);
		$this->assertEquals($resultCreateReader,true);
		$this->assertEquals(count($securityAfterCreateReader->readers->names),1);
		$this->assertEquals(reset($securityAfterCreateReader->readers->names),"jack");

		//check if deleteion went well
		$this->assertInternalType("boolean", $resultDeleteReader);
		$this->assertEquals($resultDeleteReader,true);
		$this->assertEquals(count($securityAfterDeleteReader->readers->names),0);
	}

	/**
	 * @group couchAdmin
	 */
	public function testGetDatabaseAdminUsers () {
		// create an admin user which will be "gabage collected" in tearDown()
		$this->_createInitialAdminUser();

		// the database is needed for the test
		$this->adminClient->createDatabase();

		$authorizedAdmin = new couchAdmin($this->adminClient);

		$authorizedAdmin->addDatabaseAdminUser("joe");
		$adminUsers = $authorizedAdmin->getDatabaseAdminUsers();
		$authorizedAdmin->removeDatabaseAdminUser("joe");

		//we don't need the database anymore
		$this->adminClient->deleteDatabase();

		//check if getDatabaseAdminUsers() returns correct values
		$this->assertInternalType("array", $adminUsers);
		$this->assertEquals(1,count($adminUsers));
		$this->assertEquals("joe",reset($adminUsers));
	}

	/**
	 * @group couchAdmin
	 */
	public function testGetDatabaseReaderUsers () {
		// create an admin user which will be "gabage collected" in tearDown()
		$this->_createInitialAdminUser();

		// the database is needed for the test
		$this->adminClient->createDatabase();

		$authorizedAdmin = new couchAdmin($this->adminClient);

		$authorizedAdmin->addDatabaseReaderUser("jack");
		$readUser = $authorizedAdmin->getDatabaseReaderUsers();
		$authorizedAdmin->removeDatabaseReaderUser("jack");

		//we don't need the database anymore
		$this->adminClient->deleteDatabase();

		$this->assertInternalType("array", $readUser);
		$this->assertEquals(1,count($readUser));
		$this->assertEquals("jack",reset($readUser));
	}

	/**
	 * @group couchAdmin
	 * @group couchAdminRoles
	 */
	public function testDatabaseAdminRole () {
		// create an admin user which will be "gabage collected" in tearDown()
		$this->_createInitialAdminUser();

		$authorizedAdmin = new couchAdmin($this->adminClient);

		// the database is needed for the test
		$this->adminClient->createDatabase();

		$securityBeforeRole = $authorizedAdmin->getSecurity();
		$resultAddAdminRole = $authorizedAdmin->addDatabaseAdminRole("cowboy");
		$securityAfterAddAdminRole = $authorizedAdmin->getSecurity();
		$resultRemoveAdminRole = $authorizedAdmin->removeDatabaseAdminRole("cowboy");
		$securityAfterRemoveAdminRole = $authorizedAdmin->getSecurity();

		//we don't need the database anymore
		$this->adminClient->deleteDatabase();

		// make sure there are no roles at the beginning
		$this->assertEquals(count($securityBeforeRole->admins->roles),0);

		// check if role creation went well
		$this->assertInternalType("boolean", $resultAddAdminRole);
		$this->assertEquals($resultAddAdminRole,true);
		$this->assertEquals(count($securityAfterAddAdminRole->admins->roles),1);
		$this->assertEquals(reset($securityAfterAddAdminRole->admins->roles),"cowboy");

		// check if role deletion went well
		$this->assertInternalType("boolean", $resultRemoveAdminRole);
		$this->assertEquals($resultRemoveAdminRole,true);
		$this->assertEquals(count($securityAfterRemoveAdminRole->admins->roles),0);
	}

	/**
	 * @group couchAdmin
	 * @group couchAdminRoles
	 */
	public function testDatabaseReaderRole () {
		// create an admin user which will be "gabage collected" in tearDown()
		$this->_createInitialAdminUser();

		$authorizedAdmin = new couchAdmin($this->adminClient);

		// the database is needed for the test
		$this->adminClient->createDatabase();

		$securityBeforeRole = $authorizedAdmin->getSecurity();
		$resultAddReadRole = $authorizedAdmin->addDatabaseReaderRole("cowboy");
		$securityAfterAddReadRole = $authorizedAdmin->getSecurity();
		$resultRemoveReadRole = $authorizedAdmin->removeDatabaseReaderRole("cowboy");
		$securityAfterRemoveReadRole = $authorizedAdmin->getSecurity();

		//we don't need the database anymore
		$this->adminClient->deleteDatabase();

		// make sure there are no roles at the beginning
		$this->assertEquals(count($securityBeforeRole->readers->roles),0);

		// check if role creation went well
		$this->assertInternalType("boolean", $resultAddReadRole);
		$this->assertEquals($resultAddReadRole,true);
		$this->assertEquals(count($securityAfterAddReadRole->readers->roles),1);
		$this->assertEquals(reset($securityAfterAddReadRole->readers->roles),"cowboy");

		// check if role deletion went well
		$this->assertInternalType("boolean", $resultRemoveReadRole);
		$this->assertEquals($resultRemoveReadRole,true);
		$this->assertEquals(count($securityAfterRemoveReadRole->readers->roles),0);
	}

	/**
	 * @group couchAdmin
	 * @group couchAdminRoles
	 */
	public function testGetDatabaseAdminRoles () {
		// create an admin user which will be "gabage collected" in tearDown()
		$this->_createInitialAdminUser();

		$authorizedAdmin = new couchAdmin($this->adminClient);

		// the database is needed for the test
		$this->adminClient->createDatabase();

		$users = $authorizedAdmin->getDatabaseAdminRoles();

		//we don't need the database anymore
		$this->adminClient->deleteDatabase();

		$this->assertInternalType("array", $users);
		$this->assertEquals(0,count($users));
	}

	/**
	 * @group couchAdmin
	 * @group couchAdminRoles

	 */
	public function testGetDatabaseReaderRoles () {
		// create an admin user which will be "gabage collected" in tearDown()
		$this->_createInitialAdminUser();

		$authorizedAdmin = new couchAdmin($this->adminClient);

		// the database is needed for the test
		$this->adminClient->createDatabase();

		$users = $authorizedAdmin->getDatabaseReaderRoles();

		//we don't need the database anymore
		$this->adminClient->deleteDatabase();

		$this->assertInternalType("array", $users);
		$this->assertEquals(0,count($users));
	}

	/**
	 * @group couchAdmin
	 * @group couchAdminRoles
	 */
	public function testUserRoles () {
		// create an admin user which will be "gabage collected" in tearDown()
		$this->_createInitialAdminUser();

		$authorizedAdmin = new couchAdmin($this->adminClient);

		// the database is needed for the test
		$this->adminClient->createDatabase();

		//create user Joe
		$authorizedAdmin->createUser('joe', 'joes_password');
		$userJoeAtBeginning = $authorizedAdmin->getUser("joe");

		//add one role
		$oUser = clone $userJoeAtBeginning;
		$authorizedAdmin->addRoleToUser($oUser,"cowboy");
		$userJoeWithOneRole = $authorizedAdmin->getUser("joe");

		//add second role
		$authorizedAdmin->addRoleToUser("joe","trainstopper");
		$userJoeWithTwoRoles = $authorizedAdmin->getUser("joe");

		//remove first role again
		$oUser = clone $userJoeWithTwoRoles;
		$authorizedAdmin->removeRoleFromUser($oUser,"cowboy");
		$userJoeWithOneRoleLeft = $authorizedAdmin->getUser("joe");

		//remove second role (so no roles left)
		$authorizedAdmin->removeRoleFromUser("joe","trainstopper");
		$userJoeWithNoRoleLeft = $authorizedAdmin->getUser("joe");

		//remove user joe
		$authorizedAdmin->deleteUser('joe');

		//we don't need the database anymore
		$this->adminClient->deleteDatabase();

		// check joe after creation
		$this->assertInternalType("object", $userJoeAtBeginning);
		$this->assertObjectHasAttribute("_id",$userJoeAtBeginning);
		$this->assertObjectHasAttribute("roles",$userJoeAtBeginning);
		$this->assertInternalType("array", $userJoeAtBeginning->roles);
		$this->assertEquals(0,count($userJoeAtBeginning->roles));

		// check joe after first role added
		$this->assertInternalType("object", $userJoeWithOneRole);
		$this->assertObjectHasAttribute("_id",$userJoeWithOneRole);
		$this->assertObjectHasAttribute("roles",$userJoeWithOneRole);
		$this->assertInternalType("array", $userJoeWithOneRole->roles);
		$this->assertEquals(1,count($userJoeWithOneRole->roles));
		$this->assertEquals("cowboy",reset($userJoeWithOneRole->roles));

		// check joe after second role added
		$this->assertInternalType("object", $userJoeWithTwoRoles);
		$this->assertObjectHasAttribute("_id",$userJoeWithTwoRoles);
		$this->assertObjectHasAttribute("roles",$userJoeWithTwoRoles);
		$this->assertInternalType("array", $userJoeWithTwoRoles->roles);
		$this->assertEquals(2,count($userJoeWithTwoRoles->roles));
		$this->assertEquals("cowboy",reset($userJoeWithTwoRoles->roles));
		$this->assertEquals("trainstopper",end($userJoeWithTwoRoles->roles));

		// check joe after first role removed
		$this->assertInternalType("object", $userJoeWithOneRoleLeft);
		$this->assertObjectHasAttribute("_id",$userJoeWithOneRoleLeft);
		$this->assertObjectHasAttribute("roles",$userJoeWithOneRoleLeft);
		$this->assertInternalType("array", $userJoeWithOneRoleLeft->roles);
		$this->assertEquals(1,count($userJoeWithOneRoleLeft->roles));
		$this->assertEquals("trainstopper",reset($userJoeWithOneRoleLeft->roles));

		// check joe after second role removed
		$this->assertInternalType("object", $userJoeWithNoRoleLeft);
		$this->assertObjectHasAttribute("_id",$userJoeWithNoRoleLeft);
		$this->assertObjectHasAttribute("roles",$userJoeWithNoRoleLeft);
		$this->assertInternalType("array", $userJoeWithNoRoleLeft->roles);
		$this->assertEquals(0,count($userJoeWithNoRoleLeft->roles));

	}

	/**
	 * @group couchAdmin
	 */
	public function testDeleteUser() {
		$this->_createInitialAdminUser();

		// the database is needed for the test
		$this->adminClient->createDatabase();

		$authorizedAdmin = new couchAdmin($this->adminClient);

		$authorizedAdmin->createUser("joe","dalton");

		$resultDeleteUser = $authorizedAdmin->deleteUser("joe");
		$resultGetAllUsers = $authorizedAdmin->getAllUsers(true);

		//we don't need the database anymore
		$this->adminClient->deleteDatabase();


		$this->assertInternalType("object", $resultDeleteUser);
		$this->assertObjectHasAttribute("ok",$resultDeleteUser);
		$this->assertEquals($resultDeleteUser->ok,true);

		$this->assertInternalType("array", $resultGetAllUsers);
		$this->assertEquals(count($resultGetAllUsers),1);
	}

	/**
	 * @group couchAdmin
	 */
	public function testDeleteAdmin() {
		// create an admin user which will be "gabage collected" in tearDown()
		$this->_createInitialAdminUser();

		$authorizedAdmin = new couchAdmin($this->adminClient);
		$authorizedAdmin->createAdmin("secondAdmin","password");
		$authorizedAdmin->deleteAdmin("secondAdmin");
	}

	/**
	 * @group couchAdmin
	 */
	public function testUsersDatabaseName () {
		$adm = new couchAdmin($this->adminClient,array("users_database"=>"test"));
		$this->assertEquals("test",$adm->getUsersDatabase());
		$adm = new couchAdmin($this->adminClient);
		$this->assertEquals("_users",$adm->getUsersDatabase());
		$adm->setUsersDatabase("test");
		$this->assertEquals("test",$adm->getUsersDatabase());
	}

	/**
	 * removes the admin with the username $username from the database
	 * @param string $username
	 */
	private function _removeAdminUser($username)
	{
		$authorizedAdm = new couchAdmin($this->adminClient);
		$authorizedAdm->deleteAdmin($username);
	}

	/**
	 * this function creates the inital admin user in the CouchDB
	 */
	private function _createInitialAdminUser()
	{
		$adm = new couchAdmin($this->client);
		$adm->createAdmin($this->admin["login"], $this->admin["password"]);
	}

	/**
	 * this function creates the inital admin user in the CouchDB
	 */
	private function _removeInitialAdminUser()
	{
		$this->_removeAdminUser($this->admin["login"]);
	}
}
