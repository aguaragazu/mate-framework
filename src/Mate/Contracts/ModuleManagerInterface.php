<?php

namespace Mate\Contracts;

interface ModuleManagerInterface
{
    /**
     * Descubre módulos dentro del sistema de archivos de la aplicación.
     *
     * @return void
     */
    public function discoverModules(): void;

    /**
     * Registra módulos con el contenedor de dependencias de la aplicación.
     *
     * @return void
     */
    public function registerModules(): void;

    /**
     * Arranca cada módulo registrado, inicializando su funcionalidad.
     *
     * @return void
     */
    public function bootModules(): void;

    /**
     * Cierra cada módulo registrado, liberando recursos y finalizando su ejecución.
     *
     * @return void
     */
    public function shutdownModules(): void;
}