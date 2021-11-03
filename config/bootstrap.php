<?php

declare(strict_types=1);

use App\App;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

try {
    (new App())->boot()->get('cli_app');
} catch (Exception $e) {
    echo $e->getMessage();
}