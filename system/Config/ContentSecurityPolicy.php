<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 17:17
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace YP\Config;

/**
 * Class ContentSecurityPolicy
 *
 * @package YP\Config
 */
class ContentSecurityPolicy
{
    public $reportOnly = false;

    public $defaultSrc = 'none';

    public $scriptSrc = 'self';

    public $styleSrc = 'self';

    public $imageSrc = 'self';

    public $base_uri = null;

    public $childSrc = null;

    public $connectSrc = 'self';

    public $fontSrc = null;

    public $formAction = null;

    public $frameAncestors = null;

    public $mediaSrc = null;

    public $objectSrc = null;

    public $pluginTypes = null;

    public $reportURI = null;

    public $sandbox = false;

    public $upgradeInsecureRequests = false;
}
