<?php
$config = require 'config/config.php';
$db = pg_connect('host=' . $config['DB_HOST'] . ' dbname=' . $config['DB_NAME'] . ' user=' . $config['DB_USER'] . ' password=' . $config['DB_PASS']);
?>