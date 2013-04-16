<?php
	ini_set('display_errors', false);
	error_reporting(E_ERROR | E_RECOVERABLE_ERROR | E_WARNING | E_NOTICE);

	require_once('PHPUnit/Autoload.php');

	require_once (__DIR__ . "/CouchClientTestCase.php");
	require_once (__DIR__ . "/../lib/couch.php");
	require_once (__DIR__ . "/../lib/couchClient.php");
	require_once (__DIR__ . "/../lib/couchDocument.php");
	require_once (__DIR__ . "/../lib/couchAdmin.php");
	require_once (__DIR__ . "/../lib/couchReplicator.php");
