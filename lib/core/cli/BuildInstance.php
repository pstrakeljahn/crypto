<?php

namespace PS\Core\Cli;

use Config;
use PS\Core\Builder\ClassBuilder;
use PS\Core\Logging\Logging;
use PS\Packages\System\Classes\Package;
use PS\Packages\System\Classes\User;

class BuildInstance
{

    public bool $validate;
    public bool $onlyAdding;
    public bool $force;
    public Logging $logInstance;

    public function __construct($validate = true, $onlyAdding = true, $force = false)
    {
        $this->validate = $validate;
        $this->onlyAdding = $onlyAdding;
        $this->force = $force;
        Logging::generateFiles();
        $this->logInstance = Logging::getInstance();
        try {
            // Start Script
            $this->run();
        } catch (\Exception $e) {
            Logging::getInstance()->add(Logging::LOG_TYPE_ERROR, $e->getMessage(), false, true);
        }
    }

    private function run(): void
    {
        $this->printHeader();

        //Fetch Entites
        $packages = glob(Config::BASE_PATH . 'lib/packages/*');
        $classBuilder = new ClassBuilder;
        foreach ($packages as $package) {
            $entities = glob($package . "/database/*.json");
            foreach ($entities as $entityFile) {
                $entity = json_decode(file_get_contents($entityFile), true);
                $this->logInstance->add(Logging::LOG_TYPE_BUILD, 'Processing ' . $entity['name'], false, true);
                $classBuilder->buildClass($entity, $package . '/info.php', $this->validate, $this->onlyAdding, $this->force);
            }
        }
        $classBuilder->fetchKeyConstraints();

        $this->createIndex();

        // Install composer
        $this->installComposer();

        // Create Admin user
        $this->createAdminUser();

        $packages = glob(Config::BASE_PATH . 'lib/packages/*');
        foreach ($packages as $package) {
            $this->updatePackages(require($package . '/info.php'));
        }

        echo "\e[32mDone!\033[0m\n";
    }

    private function printHeader(): void
    {
        echo "**********************************************************\n";
        echo "*                   \"Instance Builder\"                   *\n";
        echo "**********************************************************\n";
        $validateSymbol = $this->validate ? "\e[32m✔\033[0m" : "\e[31mX\033[0m";
        $onlyAddingSymbol = $this->onlyAdding ? "\e[32m✔\033[0m" : "\e[31mX\033[0m";
        $forceSymbol = $this->force ? "\e[32m✔\033[0m" : "\e[31mX\033[0m";
        echo "[{$validateSymbol}] Validate   [{$onlyAddingSymbol}] Only Adding Changes  [{$forceSymbol}] Force Changes\n\n";
    }

    private function installComposer()
    {
        // Download Composer
        $ch = curl_init('https://getcomposer.org/installer');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->logInstance->add(Logging::LOG_TYPE_ERROR, curl_error($ch), false, true);
        }

        curl_close($ch);
        $filePath = Config::BASE_PATH . 'lib/core/';
        file_put_contents($filePath . 'composer-setup.php', $data);

        // Install packages
        $this->logInstance->add(Logging::LOG_TYPE_BUILD, 'Downloading Composer.phar', false, true);
        exec("php $filePath/composer-setup.php");
        exec("php composer.phar --working-dir={$filePath} install", $result);
        unlink("$filePath/composer-setup.php");
        unlink("composer.phar");
        echo "\n";
    }

    private function createAdminUser()
    {
        if (class_exists('PS\Packages\System\Classes\User')) {
            $user = User::getInstance()->select();
            if (!count($user)) {
                $user = User::getInstance()
                    ->setUsername('admin')
                    ->setPassword(password_hash('admin', PASSWORD_DEFAULT))
                    ->setMail('admin@admin.de')
                    ->setFirstname('')
                    ->setSurname('')
                    ->setRole(User::ENUM_ROLE_USER)
                    ->save();
                Logging::getInstance()->add(Logging::LOG_TYPE_DEBUG, 'Admin added');
                echo "\nAdmin User added (admin => admin)\n\n";
            }
        }
    }

    private function createIndex()
    {
        $namespaces = array();
        Config::BASE_PATH . 'lib/build/';
        $string = "<?php \n\nreturn [\n";

        foreach (glob(Config::BASE_PATH . 'lib/build/*.php') as $file) {
            if (str_contains($file, '_index.php')) continue;
            preg_match('/PS\\\\Packages\\\\.*\\\\Classes\\\\.*/', file_get_contents($file), $matches);
            if (count($matches)) {
                $trimmed = substr($matches[0], 0, -2);
                $explodedString = explode("\\", $trimmed);
                $namespaces[$explodedString[count($explodedString) - 1]] = $trimmed;
                $className = $explodedString[count($explodedString) - 1];
                $string .= "\t\"$className\" => \"$trimmed\",\n";
            }
        }

        $string .= "];";

        file_put_contents(Config::BASE_PATH . 'lib/build/_index.php', $string);
    }

    private function updatePackages(array $package)
    {
        $arrPackages = (new Package)->getInstance()->add(Package::NAME, $package['packageName'])->select();
        if (count($arrPackages)) {
            $arrPackages[0]->setVersion($package['version'])->save();
        } else {
            (new Package)->setName($package['packageName'])->setVersion($package['version'])->save();
        }
    }
}
