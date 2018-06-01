![Cakephp Logo](https://cakephp.org/img/trademarks/logo-2.jpg)
+
![ArangoDB-PHP Logo](http://www.arangodb.com/wp-content/uploads/2013/03/logo_arangodbphp_trans.png)

# ArangoDB-CAKEPHP-3x - A ArangoDb based model and Query builder for Cakephp 3.x

<br>
<br>
##### Table of Contents

- [Description](#description)
- [Requirements](#requirements)
- [Installing the PHP client](#installing)
 - [Using Composer](#using_composer)
 - [Cloning the git repository](#cloning_git)
- [How to use the Cakephp ArangoDb Client](#howto_use)
 - [Setting up the connection options](#setting_up_connection_options)
 - [Models](#cakephp_arangodb_models)
 - [Controllers](#cakephp_arangodb_controllers)

<br>

<a name="description"></a>
# Description

This is the cakephp version of the arangodb-php lib. the cakephp client allows you to convert your models to arangodb supported models easily. the cakephp arangodb client also allows you to use cakephp find, save, delete, update and joins etc simillar to cake core functionality.

<br>

<a name="requirements"></a>
# Requirements

* Cakephp version 3.0 or higher 

* ArangoDB database server version 3.0 or higher. 

* PHP version 5.6 or higher

Note on PHP version support: 

<br>



<a name="installing"></a>
## Installing the Cakephp arangodb client

To get started you need PHP 5.6 or higher plus an ArangoDB server running on any host that you can access.

There are two alternative ways to get the Cakephp ArangoDb client:

 * Using Composer
 * Cloning the git repository

<a name="using_composer"></a>
## Alternative 1: Using Composer

```
composer require cakephparangodb/arangodb
```

<a name="cloning_git"></a>
## Alternative 2: Cloning the git repository

You need to have a git client installed. To clone this repository from github, execute the following command in your project directory:

    git clone "https://github.com/shubham715/Cakephp-ArangoDb-Package.git"


This will create a subdirectory arangodb-php in your current directory. It contains all the files of the client library. It also includes a dedicated autoloader that you can use for autoloading the client libraries class files.
To invoke this autoloader, add the following line to your PHP files that will use the library:


<a name="invoke_autoloader_directly"></a>
## Alternative 3: Invoking the autoloader directly

If you do not wish to include autoload.php to load and setup the autoloader, you can invoke the autoloader directly:

```php
require 'arangodb-php/lib/ArangoDBClient/autoloader.php';
\ArangoDBClient\Autoloader::init();
```

<br>

<a name="howto_use"></a>
# How to use the PHP client

<a name="setting_up_connection_options"></a>
## Setting up the connection options

In order to use Cakephp ArangoDB, you need to specify the connection options. currently its only available from core files of this client in future updates we will add functionality to change db config from app.php

For now you need to open yourProject/vendor/cakephparangodb/arangodb/lib/ArangoDBClient/Connect.php and change db config of $_options according to your db config.



<a name="cakephp_arangodb_models"></a>
## Convert Cakephp Models to ArangoDb supported models. Change your cakephp models to like below example

Here for example i am changing UsersTable Model.


<?php
namespace App\Model\Table;

use ArangoDBClient\Connect;

class UsersTable extends \ArangoDBClient\Eloquent\Model
{

}
?>

Thats it. Now you can use Test Table of arangodb into controller like below example

<a name="cakephp_arangodb_controllers">
##Here i am giving some examples that how you can use models in controller

## Using Find
$data = $this->Users->find('all',['select'=>['name','email','status']]);
## Using FindOne
$data = $this->Users->findOne('all',['conditions'=>['email'=> $email]]);
## Join
$data = $this->Users->findWithJoin('all',['contain'=> 'userdetails', 'conditions'=>['c.userid'=> 'u.id']]);
//Here c is refer for base table and u is for join table.

##Save Data
$data = ['name'=>'shubham715', email'=> 'shubhamsharma715@gmail.com', 'status'=> 1];
$saveData = $this->Users->save($financeEntity);

<br>

Here lots of examples that we will update soon.

