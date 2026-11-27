<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

$uri = getenv('MONGODB_URI') ?: 'mongodb://admin:admin123@mongodb:27017/?authSource=admin';
$client = new Client($uri);
$db = $client->selectDatabase('akademik');
