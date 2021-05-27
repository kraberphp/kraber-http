<?php

$loader = require __DIR__ .'/../vendor/autoload.php';
$loader->addPsr4('Kraber\\Test\\', __DIR__);

define('URI_FACTORY', \Kraber\Http\Factory\UriFactory::class);
define('STREAM_FACTORY', \Kraber\Http\Factory\StreamFactory::class);
define('UPLOADED_FILE_FACTORY', \Kraber\Http\Factory\UploadedFileFactory::class);
