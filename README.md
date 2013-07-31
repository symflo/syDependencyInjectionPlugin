# SyDependencyInjectionPlugin

use [Symfony DependencyInjection Component SF2](https://github.com/symfony/DependencyInjection) on SF1
IN PROGRESS.

## Requirements

* PHP 5.3.3+

## Installation

Add autoload Composer on your symfony project.
In the `config/ProjectConfiguration.class.php` add:

```php
<?php
require_once __DIR__.'/../vendor/autoload.php';
?>
```

Add Symfony DependencyInjection Component in your `composer.json`

```shell
    "require": {
        ...
        "symfony/dependency-injection": "2.4.*@dev",
        "symfony/config": "2.4.*@dev",
        ...
    },
```

Activate the plugin in the `config/ProjectConfiguration.class.php`.

```php
<?php

class ProjectConfiguration extends sfProjectConfiguration
{
    public function setup()
    {
        $this->enablePlugins(array(
            /* ... */
            'syDependencyInjectionPlugin',
        ));
    }
}
?>
```

## Configuration

Two possibilities:

### Locator files
Simple and rapid way (not recommanded for big project)
In `app.yml`:

```yaml
all:
  syDependencyInjectionPlugin:
    locators:
      defaultFileLocators:
        dir: %SF_ROOT_DIR%/config/services
        files:
          - services.php
          - services.xml
          - services.yml
          ...
      otherFileLocators:
        dir: %SF_PLUGIN_DIR%/YourPlugin/config/otherservices
        files:
          - services.php
          ...
```

### Extensions

```yaml
all:
  syDependencyInjectionPlugin:
    extensions:
      - MyDefaultExtension
      - MyOtherExtension
    compilerPass:
      custom:
        class: CustomCompilerPass
        #passConfig: not required #TYPE_BEFORE_OPTIMIZATION, TYPE_OPTIMIZE, TYPE_BEFORE_REMOVING, TYPE_REMOVE, TYPE_AFTER_REMOVING

```
Example extension and compilerPass:
Extension in `lib\dependencyInjection`.

```php
<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\Config\FileLocator;

class DefaultExtension implements ExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config/services')
        );
        $loader->load('services.xml');
    }

    public function getXsdValidationBasePath()
    {
        return false;
    }

    public function getNamespace()
    {
        return false;
    }

    public function getAlias()
    {
        return 'default';
    }
}
?>
```

CompilerPass in `lib\dependencyInjection\compilerPass`:

```php
<?php

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CustomCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
    }
}

?>
```
More informations: [Documentation on Component Symfony DependencyInjection](http://symfony.com/doc/current/components/dependency_injection/compilation.html)

Naturaly you can use 2 ways in same time.


## Then in your Action

```php
<?php
//...

public function executeTestMeteor(sfWebRequest $request)
{ 
    $container = $this->getContainer();
    $service = $this->getService('your.service');
}

//...
?>
```