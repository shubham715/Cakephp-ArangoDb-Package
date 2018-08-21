<?php

namespace ArangoDbClient\Eloquent;
use ArangoDBClient\Connect;
use Cake\Auth\DefaultPasswordHasher;

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

    public function identify($data)
    {
        if(isset($data['email']) && isset($data['password']) && $data['email']!="" && $data['password']!="") {
            $getUser = $this->findOne('all', ['conditions'=>['email'=> $data['email']], 1]);
            if($getUser) {      
                  
              if ((new DefaultPasswordHasher)->check($data['password'], $getUser['password'])) { 
                return $getUser;
              }      
            } 
        }    
        return false;    
    }

    public function save(array $data=[], $isResponseArray='')
    {
        //check implemented Events 
        $data = $this->implementedEvents($data);
        $data = json_encode($data);
        $q = 'INSERT '.$data.' INTO '.$this->_table.' LET result = NEW RETURN result';
        $result = ($isResponseArray)?$this->customQueryJson($q):$this->customQuery($q);             
        return (isset($result[0]))?(($isResponseArray)?$result[0]:$result[0]->getAll()):false;         
    }


    public function saveMany(array $data=[])
    {
        //check implemented Events 
        //$data = $this->implementedEvents($data);
        $data = json_encode($data);    
              
        $q = 'FOR d IN '.$data.' INSERT d INTO  '.$this->_table; // LET result = NEW RETURN result';
        $result = $this->customQuery($q);             
        return $result;        
    }

    public function implementedEvents(array $data =[])
    {
      if(method_exists($this, 'beforeSave')) {
        $data = $this->beforeSave($data);              
      } 
      return $data;
    }

    public function get($primaryKey, $options = [])
    {
        $result = $this->_arangoHandler->get($this->_table, $primaryKey);
        if($result) {
            return $result;
        }
        return false;
    }

    public function returnData($selectArray = [])
    {
        $str = '';
        if(count($selectArray) > 0) {
         $str = "RETURN {";
         foreach ($selectArray as $key => $value) {
          if(is_numeric($key)) {
            $str .= $value.": c.".$value.", ";
          } else {
            $str .= (strpos($value,'DATE') > -1)?($key.": ".$value.", "):($key.": c.".$value.", ");
          }
         }
         $str = trim($str, ", ");
         $str .= "}";
        }
        return $str;
    }

    public function orderbyData($arr = [])
    {
        $str = "";
        foreach ($arr as $key => $value) {
          $str = "SORT c.".$key." ".$value;
        }
        return $str;
    }

    public function limitData($str = '')
    {
        $str = isset($str['offset'])?$str['offset'].",".$str['count']:$str;
        $str = "LIMIT ".$str;
        return $str;
    }

    public function isUnique($options = [])
    {
        $str_condition = isset($options['conditions'])?$this->conditionProcess($options['conditions']):"";
        $str_limit = "LIMIT 1";
        $q = "FOR c IN ".$this->_table." ".$str_condition." ".$str_limit. " COLLECT id = c._key WITH COUNT INTO number RETURN { id: id , count: number}";
        $result = $this->customQuery($q);              
        return isset($result[0])?$result[0]:false;  
    }

    public function count($options = []) {
      $str_condition = isset($options['conditions'])?$this->conditionProcess($options['conditions']):"";
  
      $q = "FOR c IN ".$this->_table." ".$str_condition." COLLECT WITH COUNT INTO length RETURN length";

      $result = $this->customQuery($q);
      return (isset($result[0]))?$result[0]:false;       
    }

    /*
    * Ref Query = FOR u IN common_settings FOR c IN currency FILTER u.airdrop_currency == c.symbol RETURN { commonsettings: u, currency: c } 
    *Example -  $getCommonSettings = $this->CommonSettings->findWithJoin('all',['contain'=> 'currency', 'conditions'=>['c.currency_id'=> 'u.id']]);
    */
    public function findWithJoin($type = 'all', $options = [])
    {      
      $q_join = '';
      if(isset($options['contain'])) {
        $q_join = 'FOR u in '.strtolower($options['contain']).' ';
      }
      $str_condition = isset($options['conditions'])?$this->conditionProcess($options['conditions']):"";
      $str_returndata = isset($options['select'])?$this->returnData($options['select']):"RETURN { main: c, contain: u }";
      $str_sort = isset($options['order'])?$this->orderbyData($options['order']):"";
      $str_limit = isset($options['limit'])?$this->limitData($options['limit']):"";

      $q = "FOR c IN ".$this->_table." ".$q_join."".$str_condition." ".$str_sort." ".$str_limit." ".$str_returndata; 

      $ispaginate = isset($options['paginate'])?true:false;
      $response = $this->customQuery($q, $ispaginate);                
      return $response;         
    }

    public function find($type = 'all', $options = [], $isResponseArray=false)
    {
      if($type=="list") {
          $options['select'] = [$options['key'], $options['value']];
      }    
      $str_condition = isset($options['conditions'])?$this->conditionProcess($options['conditions']):"";
      $str_returndata = isset($options['select'])?$this->returnData($options['select']):"RETURN c";
      $str_sort = isset($options['order'])?$this->orderbyData($options['order']):"";
      $str_limit = isset($options['limit'])?$this->limitData($options['limit']):"";

      $q = "FOR c IN ".$this->_table." ".$str_condition." ".$str_sort." ".$str_limit." ".$str_returndata;           
      
      if($type=="list") {
        $result = $this->customQuery($q);
        $response = [];              
        if(count($result) > 0) {
            foreach ($result as $key => $val) {                     
               $response[$val->{$options['key']}] = $val->{$options['value']};
            }
          } 
      } else { 
        $ispaginate = isset($options['paginate'])?true:false;
        $response = ($isResponseArray==true)?$this->customQueryJson($q, $ispaginate):$this->customQuery($q, $ispaginate);               
      }  
      return $response;         
    }

    public function findById($id='', $isResponseArray=false)
    {
       if($id!="") {
        $q = 'RETURN DOCUMENT("'.$this->_table.'", "'.$id.'")';
        $result = ($isResponseArray==true)?$this->customQueryJson($q):$this->customQuery($q);               
        return (isset($result[0]))?(($isResponseArray)?$result[0]:$result[0]->getAll()):false; 
       }
    } 

    public function findOne($type = 'all', $options = [], $isResponseArray=false)
    {
        $str_condition = isset($options['conditions'])?$this->conditionProcess($options['conditions']):"";
        $str_returndata = isset($options['select'])?$this->returnData($options['select']):"RETURN c";
        $str_sort = isset($options['order'])?$this->orderbyData($options['order']):"";
        $str_limit = "LIMIT 1";

        $q = "FOR c IN ".$this->_table." ".$str_condition." ".$str_sort." ".$str_limit." ".$str_returndata; 
        //$result = $this->customQuery($q, $bindParams=[]);
        $response = ($isResponseArray==true)?$this->customQueryJson($q):$this->customQuery($q);
        if($isResponseArray!=true) {
          if(isset($options['responseTypeArray'])) {
            if(isset($result[0])) {  
              $result = $result[0];            
              $response = $result->getAll();   
              $response['id'] = $response['_key'];               
              return $response;
            }
          } else {
            return isset($response[0])?$response[0]->getAll():false;   
          } 
        } else {
                
          return isset($response[0])?$response[0]:false;   
        }
        return false;         
    }

    public function createOrUpdate(array $data = [])
    {
       if(isset($data['id']) && $data['id']!="") {             
         $result = $this->updateById($data, $data['id']);
       } else {
         if(isset($data['id'])) { unset($data['id']); }
         $result = $this->save($data);
       }
       return isset($result)?$result:false;
    }

    public function updateById($data=[], $id='')
    {
        $data = $this->implementedEvents($data);
        if(isset($data['id'])) { unset($data['id']); }
        $data = json_encode($data);               
        $q = 'UPDATE "'.$id.'" WITH '.$data.' IN '.$this->_table;
        $result = $this->customQuery($q);
        return true;
    }

    public function updateAll($data=[], $conditions=[])
    {  
       $data = json_encode($data);       
       $str_condition = isset($conditions)?$this->conditionProcess($conditions):"";
       $q = "FOR c in ".$this->_table." ".$str_condition." UPDATE c WITH ".$data." IN ".$this->_table.""; 
       $result = $this->customQuery($q);
       return true;
       //$q = "FOR u IN users FILTER u.age == 10 UPDATE u WITH { age: 50 } IN users";  
    }

    /*
     * Delete records based on conditions 
     *  Ref:- "FOR c IN @@collection FILTER x.d == 'qux' REMOVE x IN @@collection RETURN OLD";
     * delete and deleteAll Both are same function
    */    
    public function deleteAll($conditions=[])
    {  
       //Ref:- FOR u IN users FILTER u.active == false REMOVE { _key: u._key } IN backup
       //$data = json_encode($data);       
      /*$str_condition = isset($conditions)?$this->conditionProcess($conditions):""; 
      $q = "FOR c IN ".$this->_table." ".$str_condition." REMOVE { _key: c._key } IN ".$this->_table."";     
      $result = $this->customQuery($q);
      return $result; */ 
        $conditionsArray = isset($conditions)?$this->bindParamsConditionProcess($conditions):""; 
        $str_condition = isset($conditionsArray[0])?$conditionsArray[0]:"";
        $vals =isset($conditionsArray[1])?$conditionsArray[1]:""; 
        $vals['@collection'] = $this->_table;
        $q = 'FOR c IN @@collection '.$str_condition.' REMOVE c IN @@collection RETURN OLD';  
        //exit;
         $response = ['query'=> $q, 'bindVars'=> $vals];
         $result = $this->customQueryWithParams($response);
         return $result;

    }

    public function deleteById($id)
    {  
      if($id!="") {
        $q = 'REMOVE  "'.$id.'" IN "'.$this->_table.'" RETURN OLD';
        $result = $this->customQuery($q);        
        return $result;
       } 
      //Ref - REMOVE "1234565" IN Characters
    }  

    /*
     * Delete records based on conditions 
     *  Ref:- "FOR c IN @@collection FILTER x.d == 'qux' REMOVE x IN @@collection RETURN OLD";
    */    
    public function delete($conditions=[])
    {
        $conditionsArray = isset($conditions)?$this->bindParamsConditionProcess($conditions):""; 
        $str_condition = isset($conditionsArray[0])?$conditionsArray[0]:"";
        $vals =isset($conditionsArray[1])?$conditionsArray[1]:""; 
        $vals['@collection'] = $this->_table;
        $q = 'FOR c IN @@collection '.$str_condition.' REMOVE c IN @@collection RETURN OLD';  
        //exit;
         $response = ['query'=> $q, 'bindVars'=> $vals];
         $result = $this->customQueryWithParams($response);
         return $result; 
    }
    
    public function isExist($id)
    {
        $str_condition = isset($options['conditions'])?$this->conditionProcess($options['conditions']):"";
        $str_returndata = isset($options['select'])?$this->returnData($options['select']):"RETURN c";
        $str_sort = isset($options['order'])?$this->orderbyData($options['order']):"";
        $str_limit = "LIMIT 1";

        $q = "FOR c IN ".$this->_table." ".$str_condition." ".$str_sort." ".$str_limit." ".$str_returndata;
        $result = $this->customQuery($q);
        return isset($result[0])?$result[0]:false;
    }

    public function customQuery($query, $paginate='') {
        // create a statement to insert 1000 test users
        $statement = Connect::ArangoStatementHandler(
        $this->_arangoConnect, $query
        );
        // execute the statement
        $cursor = $statement->execute();
        if($paginate==true) {
          $totalPages = isset($cursor->getExtra()['stats']['scannedFull'])?$cursor->getExtra()['stats']['scannedFull']:""; 
          return $result = ['data'=> $cursor->getAll(), 'total'=> $totalPages];          
        }     
        return $cursor = $cursor->getAll();
    }
     
    public function customQueryJson($query, $paginate='') {
        // create a statement to insert 1000 test users
        $statement = Connect::ArangoStatementHandlerArray(
        $this->_arangoConnect, $query
        );
        // execute the statement
        $cursor = $statement->execute();     
        return $cursor = $cursor->getAll();
    } 

    public function customQueryWithParams($query) {
        // create a statement to insert 1000 test users
        $query["_flat"] = true;
        $statement = Connect::ArangoBindStatementHandler(
        $this->_arangoConnect, $query
        );
              
        // execute the statement
        $cursor = $statement->execute();
        $cursor = $cursor->getAll();
        return isset($cursor[0])?$cursor[0]:[];
    }

    public function customQueryAllWithParams($query) {
        // create a statement to insert 1000 test users
        $query["_flat"] = true;
        $statement = Connect::ArangoBindStatementHandler(
        $this->_arangoConnect, $query
        );
              
        // execute the statement
        $cursor = $statement->execute();
        $cursor = $cursor->getAll();
        return $cursor;
    }

    public function newEntity($data = null, array $options = [])
    {
        //currently we dont creating entity so here we only using array.
        $obData = Connect::newArangoDocument();
        return $obData;
    }


    /**
     * this function will return merge data of entity and post data
     */
    public function patchEntity($entity, array $data, array $options = [])
    {
      $mergeData = $entity; 
      foreach ($data as $key => $value) { 
        if($key!="id" || $key!="_id" || $key!="_key") {         
          $mergeData[$key] = $value;
        }
      }
      return $mergeData; 
    }


    public function processOrConditions($conditions=[])
    { 
        $str = "";       
                          
       foreach ($conditions as $key => $value) { //echo $key."<br>";
           if(is_numeric($key)) { 
            foreach ($value as $ikey => $ivalue) {
             if (preg_match('/[\'^<>=]/', $ikey))
             {      
               $str .=  "c.".$ikey." ".(((is_numeric($value) || (string)$value=='0' || (string)$value=='1') && $key!="_key")?$value:"'".$value."'");
             } else {
               $str .=  "c.".$ikey."==".(((is_numeric($value) || (string)$value=='0' || (string)$value=='1') && $key!="_key")?$value:"'".$value."'");
             }
             $str .= " OR ";
            }
           } else {     // echo '<pre>'; print_r($value); echo '</pre>'; exit;                           
             if (preg_match('/[\'^<>=]/', $key))
             {      
               $str .=  "c.".$key." ".((is_numeric($value) || (string)$value=='0' || (string)$value=='1')?$value:"'".$value."'");
             } else {
              if(is_numeric($value)) {
               $str .= "( c.".$key."=="."'".$value."' OR c.".$key."==".$value.")";
              } else {
               $str .=  "c.".$key."==".((is_numeric($value) || (string)$value=='0' || (string)$value=='1')?$value:"'".$value."'");
              }
             }
            $str .= " OR ";
         }
       //$str .= " OR "; 
      }
      return $str;
    }

    public function conditionProcess($conditions=[])
    { 
      $loopCount = 0;  
      if(count($conditions) > 0) { 
        $str = "FILTER ";
        foreach ($conditions as $key => $value) { 
          //  echo (is_numeric($value) || (string)$value=='0' || (string)$value=='1')?$value:"'".$value."'"; 
          if($key=="OR") {
           $str .= $this->processOrConditions($value);
          } else { 
            
            //FILTER c.age < 13 AND c.age != null OR c.age == 40
            if(strpos($key, '.') !== false) 
            { 
              $str .= $key."==".$value;
            } elseif (preg_match('/[\'^<>=!]/', $key))
            { 
              $str .=  "c.".$key." ".(((is_numeric($value) || (string)$value=='0' || (string)$value=='1') && $key!="_key")?$value:"'".$value."'");
            } else { 
              if(is_numeric($value)) {

              $str .= "( c.".$key."=="."'".$value."' OR c.".$key."==".$value.")";
              } else {
                $str .= "c.".$key."==".(((is_numeric($value) || (string)$value=='0' || (string)$value=='1') && $key!="_key")?$value:"'".$value."'");
              }
            }

            $str .=" AND ";
         }
        }
        $loopCount++;
      }
      $str = trim(trim($str, " OR ")," AND ");
      return $str;      
    }
    //For bind Queries
     public function bindParamsOrConditions($conditions=[])
    { 
       $valuesArray = []; 
       $str = "";  
       $loopCount = 100;              
       foreach ($conditions as $key => $value) { //echo $key."<br>";
           if(is_numeric($key)) { 
            foreach ($value as $ikey => $ivalue) {
             if (preg_match('/[\'^<>=]/', $ikey))
             {      
               $str .=  "c.".$ikey." @".$ikey.$loopCount;
             } else {
               $str .=  "c.".$ikey."== @".$ikey.$loopCount;
             }
             $str .= " OR ";
             $valuesArray[$ikey.$loopCount] = $ivalue;  
             $loopCount++;
            }
           } else {     // echo '<pre>'; print_r($value); echo '</pre>'; exit;                           
             if (preg_match('/[\'^<>=]/', $key))
             {      
               $str .=  "c.".$key." @".$key.$loopCount;
             } else {
               $str .=  "c.".$key."== @".$key.$loopCount;
             }
            $str .= " OR ";
            $valuesArray[$key.$loopCount] = $value;  
            $loopCount++;
         }
       //$str .= " OR "; 
         $loopCount++;
      }
      $response = [$str,$valuesArray];
      return $response;
    }

    public function bindParamsConditionProcess($conditions=[])
    { 
      $valuesArray = [];  
      $loopCount = 0;  
      if(count($conditions) > 0) { 
        $str = "FILTER ";
        foreach ($conditions as $key => $value) { 
          //  echo (is_numeric($value) || (string)$value=='0' || (string)$value=='1')?$value:"'".$value."'"; 
          if($key=="OR") {
           $bindOrValues = $this->bindParamsOrConditions($value); 
           $str .= isset($bindOrValues[0])?$bindOrValues[0]:"";
           if(isset($bindOrValues[1])) {
            $valuesArray += $valuesArray+$bindOrValues[1];
           }  
          } else { 
            
            //FILTER c.age < 13 AND c.age != null OR c.age == 40
            if (preg_match('/[\'^<>=]/', $key))
            { 
              $str .=  "c.".$key." @".$key.$loopCount;
             // $valuesArray[$key] = 
            } else {  
              $str .= "c.".$key."== @".$key.$loopCount;
            }

            $valuesArray[$key.$loopCount] = $value;  
            $str .=" AND ";
         }
        }
        $loopCount++;
      }
            
      $str = trim(trim($str, " OR ")," AND ");
      $response = [$str,$valuesArray];
      return $response; 
    }


}
