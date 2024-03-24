<?php


require_once __DIR__ . '/autoload.php';

class Services
{
    private array $arrServices = [];

    public function run()
    {
        $this->getServices();
        $keys = array_keys($this->arrServices);

        echo "\nStart Service Instance - manuell\n";
        echo "********************************\n";
        foreach ($keys as $index => $key) {
            echo "\t$index => $key\n";
        }

        echo "\nSelect the package in which the Service is located: ";
        do {
            $selectedPackage = $this->selectPackage($keys);
        } while ($selectedPackage === null);

        $keys = array_keys($this->arrServices[$selectedPackage]);

        echo "\n Services availayble in '$selectedPackage':\n";
        foreach ($keys as $index => $key) {
            echo "\t$index => $key\n";
        }

        echo "\nSelect the service you want to start: ";
        do {
            $selectedService = $this->selectService($keys);
        } while ($selectedService === null);

        $this->startupServcie($selectedPackage, $selectedService);
    }

    private function getServices(): void
    {
        $files = glob(Config::BASE_PATH . 'lib/packages/*/classes/services/*.php');
        $regex = '/^.+?\/lib\/packages\/(.+?)\/classes\/services\/.+$/';
        $arrServices = [];
        foreach ($files as $file) {
            preg_match($regex, $file, $package);
            $arrServices[$package[1]][basename($file, ".php")] = $file;
        }
        $this->arrServices = $arrServices;
    }

    private function selectPackage(array $keys): ?string
    {
        $selectedIndex = trim(fgets(STDIN));
        if (isset($keys[$selectedIndex])) {
            return $keys[$selectedIndex];
        } else {
            echo "\nSelection is inavlid! Select the package in which the Service is located: ";
            return null;
        }
    }

    private function selectService(array $keys): ?string
    {
        $selectedIndex = trim(fgets(STDIN));
        if (isset($keys[$selectedIndex])) {
            return $keys[$selectedIndex];
        } else {
            echo "\nSelection is inavlid! Select the service which is available: ";
            return null;
        }
    }

    private function startupServcie(string $package, string $class): void
    {
        $className = sprintf('PS\Packages\%s\Classes\Services\%s', ucfirst($package), $class);
        $instance = new $className();
        $instance->start();
    }
}

(new Services)->run();
