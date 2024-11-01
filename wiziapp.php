<?php
/*
* Plugin Name: Wiziapp
* Description: Create a stunning native Android App with Unlimited push notification & be available on the Google Play Store!
* Author: WiziApp Solutions Ltd.
* Version: 4.3.5
* Author URI: http://www.wiziapp.com/
*/

require_once(dirname(__FILE__).'/modules/app_config.php');
require_once(dirname(__FILE__).'/modules/admin.php');
require_once(dirname(__FILE__).'/modules/android.php');
require_once(dirname(__FILE__).'/modules/ios.php');
require_once(dirname(__FILE__).'/modules/ipad.php');
require_once(dirname(__FILE__).'/modules/html5.php');
require_once(dirname(__FILE__).'/modules/push.php');
require_once(dirname(__FILE__).'/modules/theme_purchase.php');
require_once(dirname(__FILE__).'/modules/pages.php');
require_once(dirname(__FILE__).'/modules/monetization.php');
require_once(dirname(__FILE__).'/modules/bundle.php');
require_once(dirname(__FILE__).'/modules/multisite.php');
require_once(dirname(__FILE__).'/modules/customize.php');
require_once(dirname(__FILE__).'/modules/compatibility.php');
     
function startsWith($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}
