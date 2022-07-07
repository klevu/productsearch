<?php

namespace Klevu\Search\Service\Account\Model;

use Klevu\Search\Api\Service\Account\Model\AccountDetailsInterface;

class AccountDetails implements AccountDetailsInterface
{
    const PLATFORM_MAGENTO = 'magento';

    /**
     * @var string
     */
    private $company;
    /**
     * @var string
     */
    private $email;
    /**
     * @var bool
     */
    private $active;
    /**
     * @var string
     */
    private $platform;
    /**
     * @var string
     */
    private $analyticsUrl;
    /**
     * @var string
     */
    private $catNavUrl;
    /**
     * @var string
     */
    private $catNavTrackingUrl;
    /**
     * @var string
     */
    private $indexingUrl;
    /**
     * @var string
     */
    private $jsUrl;
    /**
     * @var string
     */
    private $searchUrl;
    /**
     * @var string
     */
    private $tiersUrl;

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $company
     *
     * @return void
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getAnalyticsUrl()
    {
        return $this->analyticsUrl;
    }

    /**
     * @param string $analyticsUrl
     *
     * @return void
     */
    public function setAnalyticsUrl($analyticsUrl)
    {
        $this->analyticsUrl = $analyticsUrl;
    }

    /**
     * @return string
     */
    public function getCatNavUrl()
    {
        return $this->catNavUrl;
    }

    /**
     * @param string $catNavUrl
     *
     * @return void
     */
    public function setCatNavUrl($catNavUrl)
    {
        $this->catNavUrl = $catNavUrl;
    }

    /**
     * @return string
     */
    public function getCatNavTrackingUrl()
    {
        return $this->catNavTrackingUrl;
    }

    /**
     * @param string $catNavTrackingUrl
     *
     * @return void
     */
    public function setCatNavTrackingUrl($catNavTrackingUrl)
    {
        $this->catNavTrackingUrl = $catNavTrackingUrl;
    }

    /**
     * @return string
     */
    public function getIndexingUrl()
    {
        return $this->indexingUrl;
    }

    /**
     * @param string $indexingUrl
     *
     * @return void
     */
    public function setIndexingUrl($indexingUrl)
    {
        $this->indexingUrl = $indexingUrl;
    }

    /**
     * @return string
     */
    public function getJsUrl()
    {
        return $this->jsUrl;
    }

    /**
     * @param string $jsUrl
     *
     * @return void
     */
    public function setJsUrl($jsUrl)
    {
        $this->jsUrl = $jsUrl;
    }

    /**
     * @return string
     */
    public function getSearchUrl()
    {
        return $this->searchUrl;
    }

    /**
     * @param string $searchUrl
     *
     * @return void
     */
    public function setSearchUrl($searchUrl)
    {
        $this->searchUrl = $searchUrl;
    }

    /**
     * @return string
     */
    public function getTiersUrl()
    {
        return $this->tiersUrl;
    }

    /**
     * @param string $tiersUrl
     *
     * @return void
     */
    public function setTiersUrl($tiersUrl)
    {
        $this->tiersUrl = $tiersUrl;
    }

    /**
     * @return bool
     */
    public function getActive()
    {
        return (bool)$this->active;
    }

    /**
     * @param bool $active
     *
     * @return void
     */
    public function setActive($active)
    {
        $this->active = (bool)$active;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param string $platform
     *
     * @return void
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
    }

    /**
     * @return bool
     */
    public function isAccountActive()
    {
        return $this->active;
    }

    /**
     * @return bool
     */
    public function isPlatformMagento()
    {
        return $this->platform === static::PLATFORM_MAGENTO;
    }
}
