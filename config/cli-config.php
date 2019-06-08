<?php

use App\Kernel;
use Doctrine\ORM\Tools\Console\ConsoleRunner;

require_once __DIR__.'/../vendor/autoload.php';

$kernel = new Kernel();

return ConsoleRunner::createHelperSet($kernel->getContainer()['em']);
