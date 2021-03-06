<?php

/*
 * This file is part of the Eulogix\Cool package.
 *
 * (c) Eulogix <http://www.eulogix.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace Eulogix\Cool\Lib;

use Eulogix\Cool\Lib\Factory\Factory;
use Eulogix\Cool\Lib\Security\CoolUser;
use PDO;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Eulogix\Cool\Lib\Database\Schema;

/**
 * @author Pietro Baricco <pietro@eulogix.com>
 */

class Cool {

    /**
     * @var Schema[] The cache of databases
     */
    private $schemas = array();
    
    /**
    * 
    * @var ContainerInterface
    */
    private $container;

    /**
     * @var integer
     */
    private $execGuid;

    /**
     * @var \PDO The pdo connection
     */
    private  $PDOconnection;


    protected function __construct() {}

    /**
     * @return Cool
     */
    public static function getInstance() {
        static $instance = null;
        if ($instance === null)
            $instance = new Cool();
        return $instance;
    }

    /**
    * @param mixed $schemaName
    * @returns Schema
    * @throws \Exception
    */
    public function getSchema($schemaName = null) {
        if ($schemaName === null) {
            throw new \Exception("schema_name is null!");
        }
        
        $names = $this->getAvailableSchemaNames();
        if(!in_array($schemaName,$names)) {
            throw new \Exception("schema $schemaName is not available!");
        }
            
        if (!isset($this->schemas[$schemaName])) {
            if($attachedTo = $this->getAttachedToSchemaName($schemaName)) {
                return $this->getSchema($attachedTo);
            } else if( $db_namespace = $this->getSchemaNamespace($schemaName) ) {
                $class = $db_namespace."\\Schema";
                if(class_exists($class)) { 
                    $this->schemas[$schemaName] = new $class($schemaName,$db_namespace);
                } else throw new \Exception("class $class is not available! Build the dictionary before including it in the app config");
            } else throw new \Exception("namespace not found for $schemaName!");
        }

        return $this->schemas[$schemaName];
    }

    /**
     * returns the Cool core database
     *
     * @returns \Eulogix\Cool\Bundle\CoreBundle\Model\Core\Schema
     */
    public function getCoreSchema() {
        return $this->getSchema('core');
    }
           
    /**
    * @return ContainerInterface
    */
    public function getContainer() {
        return $this->container;
    }    
    
    /**
    * @param ContainerInterface $container
    */
    public function setContainer($container) {
        $this->container = $container;
    }
    
    /**
    * @return Factory
    */
    public function getFactory() {
        return $this->getContainer()->get('cool.factory');
    }

    /**
     * @return array
     */
    private function getGlobalConfiguration() {
        return $this->getContainer()->getParameter('eulogix_cool_config');
    }

    /**
     * returns a uniquely generated uid for the current execution context.
     * @return string
     */
    public function getExecutionGuid() {
        if(!$this->execGuid)
            $this->execGuid = uniqid();
        return $this->execGuid;
    }

    /**
     * @return null|CoolUser
     */
    public function getLoggedUser() {
        $u = $this->getFactory()->getUserManager()->getLoggedUser();
        if($u instanceof CoolUser)
            return $u;
        return null;
    }

    /**
     * Gets the namespace in which Cool looks for Dictionary and Database classes
     *
     * @param $schemaName
     * @return string|null
     */
    public function getSchemaNamespace($schemaName) {
        return $this->getAvailableSchemas()[$schemaName]['namespace'] ?? null;
    }

    /**
     * returns an array of names of available connections
     * @return string[]
     */
    public function getAvailableConnectionNames() {
        $cnf = \Propel::getConfiguration();
        $datasources = array_keys( $cnf['datasources'] );
        $k = array_search('default',$datasources);
        if($k!==FALSE)
            unset($datasources[$k]);
        return $datasources;
    }

    /**
     * @return array
     */
    public function getAvailableSchemas() {
        return $this->getGlobalConfiguration()['schemas'];
    }

    /**
     * @return array
     */
    public function getAvailableSchemaNames()
    {
        return array_keys($this->getAvailableSchemas());
    }

    /**
     * @return \PropelPDO
     */
    public function getConnection() {
        if(!$this->PDOconnection) {
            $this->PDOconnection = \Propel::getConnection();
            //$this->PDOconnection->useDebug(false);
        }
        return $this->PDOconnection;
    }

    public function initSchemas() {
        $schemas = $this->getAvailableSchemaNames();
        foreach($schemas as $schemaName) {
            try {
                $schema = $this->getSchema($schemaName);
                $schema->init();
            } catch(\Exception $e) {}
        }
    }

    /**
     * refreshes the search path of the current connection using the active schema names
     */
    public function refreshSearchPaths() {
        $paths = ['public'];
        $normalSchemas = [];
        $mtSchemas = [];
        $schemas = $this->getAvailableSchemaNames();
        foreach($schemas as $schemaName) {
            $schema = $this->getSchema($schemaName);
            if(!$schema->isMultiTenant())
                $normalSchemas[] = $schema->getCurrentSchema();
            else $mtSchemas[] = $schema->getCurrentSchema();
        }
        $paths = array_merge($paths, $mtSchemas, $normalSchemas);
        $searchPath = implode(',',$paths);
        $propelConf = \Propel::getConfiguration();
        foreach($propelConf['datasources'] as $dsName => $settings) {
            $con = \Propel::getConnection($dsName == 'default' ? null : $dsName);
            c_query("SELECT set_config('search_path', '{$searchPath}', false);", $con);
        }
    }

    public function getCurrentSchemas() {
        $ret = [];
        $schemas = $this->getAvailableSchemaNames();
        foreach($schemas as $schemaName) {
            $ret[ $schemaName ] = $this->getSchema($schemaName)->getCurrentSchema();
        }
        return $ret;
    }

    /**
     * @param array $schemas
     * @throws \Exception
     */
    public function setCurrentSchemas(array $schemas) {
        foreach($schemas as $schemaName => $currentSchema) {
            if($this->getSchema($schemaName)->isMultiTenant())
                $this->getSchema($schemaName)->setCurrentSchema($currentSchema);
        }
    }

    /**
     * @return array
     */
    public function getCurrentSearchPaths() {
        $ret = [];
        $propelConf = \Propel::getConfiguration();
        foreach($propelConf['datasources'] as $dsName => $settings) {
            $con = \Propel::getConnection($dsName == 'default' ? null : $dsName);
            $ret[$dsName] = c_fetch("SELECT current_setting('search_path')", $con);
        }
        return $ret;
    }

    /**
     * closes the writes to the session files, allowing multiple simultaneous requests from the same client
     */
    public function freeSession() {
        $this->getFactory()->getSession()->save();
    }

    /**
     * @param string $schemaName
     * @return \string[]
     */
    public function getSchemaNamesAttachedTo($schemaName) {
        $schemas = $this->getAvailableSchemas();
        $ret = [];
        foreach($schemas as $configSchemaName => $schemaConfig)
            if(($schemaConfig['attach_to'] ?? null) == $schemaName)
                $ret[] = $configSchemaName;
        return $ret;
    }

    /**
     * @param string $schemaName
     * @return \string[]
     */
    public function getAttachedToSchemaName($schemaName) {
        return $this->getAvailableSchemas()[ $schemaName ]['attach_to'] ?? false;
    }

    /**
     * captures the execution environment (eg in a web session) in order to restore it typically in a command
     * in order to simplify the development of asynchronous operations
     * @return array
     */
    public function getExcutionEnvironment() {
        return [
            'securityToken' => $this->getContainer()->get('security.token_storage')->getToken(),
            'sessionData' => $this->getFactory()->getSession()->all(),
            'currentSchemas' => $this->getCurrentSchemas(),
        ];
    }

    /**
     * restores a previously captured execution environment
     * @param array $executionEnvironment
     * @return array
     */
    public function restoreExcutionEnvironment(array $executionEnvironment) {
        if($securityToken = @$executionEnvironment['securityToken'])
            $this->getContainer()->get('security.token_storage')->setToken($securityToken);

        if($sessionData = @$executionEnvironment['sessionData']) {
            unset($sessionData['_security_secured_area']);
            $this->getFactory()->getSession()->replace($sessionData);
        }

        if($currentSchemas = @$executionEnvironment['currentSchemas'])
            $this->setCurrentSchemas($currentSchemas);
    }

}