<?php

class couchClientTestUpdateDocTest extends CouchClientTestCase
{
	/**
	 * @var $updateFn string
	 */
	private $updateFn = <<<EOT
function(doc,req) {
	var resp = {query:null,form:null};
	if ( "query" in req ) {
		resp.query = req.query;
	}
	if ( "form" in req ) {
		resp.form = req.form;
	}
	return [doc,{
			headers: {"Content-Type": "application/json"},
			body: JSON.stringify(resp)
		}];
}
EOT
	;

	public function setUp()
	{
		parent::setUp();
		try {
			$this->client->deleteDatabase();
		} catch ( Exception $e) {}
		$this->client->createDatabase();

		$ddoc = new stdClass();
		$ddoc->_id = "_design/test";
		$ddoc->updates = array("test" => $this->updateFn);
		$this->client->storeDoc($ddoc);
		$doc = new stdClass();
		$doc->_id = "foo";
		$this->client->storeDoc($doc);
	}

	/**
	 * @group couchClientUpdateDoc
	 */
	public function testUpdate () {
		$update = $this->client->updateDoc("test","test",array());
		$this->assertInternalType("object", $update);
		$this->assertObjectHasAttribute("query",$update);
		$this->assertInternalType("object", $update->query);
		$this->assertEquals(0, count((array)$update->query));
		$this->assertObjectHasAttribute("form",$update);
		$this->assertInternalType("object", $update->form);
		$this->assertEquals(0, count((array)$update->form));

	}

	/**
	 * @group couchClientUpdateDoc
	 */
	public function testUpdateQuery () {
		$update = $this->client->updateDoc("test","test",array("var1"=>"val1/?\"","var2"=>"val2"));
		$this->assertInternalType("object", $update);
		$this->assertObjectHasAttribute("query",$update);
		$this->assertInternalType("object", $update->query);
		$this->assertEquals(2, count((array)$update->query));
		$this->assertObjectHasAttribute("var1",$update->query);
		$this->assertInternalType("string", $update->query->var1);
		$this->assertEquals("val1/?\"", $update->query->var1);

		$this->assertObjectHasAttribute("form",$update);
		$this->assertInternalType("object", $update->form);
		$this->assertEquals(0, count((array)$update->form));
	}

	/**
	 * @group couchClientUpdateDoc
	 */
	public function testUpdateForm () {
		$update = $this->client->updateDocFullAPI("test","test",array(
			"data"=> array("var1"=>"val1/?\"","var2"=>"val2")
		));
		$this->assertInternalType("object", $update);
		$this->assertObjectHasAttribute("query",$update);
		$this->assertInternalType("object", $update->query);
		$this->assertEquals(0, count((array)$update->query));
		$this->assertObjectHasAttribute("form",$update);
		$this->assertInternalType("object", $update->form);
		$this->assertEquals(2, count((array)$update->form));
		$this->assertObjectHasAttribute("var1",$update->form);
		$this->assertInternalType("string", $update->form->var1);
		$this->assertEquals("val1/?\"", $update->form->var1);
	}
}
