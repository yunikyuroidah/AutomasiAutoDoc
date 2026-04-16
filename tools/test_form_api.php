<?php
session_id('cli-test');
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'cli';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['debug'] = '1';

require __DIR__ . '/../backend/api/form.php';
