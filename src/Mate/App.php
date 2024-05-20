<?php

namespace Mate;

use Dotenv\Dotenv;
use Mate\Config\Config;
use Mate\Database\Drivers\DatabaseDriver;
use Mate\Database\Model as DatabaseModel;
use Mate\Http\HttpMethod;
use Mate\Http\HttpNotFoundException;
use Mate\Http\Request;
use Mate\Http\Response;
use Mate\Routing\Router;
use Mate\Server\Server;
use Mate\Session\Session;
use Mate\Session\SessionStorage;
use Mate\Support\Facades\Model;
use Mate\Validation\Exceptions\ValidationException;
use Throwable;

class App
{
    public static string $root;

    public Router $router;

    public Request $request;

    public Server $server;

    public Session $session;

    public DatabaseDriver $database;

    private array $registeredModules = [];

    public static function bootstrap(string $root)
    {
        self::$root = $root;

        $app = singleton(self::class);

        return $app
            ->loadConfig()
            ->runServiceProviders('boot')
            ->setHttpHandlers()
            ->setUpDatabaseConnection()
            ->runServiceProviders('runtime');

        // Register modules
        // $app->scanModules($root . '/modules');
        
        return $app;
    }

    protected function loadConfig(): self
    {
        Dotenv::createImmutable(self::$root)->load();
        Config::load(self::$root . "/config");

        return $this;
    }

    protected function runServiceProviders(string $type): self
    {
        foreach (config("providers.$type", []) as $provider) {
            $provider = new $provider();
            $provider->registerServices();
        }

        return $this;
    }

    protected function setHttpHandlers(): self
    {
        $this->router = singleton(Router::class);
        $this->server = app(Server::class);
        $this->request = singleton(Request::class, function () {
            $method = HttpMethod::from($_SERVER['REQUEST_METHOD']);
            return new Request($method);
        });
        $this->session = singleton(Session::class, fn () => new Session(app(SessionStorage::class)));

        return $this;
    }

    protected function setUpDatabaseConnection(): self
    {
        $this->database = app(DatabaseDriver::class);

        $this->database->connect(
            config("database.connection"),
            config("database.host"),
            config("database.port"),
            config("database.database"),
            config("database.username"),
            config("database.password"),
        );
        
        // Model::setConnection($this->database);

        return $this;
    }

    protected function prepareNextRequest()
    {
        if ($this->request->method() == HttpMethod::GET) {
            $this->session->set('_previous', $this->request->uri());
        }
    }

    protected function terminate(Response $response)
    {
        $this->prepareNextRequest();
        $this->server->sendResponse($response);
        $this->database->close();
        exit();
    }

    public function run()
    {
        try {
            $this->terminate($this->router->resolve($this->request));
        } catch (HttpNotFoundException $e) {
            $this->abort(Response::text("Not found")->setStatus(404));
        } catch (ValidationException $e) {
            $this->abort(back()->withErrors($e->errors(), 422));
        } catch (Throwable $e) {
            $response = json([
                "error" => $e::class,
                "message" => $e->getMessage(),
                "trace" => $e->getTrace()
            ]);

            $this->abort($response->setStatus(500));
        }
    }

    public function abort(Response $response)
    {
        $this->terminate($response);
    }

    public function scanModules(string $modulesDir): void
    {
        $modulesDir = rtrim($modulesDir, '/'); // Ensure no trailing slash

        foreach (new \DirectoryIterator($modulesDir) as $fileInfo) {
            if ($fileInfo->isDir() && $fileInfo->isReadable()) {
                $moduleDir = $fileInfo->getPathname();
                $this->registerModule($moduleDir);
            }
        }
    }

    public function getRegisteredModules(): array
    {
        return $this->registeredModules;
    }

    public function registerModule(string $moduleDir): void
    {
        if (!is_dir($moduleDir)) {
            throw new \InvalidArgumentException("Invalid module directory: $moduleDir");
        }

        $moduleName = basename($moduleDir);
        $this->registeredModules[$moduleName] = $moduleDir;

        
    }
}
