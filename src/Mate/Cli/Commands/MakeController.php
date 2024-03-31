<?php

namespace Mate\Cli\Commands;

use Mate\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeController extends Command
{
    protected static $defaultName = "make:controller";
    protected static $defaultDescription = "Create a new controller";

    protected function configure()
    {
        $this->addArgument("name", InputArgument::REQUIRED, "Controller name");
        $this->addOption("api", null, InputOption::VALUE_NONE, "Create an API controller");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument("name");
        $isApi = $input->getOption("api");
        $segments = explode("/", $name);
        array_pop($segments);
    
        // Agregar la palabra "Controller" al final del nombre si no está presente
        $name = $this->ensureControllerSuffix($name);
        $directory = $this->getControllerDirectory($isApi);
        // Crear la estructura de directorios
        $this->createDirectories($segments,$directory);

        // Generar el archivo del controlador
        $filepath = $directory . "/" . $name . ".php";
        $template = str_replace("ControllerName", $name, template("controller"));
        file_put_contents($filepath, $template);

        // Mostrar mensaje de éxito
        $output->writeln("<info>Controller created => $filepath</info>");

        return Command::SUCCESS;
    }

    protected function ensureControllerSuffix($name)
    {
        $segments = explode("/", $name);
        $pascalSegments = array_map('ucfirst', $segments);
        return implode("/", $pascalSegments) . "Controller";
    }

    protected function getControllerDirectory($isApi = false)
    {
        $baseDirectory = App::getRoot() . "/app/Http/Controllers";
        if ($isApi) {
            return $baseDirectory . "/Api";
        } else {
            return $baseDirectory;
        }
    }

    function createDirectories($segments, $directory)
    {
        foreach ($segments as $segment) {
            $directory .= "/" . $segment;
            if (!is_dir($directory)) {
                mkdir($directory, 0755);
            }
        }
    }
}