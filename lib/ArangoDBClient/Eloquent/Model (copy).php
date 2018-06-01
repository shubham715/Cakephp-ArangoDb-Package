<?php

namespace ArangoDbClient\Eloquent;
use ArangoDBClient\Connect;

abstract class Model
{

     public function __construct(array $config = [])
    {
        if (!empty($config['table'])) {
            $this->setTable($config['table']);
        }
        $this->_arangoConnect = Connect::getConnection([]);
        $this->_arangoHandler = Connect::getDocumentHandler($this->_arangoConnect);
        $this->_collectionHandler = Connect::collectionHandler($this->_arangoConnect);
        $this->initialize($config);
    }

    public function initialize(array $config)
    {
    }


     /**
     * Sets the database table name.
     *
     * @param string $table Table name.
     * @return $this
     */
    public function setTable($table)
    {
        $this->_table = $table;

        return $this;
    }

    /**
     * Get the default connection name.
     *
     * This method is used to get the fallback connection name if an
     * instance is created through the TableLocator without a connection.
     *
     * @return string
     * @see \Cake\ORM\Locator\TableLocator::get()
     */
    public static function defaultConnectionName()
    {
        return 'default';
    }

    /**
     * Convert array to arango db object.
     */
    protected function _processValues($entity, array $options = [])
    {
        $obData = Connect::newArangoDocument();
        foreach ($entity as $key => $value) {
           $obData->set($key, $value);
        }
        return $obData;
    }

    public function sort()
    {
        $q = 'FOR u IN users SORT u.age DESC RETURN u';
        $result = $this->customQuery($q);
              echo '<pre>'; print_r($result); echo '</pre>'; exit;
              
    }

    public function updateQuery()
    {
         $q = "FOR u IN users
              FILTER u.age == 10
              UPDATE u WITH { age: 50 } IN users";
        $result = $this->customQuery($q);
              echo '<pre>'; print_r($result); echo '</pre>'; exit;
    }

    public function save(array $data, $options = [])
    {   
        $data = $this->_processValues($data); 
        $saveDocument =  $this->_arangoHandler->save($this->_table, $data);
        return $saveDocument;
    }

     /**
     * Persists multiple entities of a table.
     *
     * The records will be saved in a transaction which will be rolled back if
     * any one of the records fails to save due to failed validation or database
     * error.
     *
     * @param \Cake\Datasource\EntityInterface[]|\Cake\ORM\ResultSet $entities Entities to save.
     * @param array|\ArrayAccess $options Options used when calling Table::save() for each entity.
     * @return bool|\Cake\Datasource\EntityInterface[]|\Cake\ORM\ResultSet False on failure, entities list on success.
     */
  /*  public function saveMany($entities, $options = [])
    {
        $isNew = [];

        $return = $this->getConnection()->transactional(
            function () use ($entities, $options, &$isNew) {
                foreach ($entities as $key => $entity) {
                    $isNew[$key] = $entity->isNew();
                    if ($this->save($entity, $options) === false) {
                        return false;
                    }
                }
            }
        );

        if ($return === false) {
            foreach ($entities as $key => $entity) {
                if (isset($isNew[$key]) && $isNew[$key]) {
                    $entity->unsetProperty($this->getPrimaryKey());
                    $entity->isNew(true);
                }
            }

            return false;
        }

        return $entities;
    }*/

    public function findById($entity, $options = [])
    { 
       $id = isset($entity['id'])?$entity['id']:$entity; //pass array with "id" key or pass key directly
       if($id!="") {   
        $result = $this->_arangoHandler->get($this->_table, $entity['id']);
        return $result;
       } else {
         return false;
       }
    }

     public function get($primaryKey, $options = [])
    {
        $result = $this->_arangoHandler->get($this->_table, $primaryKey);
        if($result) {
            return true;
        }
        return false;
    }

    public function findList($type = 'all',array $conditions=[])
    {
        $list_key = ($conditions['key'])?$conditions['key']:"";
        $list_value = ($conditions['value'])?$conditions['value']:"";
        $cursor = $this->_collectionHandler->byExample($this->_table, $conditions);
        $result = $cursor->getAll();
        $result = [];
        if(count($result) > 0) {
            foreach ($result as $key => $val) {
               $result[$val[$list_key]] = $result[$val[$list_value]];
            }
        }
        return $result;
    }

    public function find($type = 'all', $options = [])
    {
        if($type=="list") {
            $list_key = ($conditions['key'])?$conditions['key']:"";
            $list_value = ($conditions['value'])?$conditions['value']:"";
            $cursor = $this->_collectionHandler->byExample($this->_table, $conditions);
            $result = $cursor->getAll();
            $result = [];
         if(count($result) > 0) {
            foreach ($result as $key => $val) {
               $result[$val[$list_key]] = $result[$val[$list_value]];
            }
         }   
        } else {
         $conditions = isset($options['conditions'])?$options['conditions']:"";
         $cursor = $this->_collectionHandler->byExample($this->_table, $conditions);
         $result = $cursor->getAll();

        }
        return $result;
    }

    public function findOne($type = 'all', $options = [])
    {
        $conditions = isset($options['conditions'])?$options['conditions']:"";
        $cursor = $this->_collectionHandler->byExample($this->_table, $conditions);
        $result = $cursor->getAll();
        return ($type=="first" && isset($result[0]))?$result[0]:$result;
    }

    /**
     * Delete Record
     */
    public function delete($entity, $options = [])
    {   
        if($entity['id']!="") {   
           $userFromServer = $this->findById($entity['id']); 
            if($userFromServer) {
            $result = $this->_arangoHandler->remove($userFromServer);
             return $result;
            }
        }
        return false;
    }

    public function deleteById($entity, $options = [])
    {  
        try {
          $result = $this->_arangoHandler->removeById($this->_table, $entity);
        } catch (\ArangoDBClient\ServerException $e) {
            $e->getMessage();
        }
    }
    /*
     *input: array of id's 
     *ouput: delete all matched id records
    **/
    public function deleteAllById(array $entity, $options = [])
    {  
        try {
          return $result = $this->_arangoHandler->removeByKeys($this->_table, $entity);
        } catch (\ArangoDBClient\ServerException $e) {
            $e->getMessage();
        }
        return false;
    }

    /**
     * {@inheritDoc}
     */
    /*public function deleteAll($entity, $options = [])
    {
        if($entity['id']!="") {   
           $userFromServer = $this->findById($entity['id']); 
            if($userFromServer) {
            $result = $this->_arangoHandler->remove($userFromServer);
             return $result;
            }
        }
        return false;
    }*/

    /**
     * {@inheritDoc}
     */
    public function updateAll($fields, $conditions)
    {
        /*$query = $this->query();
        $query->update()
            ->set($fields)
            ->where($conditions);
        $statement = $query->execute();
        $statement->closeCursor();
tchEntity(array $entity, array $data, array $options = [])
        return $statement->rowCount();*/
        $getdata = $handler->get('users', $id);

        return $result = $this->_arangoHandler->update($data);
    
    }

    /*public function findOrCreate($search, callable $callback = null, $options = [])
    {
        $options += [
            'atomic' => true,
            'defaults' => true
        ];

        return $this->_executeTransaction(function () use ($search, $callback, $options) {
            return $this->_processFindOrCreate($search, $callback, $options);
        }, $options['atomic']);
    }*/

   

    public function isExist($id)
    {
        $result = $this->_arangoHandler->has($this->_table, $id);
        if($result) {
           return true;
        }
        return false;
    }

    /**
     * Returns the query as passed.
     *
     * By default findAll() applies no conditions, you
     * can override this method in subclasses to modify how `find('all')` works.
     *
     * @param \Cake\ORM\Query $query The query to find with
     * @param array $options The options to use for the find
     * @return \Cake\ORM\Query The query builder
     */
    /* 
    public function findAll(Query $query, array $options)
    {
        return $query;
    } 
    */

    public function customQuery($query) {
        // create a statement to insert 1000 test users
        $statement = Connect::ArangoStatementHandler(
        $this->_arangoConnect, $query
        );
        // execute the statement
        $cursor = $statement->execute();
        return $cursor = $cursor->getAll();
    }


    /**
     * Returns the class used to hydrate rows for this table.
     *
     * @return string
     */
    /* public function getEntityClass()
    {
        if (!$this->_entityClass) {
            $default = Entity::class;
            $self = get_called_class();
            $parts = explode('\\', $self);

            if ($self === __CLASS__ || count($parts) < 3) {
                return $this->_entityClass = $default;
            }

            $alias = Inflector::singularize(substr(array_pop($parts), 0, -5));
            $name = implode('\\', array_slice($parts, 0, -1)) . '\\Entity\\' . $alias;
            if (!class_exists($name)) {
                return $this->_entityClass = $default;
            }

            $class = App::className($name, 'Model/Entity');
            if (!$class) {
                throw new MissingEntityException([$name]);
            }

            $this->_entityClass = $class;
        }

        return $this->_entityClass;
    }*/

    /**
     * {@inheritDoc}
     *
     * By default all the associations on this table will be hydrated. You can
     * limit which associations are built, or include deeper associations
     * using the options parameter:
     *
     * ```
     * $article = $this->Articles->newEntity(
     *   $this->request->getData(),
     *   ['associated' => ['Tags', 'Comments.Users']]
     * );
     * ```
     *
     * You can limit fields that will be present in the constructed entity by
     * passing the `fieldList` option, which is also accepted for associations:
     *
     * ```
     * $article = $this->Articles->newEntity($this->request->getData(), [
     *  'fieldList' => ['title', 'body', 'tags', 'comments']
     * ]
     * );
     * ```
     *
     * The `fieldList` option lets remove or restrict input data from ending up in
     * the entity. If you'd like to relax the entity's default accessible fields,
     * you can use the `accessibleFields` option:
     *
     */
    public function newEntity($data = null, array $options = [])
    {
        //currently we dont creating entity so here we only using array.
        $obData = Connect::newArangoDocument();
        return $obData;
    }


    /**
     * this function will return merge data of entity and post data
     */
    public function patchEntity(array $entity, array $data, array $options = [])
    {
       $mergeData = $entity; 
       foreach ($data as $key => $value) { 
         if($key!="id" || $key!="_id" || $key!="_key") {         
           $mergeData->$key = $value;
         }
       }
      return $mergeData; 
    }


}
