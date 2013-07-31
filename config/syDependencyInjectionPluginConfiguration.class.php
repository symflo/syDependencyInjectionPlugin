<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

/**
 * syDependencyInjectionPluginConfiguration configuration.
 *
 * @package     syDependencyInjectionPlugin
 * @subpackage  config
 * @author      Florent Mondoloni
 */
class syDependencyInjectionPluginConfiguration extends sfPluginConfiguration
{
    protected $container;

    /**
     * @see sfPluginConfiguration
     *
     * Initialize the service container
     */
    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', array($this, 'initializeServiceContainer'));

        $this->dispatcher->connect('configuration.method_not_found', array($this, 'listenToMethodNotFound'));
        $this->dispatcher->connect('context.method_not_found', array($this, 'listenToMethodNotFound'));
        $this->dispatcher->connect('component.method_not_found', array($this, 'listenToMethodNotFound'));
        $this->dispatcher->connect('form.method_not_found', array($this, 'listenToMethodNotFound'));
        $this->dispatcher->connect('response.method_not_found', array($this, 'listenToMethodNotFound'));
        $this->dispatcher->connect('user.method_not_found', array($this, 'listenToMethodNotFound'));
        $this->dispatcher->connect('view.method_not_found', array($this, 'listenToMethodNotFound'));
    }

    /**
     * Listener method for the method_not_found event
     * Calls the getServiceContainer() method
     *
     * @return boolean
     */
    public function listenToMethodNotFound($event)
    {
        if ('getContainer' == $event['method']) {
            $event->setReturnValue($this->getContainer());

            return true;
        }

        if ('getService' == $event['method']) {
            $event->setReturnValue($this->getContainer()->get($event['arguments'][0]));

            return true;
        }

        return false;
    }

    /**
     * Returns the current service container instance
     *
     * @return sfServiceContainer
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Initialize the service container and cache it.
     */
    public function initializeServiceContainer(sfEvent $event)
    {
        $file = self::getServiceContainerFilename();

        if (!sfConfig::get('sf_debug') && file_exists($file)) {
            require_once $file;
            $container = new ProjectServiceContainer();
        } else {
            $container = new ContainerBuilder();
            $syContainerFileLoader = new SyContainerFileLoader($container);
            $syContainerFileLoader->loadExtensions();
            $syContainerFileLoader->loadFileLocators();
            $syContainerFileLoader->loadCompilerPass();
            $container->compile();
            $dumper = new PhpDumper($container);
            file_put_contents($file, $dumper->dump());
            $this->dispatcher->notify(new sfEvent($container, 'dependency_injection_container.load_configuration'));
        }

        $this->container = $container;
        $context = $event->getSubject();
        $context->set('dependency_injection_container', $this->container);
        $this->dispatcher->notify(new sfEvent($this->container, 'dependency_injection_container.post_initialize'));
    }

    /**
     * getServiceContainerFilename
     * @return string
     */
    public static function getServiceContainerFilename()
    {
        $application = sfConfig::get('sf_app');
        $debug       = sfConfig::get('sf_debug');
        $environment = sfConfig::get('sf_environment');
        $name = 'Project'.md5($application.$debug.$environment).'ServiceContainer';

        return sfConfig::get('sf_app_cache_dir').'/'.$name.'.php';        
    }
}