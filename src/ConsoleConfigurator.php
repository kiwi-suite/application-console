<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\ApplicationConsole;

use Ixocreate\ApplicationConsole\Factory\CommandInitializer;
use Ixocreate\ApplicationConsole\Factory\CommandMapFactory;
use Ixocreate\Contract\Application\ConfiguratorInterface;
use Ixocreate\Contract\Application\ServiceRegistryInterface;
use Ixocreate\Contract\Command\CommandInterface;
use Ixocreate\Entity\Exception\InvalidArgumentException;
use Ixocreate\ServiceManager\Factory\AutowireFactory;
use Ixocreate\ServiceManager\SubManager\SubManagerConfigurator;

final class ConsoleConfigurator implements ConfiguratorInterface
{
    /**
     * @var SubManagerConfigurator
     */
    private $subManagerConfigurator;

    /**
     * MiddlewareConfigurator constructor.
     */
    public function __construct()
    {
        $this->subManagerConfigurator = new SubManagerConfigurator(ConsoleSubManager::class, CommandInterface::class);
        $this->subManagerConfigurator->addInitializer(CommandInitializer::class);
    }

    /**
     * @param string $directory
     * @param bool $recursive
     */
    public function addDirectory(string $directory, bool $recursive = true): void
    {
        $this->subManagerConfigurator->addDirectory($directory, $recursive);
    }

    /**
     * @param string $action
     * @param string $factory
     */
    public function addCommand(string $action, string $factory = AutowireFactory::class): void
    {
        $this->subManagerConfigurator->addFactory($action, $factory);
    }

    /**
     * @param ServiceRegistryInterface $serviceRegistry
     * @return void
     */
    public function registerService(ServiceRegistryInterface $serviceRegistry): void
    {
        $factories = $this->subManagerConfigurator->getServiceManagerConfig()->getFactories();

        $commandMap = [];
        foreach ($factories as $id => $factory) {
            if (!\is_subclass_of($id, CommandInterface::class, true)) {
                throw new InvalidArgumentException(\sprintf("'%s' doesn't implement '%s'", $id, CommandInterface::class));
            }
            $commandName = \forward_static_call([$id, 'getCommandName']);
            $commandMap[$commandName] = $id;

            $this->addCommand($commandName, CommandMapFactory::class);
        }

        $serviceRegistry->add(CommandMapping::class, new CommandMapping($commandMap));
        $this->subManagerConfigurator->registerService($serviceRegistry);
    }
}
