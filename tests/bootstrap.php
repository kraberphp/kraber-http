<?php

$loader = require __DIR__ .'/../vendor/autoload.php';
$loader->addPsr4('Kraber\\Test\\Unit\\', __DIR__."/unit");
$loader->addPsr4('Kraber\\Test\\Integration\\', __DIR__."/integration");

/**
 * Used by integration tests (php-http/psr7-integration-tests)
 */
define('URI_FACTORY', \Kraber\Http\Factory\UriFactory::class);
define('STREAM_FACTORY', \Kraber\Http\Factory\StreamFactory::class);
define('UPLOADED_FILE_FACTORY', \Kraber\Http\Factory\UploadedFileFactory::class);
