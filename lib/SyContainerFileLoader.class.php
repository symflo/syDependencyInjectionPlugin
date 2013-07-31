<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

/**
 * @author Florent Mondoloni
 */
class SyContainerFileLoader
{
    private $containerBuilder;
    private $extensionsSupported = array('php', 'xml', 'yaml');

    /**
     * __construct
     * @param ContainerBuilder $containerBuilder
     */
    public function __construct(ContainerBuilder $containerBuilder)
    {
        $this->containerBuilder = $containerBuilder;
    }

    /**
     * loadFileLocators
     */
    public function loadFileLocators()
    {
        $locators = sfConfig::get('app_syDependencyInjectionPlugin_locators');
        if (null === $locators) {
            return;
        }

        foreach ($locators as $locator) {
            $filesByExtension = $this->groupFileByExtension($locator);
            foreach ($filesByExtension as $extension => $files) {
                $loaderClass = self::getLoaderClassForExtension($extension);
                $loader = new $loaderClass($this->containerBuilder, new FileLocator($locator['dir']));

                foreach ($files as $file) {
                    $loader->load($file);
                }
            }
        }
    }

    /**
     * loadExtensions
     */
    public function loadExtensions()
    {
        $extensions = sfConfig::get('app_syDependencyInjectionPlugin_extensions');
        if (null === $extensions) {
            return;
        }

        foreach ($extensions as $extensionClass) {
            $extension = new $extensionClass();
            $this->containerBuilder->registerExtension($extension);
            $this->containerBuilder->loadFromExtension($extension->getAlias());
        }
    }

    /**
     * loadCompilerPass
     */
    public function loadCompilerPass()
    {
        $compilerPass = sfConfig::get('app_syDependencyInjectionPlugin_compilerPass');
        if (null === $compilerPass) {
            return;
        }

        foreach ($compilerPass as $compilerPassOptions) {
            if (!array_key_exists('passConfig', $compilerPassOptions)) {
                $compilerPassOptions['passConfig'] = PassConfig::TYPE_BEFORE_OPTIMIZATION;
            }

            $this->containerBuilder->addCompilerPass(
                new $compilerPassOptions['class'],
                $compilerPassOptions['passConfig']
            );
        }
    }

    /**
     * groupFileByExtension
     * allow to limit instance count
     * @param  array $locator
     * @return array
     */
    private function groupFileByExtension($locator)
    {
        $filesByExtension = array();
        foreach ($locator['files'] as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (!in_array($extension, $this->extensionsSupported)) {
                throw new \InvalidArgumentException(sprintf("Extension %s for file %s does not supported", $extension, $file));
            }

            $filesByExtension[$extension][] = $file;
        }

        return $filesByExtension;
    }

    /**
     * getLoaderClassForExtension
     * @param  string $extension
     * @return string
     */
    public static function getLoaderClassForExtension($extension)
    {
        switch ($extension) {
            case 'php':
                return 'Symfony\Component\DependencyInjection\Loader\PhpFileLoader';
                break;

            case 'xml':
                return 'Symfony\Component\DependencyInjection\Loader\XmlFileLoader';
                break;

            case 'yaml':
                return 'Symfony\Component\DependencyInjection\Loader\YamlFileLoader';
                break;
            
            default:
                throw new \InvalidArgumentException(sprintf("Extension %s does not supported", $extension));
                break;
        }
    }
}