<?php

/**
 * ArangoDB PHP client: single user document
 *
 * @package   ArangoDBClient
 * @author    Frank Mayer
 * @since     1.2
 */

namespace ArangoDBClient;

use ArangoDBClient\Collection as ArangoCollection;
use ArangoDBClient\CollectionHandler as ArangoCollectionHandler;
use ArangoDBClient\Connection as ArangoConnection;
use ArangoDBClient\ConnectionOptions as ArangoConnectionOptions;
use ArangoDBClient\DocumentHandler as ArangoDocumentHandler;
use ArangoDBClient\Document as ArangoDocument;
use ArangoDBClient\Exception as ArangoException;
use ArangoDBClient\Export as ArangoExport;
use ArangoDBClient\ConnectException as ArangoConnectException;
use ArangoDBClient\ClientException as ArangoClientException;
use ArangoDBClient\ServerException as ArangoServerException;
use ArangoDBClient\Statement as ArangoStatement;
use ArangoDBClient\UpdatePolicy as ArangoUpdatePolicy;

class Connect {

	/**
     * Connection options
     *
     * @var array
     */
   static private $_options  = [
	    // database name
	    ArangoConnectionOptions::OPTION_DATABASE => '_system',
	    // server endpoint to connect to
	    ArangoConnectionOptions::OPTION_ENDPOINT => 'tcp://127.0.0.1:8529',
	    // authorization type to use (currently supported: 'Basic')
	    ArangoConnectionOptions::OPTION_AUTH_TYPE => 'Basic',
	    // user for basic authorization
	    ArangoConnectionOptions::OPTION_AUTH_USER => 'root',
	    // password for basic authorization
	    ArangoConnectionOptions::OPTION_AUTH_PASSWD => '',
	    // connection persistence on server. can use either 'Close' (one-time connections) or 'Keep-Alive' (re-used connections)
	    ArangoConnectionOptions::OPTION_CONNECTION => 'Keep-Alive',
	    // connect timeout in seconds
	    ArangoConnectionOptions::OPTION_TIMEOUT => 3,
	    // whether or not to reconnect when a keep-alive connection has timed out on server
	    ArangoConnectionOptions::OPTION_RECONNECT => true,
	    // optionally create new collections when inserting documents
	    ArangoConnectionOptions::OPTION_CREATE => true,
	    // optionally create new collections when inserting documents
	    ArangoConnectionOptions::OPTION_UPDATE_POLICY => ArangoUpdatePolicy::LAST,
	];

	static private $_connection;


	public static function getConnection(array $options)
    {
    	self::$_options = array_merge(self::$_options, $options);
        ArangoException::enableLogging();
    	return self::$_connection = new ArangoConnection(self::$_options);
    	//return    $collectionHandler = new ArangoCollectionHandler($connection);
    }

  /*  public static function collectionHandler(array $options, $collectionName='')
    {
    	$collectionHandler =  new ArangoCollectionHandler(getConnection());
    	$collectionObject = new ArangoCollection();
	    $collectionObject->setName($collectionName);
	    return $collectionArango = $collectionHandler->create($collectionObject);
    }*/

    public static function collectionHandler($connection) {
        return $collectionHandler = new ArangoCollectionHandler($connection);
    }

    public static function getDocumentHandler($connection) {
    	return $handler = new ArangoDocumentHandler($connection);
    }

    public static function ArangoStatementHandler($connection, $query) {
    	return $handler = new ArangoStatement($connection, ['query' => $query, "options"=>["fullCount"=> true ]]);
    }

    public static function ArangoBindStatementHandler($connection, $query) {
      return $handler = new ArangoStatement($connection, $query);
    }

    public static function newArangoDocument() {
    	//$documentHandler =  new ArangoDocumentHandler(getConnection());
    	return $documentObject = new ArangoDocument();
    }

   /* $handler = new ArangoDocumentHandler($connection);

    // create a new document
    $user = new ArangoDocument();

    // use set method to set document properties
    $user->set('name', 'John');
    $user->set('age', 25);
    $user->set('thisIsNull', null);*/

    //  ArangoCollectionHandler
   //  
  //  ArangoDocumentHandler
}

