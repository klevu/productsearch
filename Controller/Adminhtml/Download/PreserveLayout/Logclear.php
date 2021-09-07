<?php

namespace Klevu\Search\Controller\Adminhtml\Download\PreserveLayout;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Logger\Controller\Adminhtml\AbstractLogClear;

class Logclear extends AbstractLogClear
{
    const ADMIN_RESOURCE = LoggerConstants::ADMIN_RESOURCE_CONFIGURATION;
}
