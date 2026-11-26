<?php

use Nette\Configurator;
use Nette\Utils\Random;

require_once __DIR__ . '/../bootstrap.php';

$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addParameters(['container' => ['class' => 'SystemContainer_' . Random::generate()]]);
$configurator->addConfig(__DIR__ . '/DI.extension.1.neon');

/** @var \Nette\DI\Container $container */
$container = $configurator->createContainer();
\Tester\Assert::true($container instanceof \Nette\DI\Container);

$mapper = $container->getByType('LeanMapper\DefaultMapper');
\Tester\Assert::true($mapper instanceof LeanMapper\DefaultMapper);
$mapper = $container->getService('LeanMapper.mapper');
\Tester\Assert::true($mapper instanceof LeanMapper\DefaultMapper);

/********************************************************/
// service overriding
/********************************************************/

class MyMapper extends \LeanMapper\DefaultMapper
{

}

$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addParameters(['container' => ['class' => 'SystemContainer_' . Random::generate()]]);
$configurator->addConfig(__DIR__ . '/DI.extension.2.neon');

/** @var \Nette\DI\Container $container */
$container = $configurator->createContainer();

$mapper = $container->getByType('LeanMapper\DefaultMapper');
\Tester\Assert::true($mapper instanceof MyMapper);
$mapper = $container->getByType('MyMapper');
\Tester\Assert::true($mapper instanceof MyMapper);
$mapper = $container->getService('LeanMapper.mapper');
\Tester\Assert::true($mapper instanceof LeanMapper\DefaultMapper);
$mapper = $container->getService('LeanMapper.mapper');
\Tester\Assert::true($mapper instanceof MyMapper);

/********************************************************/
// multiple scanDirs
/********************************************************/

// The extension only indexes scanDirs, so the application has to autoload the classes.
require_once __DIR__ . '/DI.extension.scanDirs/a/ScanDirsARepository.php';
require_once __DIR__ . '/DI.extension.scanDirs/b/ScanDirsBRepository.php';

$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addParameters([
    'container' => ['class' => 'SystemContainer_' . Random::generate()],
    'scanDirsFixtures' => __DIR__ . '/DI.extension.scanDirs',
]);
$configurator->addConfig(__DIR__ . '/DI.extension.3.neon');

/** @var \Nette\DI\Container $container */
$container = $configurator->createContainer();

\Tester\Assert::count(1, $container->findByType('ScanDirsARepository'));
\Tester\Assert::count(1, $container->findByType('ScanDirsBRepository'));
