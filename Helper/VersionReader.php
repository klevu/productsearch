<?php

namespace Klevu\Search\Helper;


use Magento\Framework\Config\Composer\Package;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader as ModuleReader;

/**
 * Class VersionReader
 * @package Klevu\Search\Helper
 */
class VersionReader extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var ModuleReader
     */
    private $moduleReader;

    /**
     * File name
     *
     * @var string
     */
    const KLEVU_FILE_NAME = 'composer.json';

    /**
     * VersionReader constructor.
     * @param Dir $moduleDirs
     * @param ModuleReader $moduleReader
     * @param Filesystem\Directory\ReadFactory $readFactory
     */
    public function __construct(
        Dir $moduleDirs,
        ModuleReader $moduleReader,
        Filesystem\Directory\ReadFactory $readFactory
    )
    {
        $this->moduleReader = $moduleReader;
        $this->moduleDirs = $moduleDirs;
        $this->readFactory = $readFactory;

    }

    /**
     * Returns module directory
     *
     * @param $moduleName
     * @return string
     */
    public function getModuleDirectory($moduleName)
    {
        return $this->moduleDirs->getDir($moduleName);
    }

    /**
     * Get composer based version info
     *
     * @param $moduleName
     * @return false|mixed|string
     */
    public function getVersionString($moduleName)
    {
        try {
            $version = 'unavailable';
            $path = $this->getModuleDirectory($moduleName) . '/' . self::KLEVU_FILE_NAME;
            $composerObj = json_decode(file_get_contents($path));
            //check if composer.json is valid or not
            if (gettype($composerObj) !== 'object' && !$composerObj instanceof \stdClass) {
                return $version;
            }
            $composerPkg = new Package($composerObj);
            if ($composerPkg->get('version')) {
                return $composerPkg->get('version');
            } else {
                //through native if above one not loaded, check obj type and array
                $composerData = json_decode(file_get_contents($path), true);
                if (gettype($composerData) === 'array' && !empty($composerData['version'])) {
                    $version = $composerData['version'];
                    return (string)$version;
                }
            }
        } catch (\Exception $e) {
            return $version;
        }
        return $version;
    }
}

