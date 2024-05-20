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
        $this->addArgument("name", InputArgument::REQUIRED, "Controller name")
            ->addOption("api", "a", InputOption::VALUE_OPTIONAL, "Also create api Controller file", false)
            ->addOption("model", "m", InputOption::VALUE_OPTIONAL, "Also create Model file", false)
            ->addOption("resource", "r", InputOption::VALUE_OPTIONAL, "Also create Resource file", false)
            ->addOption("service", "s", InputOption::VALUE_OPTIONAL, "Also create Service file", false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument("name");
        $template = file_get_contents(resourcesDirectory() . "/templates/controller.php");
        $api = $input->getOption("api");
        $model = $input->getOption("model");
        $resource = $input->getOption("resource");
        $service = $input->getOption("service");
        $new_name = "";

        $file_controller = App::$root . "/app/Http/Controllers/$name.php";
        $file_resource = App::$root . "/app/Http/Resources/$name.php";
        $file_model = App::$root . "/app/Models/$name.php";
        $file_service = App::$root . "/app/Services/$name.php";

        // create controlller
        if ($api !== false) {
            $template = file_get_contents(resourcesDirectory() . "/templates/apiController.php");
            $file_controller = App::$root . "/app/Http/Controllers/Api/$name.php";
        }
        if (file_exists($file_controller)) {
            $output->writeln("<error>Controller already exists => $name.php</error>");
            return Command::FAILURE;
        }
        $template = str_replace("ControllerName", $name, $template);
        file_put_contents($file_controller, $template);
        $output->writeln("<info>Controller created => $name.php</info>");

        // create model
        if ($model !== false) {
            $model_name = ucfirst(str_replace("Controller", "", $name));
            $template = file_get_contents(resourcesDirectory() . "/templates/model.php");
            $file_model = App::$root . "/app/Models/$model_name.php";
        
            if (file_exists($file_model)) {
                $output->writeln("<error>Model already exists => $model_name.php</error>");
                return Command::FAILURE;
            }
            $template = str_replace("ModelName", $model_name, $template);
            file_put_contents($file_model, $template);
            $output->writeln("<info>Model created => $new_name.php</info>");
        }
        // Create service 
        if ($service !== false) {
            $new_name = ucfirst(str_replace("Controller", "Service", $name));
            $template = file_get_contents(resourcesDirectory() . "/templates/service.php");
            $file_service = App::$root . "/app/Services/$new_name.php";
        
            if (file_exists($file_service)) {
                $output->writeln("<error>Service already exists => $new_name.php</error>");
                return Command::FAILURE;
            }
            $template = str_replace("ServiceName", $name, $template);
            file_put_contents($file_service, $template);
            $output->writeln("<info>Service created => $name.php</info>");
        }
        // Create resource 
        if ($resource !== false) {
            $new_name = ucfirst(str_replace("Controller", "Resource", $name));
            $template = file_get_contents(resourcesDirectory() . "/templates/resource.php");
            $file_resource = App::$root . "/app/Http/Resources/$new_name.php";
        
            if (file_exists($file_resource)) {
                $output->writeln("<error>Resource already exists => $new_name.php</error>");
                return Command::FAILURE;
            }
            $template = str_replace("ResourceName", $new_name, $template);
            file_put_contents($file_resource, $template);
            $output->writeln("<info>Resource created => $new_name.php</info>");
        }
        
        return Command::SUCCESS;
    }
}
