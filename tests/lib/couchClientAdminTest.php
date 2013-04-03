<?php

class couchClientAdminTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @var $couch_server string
	 */
	private $couch_server = "http://localhost:5984/";
	/**
	 * @var $admin array
	 */
	private $admin = array("login"=>"adm", "password"=>"sometest");
	/**
	 * @var $client couchClient
	 */
	private $client = null;
	/**
	 * @var $adminClient couchClient
	 */
	private $adminClient = null;


    public function setUp()
    {
		parent::setUp();

        $this->client = new couchClient($this->couch_server,"couchclienttest");
		$this->adminClient = new couchClient("http://".$this->admin["login"].":".$this->admin["password"]."@localhost:5984/","couchclienttest");
    }

	public function tearDown()
    {
		parent::tearDown();

        $this->client = null;
		$this->adminClient = null;
    }

	/**
	 * @group couchAdmin
	 */
	public function testFirstAdmin () {
		$adm = new couchAdmin($this->client);
		$adm->createAdmin($this->admin["login"],$this->admin["password"]);
	}

	/**
	 * @group couchAdmin
	 */
	public function testAdminIsSet () {
		$code = 0;
		try { $this->client->createDatabase("test"); }
		catch ( Exception $e ) { $code = $e->getCode(); }
		$this->assertEquals(302,$code);
	}

	/**
	 * @group couchAdmin
	 */
	public function testAdminCanAdmin () {
		$ok = $this->adminClient->createDatabase();
		$this->assertInternalType("object", $ok);
		$this->assertObjectHasAttribute("ok",$ok);
		$this->assertEquals($ok->ok,true);
		$ok = $this->adminClient->deleteDatabase();
		$this->assertInternalType("object", $ok);
		$this->assertObjectHasAttribute("ok",$ok);
		$this->assertEquals($ok->ok,true);
	}

	/**
	 * @group couchAdmin
	 */
	public function testUserAccount () {
		$adm = new couchAdmin($this->adminClient);
		$ok = $adm->createUser("joe","dalton");
		$this->assertInternalType("object", $ok);
		$this->assertObjectHasAttribute("ok",$ok);
		$this->assertEquals($ok->ok,true);
	}

	/**
	 * @group couchAdmin
	 */
	public function testAllUsers () {
		$adm = new couchAdmin($this->adminClient);
		$ok = $adm->getAllUsers(true);
		$this->assertInternalType("array", $ok);
		$this->assertEquals(count($ok),2);
	}

	/**
	 * @group couchAdmin
	 */
	public function testGetUser () {
		$adm = new couchAdmin($this->adminClient);
		$ok = $adm->getUser("joe");
		$this->assertInternalType("object", $ok);
		$this->assertObjectHasAttribute("_id",$ok);
	}

	/**
	 * @group couchAdmin
	 */
	public function testUserAccountWithRole () {
		$roles = array("badboys","jailbreakers");
		$adm = new couchAdmin($this->adminClient);
		$ok = $adm->createUser("jack","dalton",$roles);
		$this->assertInternalType("object", $ok);
		$this->assertObjectHasAttribute("ok",$ok);
		$this->assertEquals($ok->ok,true);
		$user = $adm->getUser("jack");
		$this->assertInternalType("object", $user);
		$this->assertObjectHasAttribute("_id",$user);
		$this->assertObjectHasAttribute("roles",$user);
		$this->assertInternalType("array", $user->roles);
		$this->assertEquals(count($user->roles),2);
		foreach ( $user->roles as $role ) {
			$this->assertEquals(in_array($role,$roles),true);
		}
	}

	/**
	 * @group couchAdmin
	 */
	public function testGetSecurity () {
		$this->adminClient->createDatabase();
		$adm = new couchAdmin($this->adminClient);
		$security = $adm->getSecurity();
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
		$adm = new couchAdmin($this->adminClient);
		$security = $adm->getSecurity();
		$security->admins->names[] = "joe";
		$security->readers->names[] = "jack";
		$ok = $adm->setSecurity($security);
		$this->assertInternalType("object", $ok);
		$this->assertObjectHasAttribute("ok",$ok);
		$this->assertEquals($ok->ok,true);

		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->names),1);
		$this->assertEquals(reset($security->readers->names),"jack");
		$this->assertEquals(count($security->admins->names),1);
		$this->assertEquals(reset($security->admins->names),"joe");
	}

	/**
	 * @group couchAdmin
	 */
	public function testDatabaseAdminUser () {
		$adm = new couchAdmin($this->adminClient);
		$ok = $adm->removeDatabaseAdminUser("joe");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->names),0);
		$ok = $adm->addDatabaseAdminUser("joe");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->names),1);
		$this->assertEquals(reset($security->admins->names),"joe");
	}

	/**
	 * @group couchAdmin
	 */
	public function testDatabaseReaderUser () {
		$adm = new couchAdmin($this->adminClient);
		$ok = $adm->removeDatabaseReaderUser("jack");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->names),0);
		$ok = $adm->addDatabaseReaderUser("jack");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->names),1);
		$this->assertEquals(reset($security->readers->names),"jack");
	}

	/**
	 * @group couchAdmin
	 */
	public function testGetDatabaseAdminUsers () {
		$adm = new couchAdmin($this->adminClient);
		$users = $adm->getDatabaseAdminUsers();
		$this->assertInternalType("array", $users);
		$this->assertEquals(1,count($users));
		$this->assertEquals("joe",reset($users));
	}

	/**
	 * @group couchAdmin
	 */
	public function testGetDatabaseReaderUsers () {
		$adm = new couchAdmin($this->adminClient);
		$users = $adm->getDatabaseReaderUsers();
		$this->assertInternalType("array", $users);
		$this->assertEquals(1,count($users));
		$this->assertEquals("jack",reset($users));
	}

	/**
	 * @group couchAdmin
	 * @group couchAdminRoles
	 */
	public function testDatabaseAdminRole () {
		$adm = new couchAdmin($this->adminClient);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->roles),0);
		$ok = $adm->addDatabaseAdminRole("cowboy");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->roles),1);
		$this->assertEquals(reset($security->admins->roles),"cowboy");
		$ok = $adm->removeDatabaseAdminRole("cowboy");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->admins->roles),0);
	}

	/**
	 * @group couchAdmin
	 * @group couchAdminRoles
	 */
	public function testDatabaseReaderRole () {
		$adm = new couchAdmin($this->adminClient);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->roles),0);
		$ok = $adm->addDatabaseReaderRole("cowboy");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->roles),1);
		$this->assertEquals(reset($security->readers->roles),"cowboy");
		$ok = $adm->removeDatabaseReaderRole("cowboy");
		$this->assertInternalType("boolean", $ok);
		$this->assertEquals($ok,true);
		$security = $adm->getSecurity();
		$this->assertEquals(count($security->readers->roles),0);
	}

	/**
	 * @group couchAdmin
	 * @group couchAdminRoles
	 */
	public function testGetDatabaseAdminRoles () {
		$adm = new couchAdmin($this->adminClient);
		$users = $adm->getDatabaseAdminRoles();
		$this->assertInternalType("array", $users);
		$this->assertEquals(0,count($users));
	}

	/**
	 * @group couchAdmin
	 * @group couchAdminRoles
	 */
	public function testGetDatabaseReaderRoles () {
		$adm = new couchAdmin($this->adminClient);
		$users = $adm->getDatabaseReaderRoles();
		$this->assertInternalType("array", $users);
		$this->assertEquals(0,count($users));
	}

	/**
	 * @group couchAdmin
	 * @group couchAdminRoles
	 */
	public function testUserRoles () {
		$adm = new couchAdmin($this->adminClient);
		$user = $adm->getUser("joe");
		$this->assertInternalType("object", $user);
		$this->assertObjectHasAttribute("_id",$user);
		$this->assertObjectHasAttribute("roles",$user);
		$this->assertInternalType("array", $user->roles);
		$this->assertEquals(0,count($user->roles));
		$adm->addRoleToUser($user,"cowboy");
		$user = $adm->getUser("joe");
		$this->assertInternalType("object", $user);
		$this->assertObjectHasAttribute("_id",$user);
		$this->assertObjectHasAttribute("roles",$user);
		$this->assertInternalType("array", $user->roles);
		$this->assertEquals(1,count($user->roles));
		$this->assertEquals("cowboy",reset($user->roles));
		$adm->addRoleToUser("joe","trainstopper");
		$user = $adm->getUser("joe");
		$this->assertInternalType("object", $user);
		$this->assertObjectHasAttribute("_id",$user);
		$this->assertObjectHasAttribute("roles",$user);
		$this->assertInternalType("array", $user->roles);
		$this->assertEquals(2,count($user->roles));
		$this->assertEquals("cowboy",reset($user->roles));
		$this->assertEquals("trainstopper",end($user->roles));
		$adm->removeRoleFromUser($user,"cowboy");
		$user = $adm->getUser("joe");
		$this->assertInternalType("object", $user);
		$this->assertObjectHasAttribute("_id",$user);
		$this->assertObjectHasAttribute("roles",$user);
		$this->assertInternalType("array", $user->roles);
		$this->assertEquals(1,count($user->roles));
		$this->assertEquals("trainstopper",reset($user->roles));
		$adm->removeRoleFromUser("joe","trainstopper");
		$user = $adm->getUser("joe");
		$this->assertInternalType("object", $user);
		$this->assertObjectHasAttribute("_id",$user);
		$this->assertObjectHasAttribute("roles",$user);
		$this->assertInternalType("array", $user->roles);
		$this->assertEquals(0,count($user->roles));
	}

	/**
	 * @group couchAdmin
	 */
	public function testDeleteUser() {
		$adm = new couchAdmin($this->adminClient);
		$ok = $adm->deleteUser("joe");
		$this->assertInternalType("object", $ok);
		$this->assertObjectHasAttribute("ok",$ok);
		$this->assertEquals($ok->ok,true);
		$ok = $adm->getAllUsers(true);
		$this->assertInternalType("array", $ok);
		$this->assertEquals(count($ok),2);
	}

	/**
	 * @group couchAdmin
	 */
	public function testDeleteAdmin() {
		$adm = new couchAdmin($this->adminClient);
		$adm->createAdmin("secondAdmin","password");
		$adm->deleteAdmin("secondAdmin");
		$adm->createAdmin("secondAdmin","password");
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
}
