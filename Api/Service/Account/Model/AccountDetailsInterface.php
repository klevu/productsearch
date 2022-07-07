<?php

namespace Klevu\Search\Api\Service\Account\Model;

interface AccountDetailsInterface
{
    /**
     * @return string
     */
    public function getCompany();

    /**
     * @param string $company
     *
     * @return void
     */
    public function setCompany($company);

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @param string $email
     *
     * @return void
     */
    public function setEmail($email);

    /**
     * @return string
     */
    public function getAnalyticsUrl();

    /**
     * @param string $analyticsUrl
     *
     * @return void
     */
    public function setAnalyticsUrl($analyticsUrl);

    /**
     * @return string
     */
    public function getCatNavUrl();

    /**
     * @param string $catNavUrl
     *
     * @return void
     */
    public function setCatNavUrl($catNavUrl);

    /**
     * @return string
     */
    public function getCatNavTrackingUrl();

    /**
     * @param string $catNavUrl
     *
     * @return void
     */
    public function setCatNavTrackingUrl($catNavUrl);

    /**
     * @return string
     */
    public function getIndexingUrl();

    /**
     * @param string $indexingUrl
     *
     * @return void
     */
    public function setIndexingUrl($indexingUrl);

    /**
     * @return string
     */
    public function getJsUrl();

    /**
     * @param string $jsUrl
     *
     * @return void
     */
    public function setJsUrl($jsUrl);

    /**
     * @return string
     */
    public function getSearchUrl();

    /**
     * @param string $searchUrl
     *
     * @return void
     */
    public function setSearchUrl($searchUrl);

    /**
     * @return string
     */
    public function getTiersUrl();

    /**
     * @param string $tiersUrl
     *
     * @return void
     */
    public function setTiersUrl($tiersUrl);

    /**
     * @return bool
     */
    public function getActive();

    /**
     * @param bool $active
     *
     * @return void
     */
    public function setActive($active);

    /**
     * @return string
     */
    public function getPlatform();

    /**
     * @param string $platform
     *
     * @return void
     */
    public function setPlatform($platform);

    /**
     * @return bool
     */
    public function isAccountActive();

    /**
     * @return bool
     */
    public function isPlatformMagento();
}
