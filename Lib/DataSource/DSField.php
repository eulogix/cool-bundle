<?php

/*
 * This file is part of the Eulogix\Cool package.
 *
 * (c) Eulogix <http://www.eulogix.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace Eulogix\Cool\Lib\DataSource;

use Eulogix\Cool\Lib\File\FileRepositoryInterface;

/**
 * @author Pietro Baricco <pietro@eulogix.com>
 */

class DSField
{
    const MACRO_TYPE_NUMERIC = 'numeric';
    const MACRO_TYPE_DATETIME = 'datetime';
    const MACRO_TYPE_STRING = 'string';
    const MACRO_TYPE_BOOLEAN = 'bool';

    /**
     * @var string
     */
    private $name, $type, $controlType, $source;

    /**
     * @var ValueMapInterface
     */
    private $valueMap;

    /**
     * @var FileRepositoryInterface
     */
    private $fileRepository;

    /**
     * @var bool
     */
    private $isPk = false,
            $isAutoGenerated = false,
            $isReadOnly = false,
            $lazyFetch = false,
            $forbidLazyFetch = false, //if set, ignores the lazy fetch attribute and always fetches the field
            $isRequired = false,
            $isPkInSource = false; //utility property used to flag a field as being a PK in its source table

    private $defaultValue;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of this field.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $controlType
     * @return $this
     */
    public function setControlType($controlType)
    {
        $this->controlType = $controlType;
        return $this;
    }

    /**
     * @return string
     */
    public function getControlType()
    {
        return $this->controlType;
    }

    /**
     * valuemap is used for small datasets
     * @param ValueMapInterface $valueMap
     * @return $this
     */
    public function setValueMap(ValueMapInterface $valueMap)
    {
        $this->valueMap = $valueMap;
        return $this;
    }

    /**
     * @return ValueMapInterface
     */
    public function getValueMap()
    {
        return $this->valueMap;
    }

    /**
     * Returns the type of this field (int, string..).
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the type of this field.
     * @param string $type
     * @return DSField
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMacroType() {
        if( in_array($this->getType(),[
            \PropelColumnTypes::BIGINT,
            \PropelColumnTypes::DECIMAL,
            \PropelColumnTypes::DOUBLE,
            \PropelColumnTypes::FLOAT,
            \PropelColumnTypes::INTEGER,
            \PropelColumnTypes::NUMERIC,
            \PropelColumnTypes::REAL,
            \PropelColumnTypes::SMALLINT,
            \PropelColumnTypes::TINYINT
            ]) )
                return self::MACRO_TYPE_NUMERIC;

        if( in_array($this->getType(),[
            \PropelColumnTypes::CHAR,
            \PropelColumnTypes::VARCHAR,
            \PropelColumnTypes::LONGVARCHAR,
            ]) )
                return self::MACRO_TYPE_STRING;

        if( in_array($this->getType(),[
            \PropelColumnTypes::BOOLEAN,
            \PropelColumnTypes::BOOLEAN_EMU,
            ]) )
                return self::MACRO_TYPE_BOOLEAN;

        if( in_array($this->getType(),[
            \PropelColumnTypes::TIME,
            \PropelColumnTypes::TIMESTAMP,
            \PropelColumnTypes::DATE,
            \PropelColumnTypes::BU_DATE
            ]) )
                return self::MACRO_TYPE_DATETIME;

        return null;
    }

    /**
     * Returns true if this field value is auto-generated by data base or ORM provider, false otherwise.
     * @return bool
     */
    public function isAutoGenerated() {
        return $this->isAutoGenerated;
    }

    /**
     * @param boolean $isAutoGenerated
     * @return $this
     */
    public function setIsAutoGenerated($isAutoGenerated)
    {
        $this->isAutoGenerated = $isAutoGenerated;
        return $this;
    }

    /**
     * a lazy fetch field is not fetched in bulk operations
     * @param boolean $lazyFetch
     * @return $this
     */
    public function setLazyFetch($lazyFetch)
    {
        $this->lazyFetch = !$this->isForbidLazyFetch() && $lazyFetch;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getLazyFetch()
    {
        return $this->isPkInSource() || $this->isPrimaryKey() ? false : $this->lazyFetch;
    }

    /**
     * @return boolean
     */
    public function isForbidLazyFetch()
    {
        return $this->forbidLazyFetch;
    }

    /**
     * @param boolean $forbidLazyFetch
     * @return $this
     */
    public function setForbidLazyFetch($forbidLazyFetch)
    {
        $this->forbidLazyFetch = $forbidLazyFetch;
        return $this;
    }

    /**
     * @param boolean $isRequired
     * @return self
     */
    public function setIsRequired($isRequired)
    {
        $this->isRequired = $isRequired;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->isRequired;
    }

    /**
     * @param boolean $readOnly
     * @return self
     */
    public function setIsReadOnly($readOnly)
    {
        $this->isReadOnly = $readOnly;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isReadOnly()
    {
        return $this->isReadOnly;
    }

    /**
     * @return boolean
     */
    public function isPrimaryKey()
    {
        return $this->isPk;
    }

    /**
     * @param boolean $bool
     * @return DSField
     */
    public function setIsPrimaryKey($bool)
    {
        $this->isPk = $bool ? $bool : false;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isPkInSource()
    {
        return $this->isPkInSource;
    }

    /**
     * @param boolean $isPkInSource
     * @return $this
     */
    public function setIsPkInSource($isPkInSource)
    {
        $this->isPkInSource = $isPkInSource;
        return $this;
    }

    /**
     * @param mixed $defaultValue
     * @return $this
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param FileRepositoryInterface $fileRepository
     * @return $this
     */
    public function setFileRepository($fileRepository)
    {
        $this->fileRepository = $fileRepository;

        return $this;
    }

    /**
     * @return FileRepositoryInterface
     */
    public function getFileRepository()
    {
        return $this->fileRepository;
    }

    /**
     * this is a generic utility field that can be used to set the source of the field in compound datasources
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param mixed $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

}