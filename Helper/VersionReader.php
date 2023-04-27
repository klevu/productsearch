<?php

namespace Klevu\Search\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Config\Composer\Package;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader as ModuleReader;

class VersionReader extends AbstractHelper
{
    /**
     * @var string
     */
    const KLEVU_FILE_NAME = 'composer.json';
    /**
     * @var ModuleReader
     */
    private $moduleReader;
    /**
     * @var Dir
     */
    protected $moduleDirs;
    /**
     * @var ReadFactory
     */
    protected $readFactory;

    /**
     * VersionReader constructor.
     *
     * @param Dir $moduleDirs
     * @param ModuleReader $moduleReader
     * @param ReadFactory $readFactory
     */
    public function __construct(
        Dir $moduleDirs,
        ModuleReader $moduleReader,
        ReadFactory $readFactory
    ) {
        $this->moduleReader = $moduleReader;
        $this->moduleDirs = $moduleDirs;
        $this->readFactory = $readFactory;
    }

    /**
     * Returns module directory
     *
     * @param string $moduleName
     *
     * @return string
     */
    public function getModuleDirectory($moduleName)
    {
        return $this->moduleDirs->getDir($moduleName);
    }

    /**
     * Get composer based version info
     *
     * @param string $moduleName
     *
     * @return false|mixed|string
     */
    public function getVersionString($moduleName)
    {
        try {
            $version = 'unavailable';
            $path = $this->getModuleDirectory($moduleName) . '/' . self::KLEVU_FILE_NAME;
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            $composerObj = json_decode(file_get_contents($path));
            //check if composer.json is valid or not
            if (!is_object($composerObj) && !$composerObj instanceof \stdClass) {
                return $version;
            }
            $composerPkg = new Package($composerObj);
            if ($composerPkg->get('version')) {
                return $composerPkg->get('version');
            }
            // through native if above one not loaded, check obj type and array
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            $composerData = json_decode(file_get_contents($path), true);
            if (is_array($composerData) && !empty($composerData['version'])) {
                $version = $composerData['version'];

                return (string)$version;
            }
        } catch (\Exception $e) {
            return $version;
        }

        return $version;
    }
}
