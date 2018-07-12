<?php

abstract class connection
{

    protected $db;

    protected $configuration_file;
    protected $databases = [];

    public function __construct($options = [])
    {

        $path = isset($options['path']) ? $options['path'] : '/media/ephemeral0/';
        $project = isset($options['project']) ? $options['project'] : 'superdry';
        $configuration_file = isset($options['conf_file']) ? $options['conf_file'] : 'configuration.php';
        $site = isset($options['site']) ? $options['site'] : null;

        $this->configuration_file = $path . $project . DIRECTORY_SEPARATOR . $configuration_file;

        $this->getConfig();
        $this->db = $this->getDB($site);

    }

    /**
     * @param $site
     * @return PDO
     */
    private function getDB($site)
    {

        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;dbname=%s;', $this->database[$site]['host'], $this->database[$site]['dbname']),
                $this->database[$site]['user'],
                $this->database[$site]['password']
            );
            $pdo->query("SET CHARACTER SET 'utf8';SET NAMES 'utf8';");

            return $pdo;
        } catch (PDOException $e) {
            exit('Unable to connect to database');
        }
    }

    /**
     *
     */
    private function getConfig()
    {

        /**
         * TODO - Include ENV and AWS Secrets Manager
         */

        try {
            if (!class_exists('JConfig')) {
                if (file_exists($this->configuration_file)) {
                    require_once $this->configuration_file;
                }
            }
        } catch (Exception $e) {

        }

        error_reporting(0);
        $jconf = new JConfig();
        $database_setting = $jconf->getDbSettings();
        error_reporting(E_ALL & ~E_NOTICE && ~E_STRICT);


        foreach ($database_setting as $database_setting) {
            foreach ($database_setting as $site => $settings) {
                $this->database[$site] = [
                    'driver' => 'mysqli',
                    'host' => $settings['host'],
                    'dbname' => $settings['database'],
                    'user' => $settings['user'],
                    'password' => $settings['password'],
                    'server' => $settings['server'],
                    'uri' => @$settings['uri'],
                    'charset' => 'utf8'
                ];
            }
        }
    }

}