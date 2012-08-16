<?
error_reporting(0);
ini_set('session.gc_maxlifetime', '18000');
$SERVER_PORT = 80;
$_SERVER['SERVER_PORT'] = 80;

define ('SMART_TOOLS_PATH', '/admin');

//основые конфигурационные файлы
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/functions/settings.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/functions/version.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/functions/mysql.php');

//внутренние классы
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/class/cache_class.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/class/message_class.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/class/page_generate_class.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/class/search_class.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/class/statistic_class.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/class/url_parser_class.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/class/tab_class_alternative.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/class/lang_class.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/class/auth_site_class.php');

//внутренние функции
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/functions/pages.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/functions/resize.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/functions/use.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/functions/valid.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/functions/print_price_as_text.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/functions/st_file_get_contents.php');

//импорт внешних данных
//require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/modules/exchange/exchange.php');
//require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/modules/exchange/exchange_index.php');
//require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/modules/import_from_rss/import_news_from_rss.php');
 
//внешние классы
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/class/swift-4.1.7/lib/swift_required.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/class/pclzip/pclzip.lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/fckeditor/fckeditor.php');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/class/sphinx/sphinxapi.php');

//xajax
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/class/xajax/xajax_core/xajax.inc.php');
$xajax = new xajax(); // "/"
$xajax->configure('javascript URI', SMART_TOOLS_PATH.'/class/xajax/'); 
//$xajax->configure('responseType', 'JSON');
?>