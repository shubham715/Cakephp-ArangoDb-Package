<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace ArangoDBClient\Query;


use Cake\Datasource\QueryInterface;
use Cake\Datasource\QueryTrait;



class Query extends DatabaseQuery implements JsonSerializable, QueryInterface
{

    protected $_arangoQuery;
    

    /**
     * Constructor
     *
     * @param \Cake\Database\Connection $connection The connection object
     * @param \Cake\ORM\Table $table The table this query is starting on
     */
    public function __construct($connection, $table)
    {
        parent::__construct($connection);
        $this->repository($table);

        if ($this->_repository) {
            $this->addDefaultTypes($this->_repository);
        }
    }


    /**
     * find documents
     *
     * @param string $type
     * @param array $options
     * @return \Cake\ORM\Entity|\Cake\ORM\Entity[]|MongoQuery
     * @access public
     * @throws \Exception
     */
    public function save($type = 'all', $options = [])
    {
        $query = new MongoFinder($this->__getCollection(), $options);
        $method = 'find' . ucfirst($type);
        //$user =  Connect::newArangoDocument();  
        $user->set('username', 'John');
        $user->set('age', 25);
        $user->set('thisIsNull', null); 
        $saveDocument = $arangoHandler->save('users', $user);
        echo $saveDocument; exit;
    }
}
