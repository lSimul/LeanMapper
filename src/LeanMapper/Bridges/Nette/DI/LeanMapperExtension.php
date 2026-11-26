<?php

namespace LeanMapper\Bridges\Nette\DI;

use Nette;
use Nette\Loaders\RobotLoader;

class LeanMapperExtension extends Nette\DI\CompilerExtension
{

    public $defaults = [
        'db' => [],
        'profiler' => true,
        'scanDirs' => null,
        'logFile' => null,
    ];



    public function loadConfiguration()
    {
        $container = $this->getContainerBuilder();
        $config = $this->getMergedConfig();

        $index = 1;
        foreach ($this->findRepositories($config) as $repositoryClass) {
            $container->addDefinition($this->prefix('table.' . $index++))->setFactory($repositoryClass);
        }

        $container->addDefinition($this->prefix('mapper'))
            ->setFactory('LeanMapper\DefaultMapper');

        $container->addDefinition($this->prefix('entityFactory'))
            ->setFactory('LeanMapper\DefaultEntityFactory');

        $connection = $container->addDefinition($this->prefix('connection'))
            ->setFactory('LeanMapper\Connection', [$config['db']]);

        if (isset($config['db']['flags'])) {
            $flags = 0;
            foreach ((array)$config['db']['flags'] as $flag) {
                $flags |= constant($flag);
            }
            $config['db']['flags'] = $flags;
        }

        if (class_exists('Tracy\Debugger') && $container->parameters['debugMode'] && $config['profiler']) {
            $panel = $container->addDefinition($this->prefix('panel'))->setFactory('Dibi\Bridges\Tracy\Panel');
            $connection->addSetup([$panel, 'register'], [$connection]);
            if ($config['logFile']) {
                $fileLogger = $container->addDefinition($this->prefix('fileLogger'))->setFactory('SavingFunds\LeanMapper\FileLogger');
                $connection->addSetup([$fileLogger, 'register'], [$connection, $config['logFile']]);
            }
        }
    }



    /**
     * Merges the defaults into the config the way nette/di 2.4's getConfig($defaults) did, which nette/di 3 dropped.
     */
    private function getMergedConfig(): array
    {
        $container = $this->getContainerBuilder();
        $this->defaults['scanDirs'] = '%appDir%';

        return Nette\DI\Config\Helpers::merge($this->config, Nette\DI\Helpers::expand($this->defaults, $container->parameters));
    }



    private function findRepositories($config)
    {
        $classes = [];

        if ($config['scanDirs']) {
            $robot = new RobotLoader;
            // nette/robot-loader 3+ removed cache storages.
            if (method_exists($robot, 'setCacheStorage')) {
                $robot->setCacheStorage(new Nette\Caching\Storages\DevNullStorage);
            }
            // nette/robot-loader 4 takes only strings, and 2.4 takes a single argument.
            foreach ((array) $config['scanDirs'] as $dir) {
                $robot->addDirectory($dir);
            }
            $robot->acceptFiles = ['*.php'];
            $robot->rebuild();
            $classes = array_keys($robot->getIndexedClasses());
        }

        $repositories = [];
        foreach (array_unique($classes) as $class) {
            if (class_exists($class)
                && ($rc = new \ReflectionClass($class)) && $rc->isSubclassOf('LeanMapper\Repository')
                && !$rc->isAbstract()
            ) {
                $repositories[] = $rc->getName();
            }
        }
        return $repositories;
    }

}
