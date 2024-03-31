<?php

namespace Mate\Cli\Commands;

use Mate\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Dotenv\Dotenv;
use Symfony\Component\Console\Input\InputOption;

class KeyGenerate extends Command
{
    /**
     * The algorithm used for encryption.
     *
     * @var string
     */
    protected $cipher;

    /**
     * The supported cipher algorithms and their properties.
     *
     * @var array
     */
    private static $supportedCiphers = [
        'aes-128-cbc' => ['size' => 16, 'aead' => false],
        'aes-256-cbc' => ['size' => 32, 'aead' => false],
        'aes-128-gcm' => ['size' => 16, 'aead' => true],
        'aes-256-gcm' => ['size' => 32, 'aead' => true],
    ];

    protected function configure()
    {
        $this->setName('key:generate');
        $this->setDescription('Genera una clave de aplicación');
        $this->addOption("show", null, InputOption::VALUE_NONE, "Create an API controller");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $this->generateRandomKey();
        $envFile = App::getRoot();

        if ($input->getOption('show')) {
            $output->writeln('<comment>'.$key.'</comment>');
        }

        if (!file_exists($envFile)) {
            $output->writeln("<error>El archivo .env no existe en la ruta: $envFile</error>");
            return Command::FAILURE;
        }

        if (! $this->setKeyInEnvironmentFile($key,$output)) {
            return;
        }
        
        $dotenv = Dotenv::createImmutable($envFile);
        $dotenv->load();
        
        putenv('APP_KEY=' . $key);
        $dotenv->safeLoad();
        $output->writeln('<info>Clave de aplicación generada satisfactoriamente.</info>');
        
        return Command::SUCCESS;
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:'.base64_encode(
            self::generateKey(config('app.cipher'))
        );
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string  $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key,OutputInterface $output)
    {
        $currentKey = config('app.key');

        if (strlen($currentKey) !== 0) {
            return false;
        }

        if (! $this->writeNewEnvironmentFileWith($key, $output)) {
            return false;
        }

        return true;
    }

    /**
     * Create a new encryption key for the given cipher.
     *
     * @param  string  $cipher
     * @return string
     */
    public static function generateKey($cipher)
    {
        return random_bytes(self::$supportedCiphers[strtolower($cipher)]['size'] ?? 32);
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param  string  $key
     * @return bool
     */
    protected function writeNewEnvironmentFileWith($key,OutputInterface $output)
    {
        $replaced = preg_replace(
            $this->keyReplacementPattern(),
            'APP_KEY='.$key,
            $input = file_get_contents(App::getRoot() . '/.env')
        );

        if ($replaced === $input || $replaced === null) {
            $output->writeln('<error>Unable to set application key. No APP_KEY variable was found in the .env file.</error>');

            return false;
        }

        file_put_contents(App::getRoot() . '/.env', $replaced);

        return true;
    }

    /**
     * Get a regex pattern that will match env APP_KEY with any random key.
     *
     * @return string
     */
    protected function keyReplacementPattern()
    {
        $escaped = preg_quote('='.config('app.key'), '/');

        return "/^APP_KEY{$escaped}/m";
    }
}