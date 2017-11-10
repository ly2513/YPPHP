<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 17:17
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Config;

/**
 * Class ContentSecurityPolicy
 *
 * @package YP\Config
 */
class ContentSecurityPolicy {

    public $reportOnly = FALSE;

    public $defaultSrc = 'none';

    public $scriptSrc = 'self';

    public $styleSrc = 'self';

    public $imageSrc = 'self';

    public $base_uri = NULL;

    public $childSrc = NULL;

    public $connectSrc = 'self';

    public $fontSrc = NULL;

    public $formAction = NULL;

    public $frameAncestors = NULL;

    public $mediaSrc = NULL;

    public $objectSrc = NULL;

    public $pluginTypes = NULL;

    public $reportURI = NULL;

    public $sandbox = FALSE;

    public $upgradeInsecureRequests = FALSE;
}
