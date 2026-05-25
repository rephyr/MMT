<?php
require dirname(__DIR__) . '/config/bootstrap.php';

use Cake\TestSuite\Fixture\SchemaLoader;

// Load one or more SQL files.
(new SchemaLoader())->loadSqlFiles('sql/MMT Database.sql', 'test');
