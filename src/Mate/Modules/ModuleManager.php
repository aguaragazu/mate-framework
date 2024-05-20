<?php

namespace Mate\Modules;

use Mate\App;
use Mate\Contracts\ModuleManagerInterface;
use Mate\File\Filesystem;

class ModuleManager implements ModuleManagerInterface
{
    private $app;
    private $filesystem;

    public function __construct(App $app, Filesystem $filesystem)
    {
        $this->app = $app;
        $this->filesystem = $filesystem;
    }

    public function getModules(): array
    {
        return $this->app->getRegisteredModules(); // Access modules from App instance
    }

    public function discoverModules(): void
    {
        // // Implementar la lógica para descubrir módulos en el directorio modules
        // $modulesDir = base_path('modules');
        // $modules = $this->filesystem->scandir($modulesDir);

        // foreach ($modules as $moduleDir) {
        //     // Verificar si el directorio contiene los archivos necesarios para un módulo
        //     if (is_dir($modulesDir) && file_exists($modulesDir . '/Module.php')) {
        //         // Registrar el módulo con el contenedor de dependencias
        //         $this->app->registerModule($moduleDir);
        //     }
        // }

        $this->app->scanModules(base_path() . '/modules');
    }

    public function registerModules():void
    {
        // Implementar la lógica para registrar módulos con el contenedor de dependencias
        $registeredModules = $this->app->getRegisteredModules();

        foreach ($registeredModules as $moduleDir) {
            // Cargar el archivo Module.php del módulo
            require_once $moduleDir . '/Module.php';

            // Obtener la clase del módulo y registrarla con el contenedor de dependencias
            $moduleClass = 'App\\Modules\\' . basename($moduleDir) . '\\Module';
            $this->app->bind(Module::class, $moduleClass);
        }
    }

    public function bootModules(): void
    {
        // Implementar la lógica para arrancar cada módulo registrado
        $registeredModules = $this->app->getRegisteredModules();

        foreach ($registeredModules as $moduleDir) {
            // Obtener la instancia del módulo del contenedor de dependencias
            $module = $this->app->make(Module::class);

            // Invocar el método de arranque del módulo
            $module->boot();
        }
    }

    public function shutdownModules(): void
    {
        // Implementar la lógica para cerrar cada módulo registrado
        $registeredModules = $this->app->getRegisteredModules();

        foreach ($registeredModules as $moduleDir) {
            // Obtener la instancia del módulo del contenedor de dependencias
            $module = $this->app->make(Module::class);

            // Invocar el método de cierre del módulo
            $module->shutdown();
        }
    }
}