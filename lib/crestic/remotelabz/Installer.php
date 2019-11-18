<?php

namespace RemoteLabz;

use \Exception;
use RemoteLabz\Logger;
use RemoteLabz\System;
use RemoteLabz\Exception\AlreadyExistException;

class Installer {
    /**
     * Logger object to handle logs
     *
     * @var Logger $logger
     */
    private $logger;

    /**
     * Where to install RemoteLabz.
     *
     * @var string $installPath
     */
    private $installPath;

    /**
     * Options from CLI.
     *
     * @var array $options
     */
    private $options;

    public function __construct(string $installPath = "", array $options = [], Logger $log = null)
    {
        $this->installPath = $installPath;
        $this->options = $options;
        $this->logger = $log;
        if ($log == null) {
            $this->logger = new Logger();
        }
    }

    /**
     * Fluent interface for constructor so options can be added during construction
     *
     * @see Installer::__construct()
     * @return static
     */
    public static function create(string $installPath = "", array $options, Logger $log = null)
    {
        return new static($installPath, $options, $log);
    }

    /**
     * Check if this script is executed as root.
     *
     * @return void
     */
    function checkRoot() {
        $username = posix_getpwuid(posix_geteuid())['name'];
        if ($username != "root") {
            throw new Exception("Installation aborted, root is required! Please launch this script as root or with sudo.");
        }
    }

    /**
     * Check that all requirements are installed on a system.
     *
     * @return void
     */
    function checkRequirements() {
        if (!(strnatcmp(phpversion(), '7.2.0') >= 0)) {
            throw new Exception("You need PHP 7.2 or higher to use RemoteLabz. Please upgrade your PHP version to continue.");
        }
        if (!System::commandExists("apache2ctl")) {
            throw new Exception("Apache 2 has not been found on your system. Please install Composer to continue.");
        }
        if (!System::commandExists("composer")) {
            throw new Exception("Composer has not been found on your system. Please install Composer to continue.");
        }
    }

    /**
     * Full workflow of RemoteLabz installation.
     *
     * @return void
     */
    function install() {
        $this->logger->debug("Starting RemoteLabz installation");
        echo "Welcome to RemoteLabz!\n";
    
        // Copy self-directory into destination
        $this->logger->debug("Copying files to ".$this->installPath);
        echo "📁 Copying files to ".$this->installPath."... ";
        try {
            $this->copyFiles();
        } catch (AlreadyExistException $e) {
            $this->logger->warning("Install directory already exists. Not copying files.");
            Logger::print("Warning: Target directory exists. Files will not be copied. ", Logger::COLOR_YELLOW);
        }
        $this->logger->debug("Files has been moved to ".$this->installPath);
        echo "OK ✔️\n";

        $directoryError = "There was a problem switching to install dir.";
        // Goto new directory
        if (chdir($this->installPath) == false) {
            throw new Exception($directoryError);
        }
        // Check new dir
        if (getcwd() !== $this->installPath) {
            throw new Exception($directoryError);
        }

        // Configure environment
        $this->logger->debug("Setting environment variables");
        echo "📜 Setting environment variables... ";
        try {
            $this->configureEnvironment($this->options);
            $this->logger->debug("Finished setting environment variables");
            echo "OK ✔️\n";
        } catch (Exception $e) {
            throw new Exception("Error setting environment variables.", 0, $e);
        }

        // Install composer packages
        $this->logger->debug("Downloading Composer packages");
        echo "🎶 Downloading Composer packages... ";
        if ($this->configureComposer()) {
            $this->logger->debug("Finished downloading Composer packages");
            echo "OK ✔️\n";
        } else {
            throw new Exception("There was an error downloading composer packages.");
        }

        $this->logger->debug("Installing Yarn packages");
        echo "📦 Downloading Yarn packages... ";
        try {
            $this->configureYarn();
            $this->logger->debug("Finished downloading Yarn packages");
            echo "OK ✔️\n";
        } catch (Exception $e) {
            throw new Exception("There was an error downloading Yarn packages.");
        }
        
        $this->logger->debug("Warming cache");
        echo "🔥 Warming cache... ";
        if ($this->configureCache($this->options['environment'])) {
            $this->logger->debug("Finished warming cache");
            echo "OK ✔️\n";
        } else {
            throw new Exception("There was an error warming app cache.");
        }

        // Handle file permissions
        $this->logger->debug("Handling file permissions");
        echo "👮 Setting file permissions... ";
        $returnCode = 0;
        $output = [];
        exec("getent passwd remotelabz > /dev/null", $output, $returnCode);
        if ($returnCode) {
            exec("useradd remotelabz");
        }
        exec("getent group remotelabz > /dev/null", $output, $returnCode);
        if ($returnCode) {
            exec("groupadd remotelabz");
        }
        try {
            $this->rchown($this->installPath, "remotelabz", "www-data");
            echo "OK ✔️\n";
        } catch (Exception $e) {
            throw new Exception("Error setting file permissions.", 0, $e);
        }
        
        $this->logger->debug("Configuring Apache");
        $this->logger->debug("Port: " . $this->options['port']);
        $this->logger->debug("Server name: " . $this->options['server-name']);
        echo "🌎 Configuring Apache... ";
        try {
            $this->configureApache($this->options['port'], $this->options['server-name'], $this->options['max-filesize']);
            echo "OK ✔️\n";
        } catch (Exception $e) {
            throw new Exception("Error while configuring Apache.", 0, $e);
        }

        $this->logger->debug("Finished RemoteLabz installation");
        echo "Done!\n";
        echo "RemoteLabz is installed! 🔥\n";
        echo "Thank you for using our software. ❤️\n";
    }

    /**
     * Copy current directory to target installation directory if it's not done already.
     *
     * @return boolean Returns `true` if everything went well, returns `false` otherwise.
     */
    private function copyFiles() : void {
        $isCopied = true;
        // Check if directory is already to the right place
        if (dirname(__FILE__) != $this->installPath) {
            // Check if there is already a directory
            if (is_dir($this->installPath)) {
                $isCopied = false;
            } else {
                // Copy files
            $this->rcopy(dirname(__FILE__), $this->installPath);
            }
            
        } else {
            $isCopied = false;
        }

        if (!is_file("/usr/bin/remotelabz-ctl")) {
            symlink($this->installPath."/bin/remotelabz-ctl", "/usr/bin/remotelabz-ctl");
        }
        chmod("/usr/bin/remotelabz-ctl", 0777);

        copy($this->installPath."/.env.dist", $this->installPath."/.env");

        if (!$isCopied) {
            throw new AlreadyExistException("Folder already exists.");
        }
    }

    private function configureEnvironment($options)
    {
        // Modify environment
        Dotenv::create($this->installPath."/.env")
            ->parse()
            ->set("WORKER_SERVER", $options['worker-server'])
            ->set("WORKER_PORT", $options['worker-port'])
            ->set("WEBSOCKET_PROXY_SERVER", $options['proxy-server'])
            ->set("WEBSOCKET_PROXY_PORT", $options['proxy-port'])
            ->set("WEBSOCKET_PROXY_API_PORT", $options['proxy-api-port'])
            ->set("APP_ENV", $options['environment'])
            ->set("MYSQL_SERVER", $options['database-server'])
            ->set("MYSQL_USER", $options['database-user'])
            ->set("MYSQL_PASSWORD", $options['database-password'])
            ->set("MYSQL_DATABASE", $options['database-name'])
            ->set("MAILER_URL", $options['mailer-url'])
            ->save()
        ;
    }

    /**
     * Handle Composer packages installation.
     *
     * @return boolean Returns `true` if everything went well, returns `false` otherwise.
     */
    private function configureComposer() : bool {
        chdir($this->installPath);
        $returnCode = 0;
        $output = [];
        exec("COMPOSER_ALLOW_SUPERUSER=1 composer install --no-progress --no-suggest 2>&1", $output, $returnCode);
        $this->logger->debug($output);
        if ($returnCode) {
            return false;
        }
        return true;
    }

    /**
     * Warming application cache.
     *
     * @return boolean Returns `true` if everything went well, returns `false` otherwise.
     */
    private function configureCache($environment) : bool {
        chdir($this->installPath);
        $returnCode = 0;
        $output = [];
        exec("php ". $this->installPath."/bin/console cache:warm -e ".$environment." 2>&1", $output, $returnCode);
        $this->logger->debug($output);
        if ($returnCode) {
            return false;
        }
        return true;
    }

    private function configureApache($port, $serverName, $uploadMaxFilesize) {
        $output = [];
        $returnCode = 0;
        chdir($this->installPath);
        $portsFileContent = file_get_contents("/etc/apache2/ports.conf");
        if (preg_match("/Listen ${port}$/m", $portsFileContent) === 1) {
            $this->logger->debug("Port ${port} is already configured in Apache.");
        } else {
            file_put_contents("/etc/apache2/ports.conf", "\nListen ${port}\n", FILE_APPEND);
        }
        copy($this->installPath."/config/apache/100-remotelabz.conf", "/etc/apache2/sites-available/100-remotelabz.conf");
        $configFileContent = file_get_contents("/etc/apache2/sites-available/100-remotelabz.conf");
        $configFileContent = preg_replace("/^<VirtualHost *:80>$/", "<VirtualHost *:${port}>", $configFileContent);
        $configFileContent = preg_replace("/ServerName remotelabz.com/", "ServerName ${serverName}", $configFileContent);
        file_put_contents("/etc/apache2/sites-available/100-remotelabz.conf", $configFileContent);
        if (!is_file("/etc/apache2/sites-enabled/100-remotelabz.conf")) {
            symlink("/etc/apache2/sites-available/100-remotelabz.conf", "/etc/apache2/sites-enabled/100-remotelabz.conf");
        }

        // Handle PHP max upload filesize
        $phpPath = str_replace(["cli", ",", "\n"], ["apache2", "", ""], shell_exec("php --ini | grep fileinfo"));
        $postMaxSize = intval(intval(substr($uploadMaxFilesize, 0, -1)) * 1.25);
        $ini = parse_ini_file($phpPath);
        // If keys already exists
        if (array_key_exists("upload_max_filesize", $ini)) {
            $content = file_get_contents($phpPath);
            $content = preg_replace("/^(upload_max_filesize=)(.*)$/m", "$1".$uploadMaxFilesize, $content);
            file_put_contents($phpPath, $content);
        } else {
            file_put_contents($phpPath, "\nupload_max_filesize=".$uploadMaxFilesize."\n", FILE_APPEND);
        }
        if (array_key_exists("post_max_size", $ini)) {
            $content = file_get_contents($phpPath);
            $content = preg_replace("/^(post_max_size=)(.*)$/m", "$1".$postMaxSize, $content);
            file_put_contents($phpPath, $content);
        } else {
            file_put_contents($phpPath, "post_max_size=".$postMaxSize.substr($uploadMaxFilesize, -1), FILE_APPEND);
        }

        // Deactivate 000-default
        exec("a2dissite 000-default 2>&1", $output);
        $this->logger->debug($output);

        $this->logger->debug("Restarting Apache");
        unset($output);
        exec("apache2ctl restart 2>&1", $output, $returnCode);
        $this->logger->debug($output);
        if ($returnCode) {
            throw new Exception("Could not restart Apache correctly.");
        }
    }

    private function configureYarn() {
        chdir($this->installPath);
        $output = [];
        $returnCode = 0;
        exec("yarn install 2>&1", $output, $returnCode);
        $this->logger->debug($output);
        if ($returnCode) {
            throw new Exception("Could not restart install Yarn packages correctly.");
        }
        unset($output);
        exec("yarn encore dev 2>&1", $output, $returnCode);
        $this->logger->debug($output);
        if ($returnCode) {
            throw new Exception("Could not compile Yarn packages correctly.");
        }
        unset($output);
        exec("php bin/console assets:install --symlink public --relative", $output, $returnCode);
        $this->logger->debug($output);
        if ($returnCode) {
            throw new Exception("Could not symlink assets correctly.");
        }
    }

    /**
     * Recursively copy a folder.
     *
     * @param string $src Source directory
     * @param string $dst Target directory
     * @return void
     */
    private function rcopy($src, $dst) {
        $dir = opendir($src); 
        @mkdir($dst); 
        while(false !== ( $file = readdir($dir)) ) { 
            if (( $file != '.' ) && ( $file != '..' )) { 
                if ( is_dir($src . '/' . $file) ) { 
                    $this->rcopy($src . '/' . $file, $dst . '/' . $file); 
                } 
                else { 
                    copy($src . '/' . $file, $dst . '/' . $file); 
                } 
            } 
        }
        closedir($dir); 
    }

    /**
     * Recursively change owner user and group of a folder.
     *
     * @param string $dir The directory to manage
     * @param string|int $user The new owner user
     * @param string|int $group The new owner group
     * @return void
     */
    function rchown($dir, $user, $group) {
        if (!($d = opendir($dir))) {
            throw new Exception("Error while opening directory ${dir}: Directory does not exists or is not reachable.");
        }
        while(false !== ( $file = readdir($d)) ) {
            if (( $file != "." ) && ( $file != ".." )) {
                $path = $dir . "/" . $file ;

                if (is_dir($path)) {
                    $this->rchown($path, $user, $group);
                } else {
                    if (!chown($path, $user)) {
                        throw new Exception("Can't set permission of file ${path}: Permission refused or user does not exists.");
                    }
                    if (!chgrp($path, $group)) {
                        throw new Exception("Can't set permission of file ${path}: Permission refused or group does not exists..");
                    }
                }
            }
        }
        closedir($d);
    }

    /**
     * Get $logger
     *
     * @return  Logger
     */ 
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Set $logger
     *
     * @param  Logger  $logger  $logger
     *
     * @return  self
     */ 
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get $installPath
     *
     * @return  string
     */ 
    public function getInstallPath()
    {
        return $this->installPath;
    }

    /**
     * Set $installPath
     *
     * @param  string  $installPath  $installPath
     *
     * @return  self
     */ 
    public function setInstallPath(string $installPath)
    {
        $this->installPath = $installPath;

        return $this;
    }
}