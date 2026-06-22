#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap/app.php';

use App\Console\Application;
use App\Console\Commands\FreshCommand;
use App\Console\Commands\ListCommand;
use App\Console\Commands\MakeCommand;
use App\Console\Commands\MakeMigrationCommand;
use App\Console\Commands\MigrateCommand;
use App\Console\Commands\MigrationsListCommand;
use App\Console\Commands\QualityCommand;
use App\Console\Commands\RollbackCommand;
use App\Console\Commands\RouteListCommand;
use App\Console\Commands\DoctorCommand;
use App\Console\Commands\SeedCommand;
use App\Console\Commands\ServeCommand;

$app = new Application();
$app
    ->register(new ListCommand($app))
    ->register(new MigrateCommand())
    ->register(new RollbackCommand())
    ->register(new MigrationsListCommand())
    ->register(new SeedCommand())
    ->register(new FreshCommand())
    ->register(new MakeCommand())
    ->register(new MakeCommand('repository'))
    ->register(new MakeCommand('controller'))
    ->register(new MakeCommand('middleware'))
    ->register(new MakeCommand('dto'))
    ->register(new MakeCommand('test'))
    ->register(new MakeMigrationCommand())
    ->register(new RouteListCommand())
    ->register(new ServeCommand())
    ->register(new DoctorCommand())
    ->register(new QualityCommand('test'))
    ->register(new QualityCommand('analyse'))
    ->register(new QualityCommand('lint'))
    ->register(new QualityCommand('format'))
    ->register(new QualityCommand('format-check'))
    ->register(new QualityCommand('qa'));

exit($app->run($argv));
