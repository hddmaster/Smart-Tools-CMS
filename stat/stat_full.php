<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if ($user->check_user_rules('view')) {

    if (isset($_GET['date1']) && $_GET['date1'] != '' &&
        isset($_GET['date2']) && $_GET['date2'] != '' &&
        isset($_GET['type']) && $_GET['type'] !== '' &&
		isset($_GET['hour1']) && $_GET['hour1'] != '' &&
        isset($_GET['hour2']) && $_GET['hour2'] != '') {
            
        $type = $_GET['type'];
        $date1 = ((isset($_GET['date1'])) ? $_GET['date1'] : date('Ymd').'000001');
        $date2 = ((isset($_GET['date2'])) ? $_GET['date2'] : date('Ymd').'235959');
        $stat = new Statistic($date1,$date2);
        $stat->orders = (isset($_GET['orders']) ? true : false);
        if ($stat->orders) $stat->get_order_users();
        $stat->admin = (isset($_GET['admin']) ? true : false);
        //if ($stat->admin)
        $stat->get_admin_users();
        
        // постраничный вывод
        $page = ((isset($_GET['page'])) ? $_GET['page']-1 : 0);
        $per_page = ((isset($_GET['per_page'])) ? $_GET['per_page'] : 20);
		
		$hour1 = ((isset($_GET['hour1'])) ? $_GET['hour1'] : 00);
		$hour2 = ((isset($_GET['hour2'])) ? $_GET['hour2'] : 24);
        
        $params = array();
        $params['date1'] = $date1;
        $params['date2'] = $date2;
		$params['hour1'] = $hour1;
        $params['hour2'] = $hour2;
        $params['type'] = $type;
        if ($stat->orders) $params['orders'] = 'true';
        if ($stat->admin) $params['admin'] = 'true';

        $stat->check_hits();
        $stat->check_uniques();
		
		echo '<div align="right"><a style="display: block; background: url(/admin/images/icons/document-excel.png) 0px 0px no-repeat; padding: 0px 0px 0px 20px; width: 91px; height: 20px;" href="/admin/stat/stat_csv_full.php?'.$_SERVER['QUERY_STRING'].'">Выгрузить в Excel</a></div>';

        //уникальные посетители
        if ($type == 'uniques') {
            $stat->check_uniques_full($hour1, $hour2);
            $stat->show_uniques_full($page, $per_page, $params);
        }
        
        //рефереры
        if ($type == 'links') {
            $stat->check_links($hour1, $hour2);
            $stat->show_links($page, $per_page, $params);
        }
        
        //вывод ключевых слов, если они определены
        if ($type == 'keywords') {
			$stat->check_links();
            $stat->check_keywords();
			if($hour1 != 00 || $hour2 != 24) {
				$stat->check_links($hour1, $hour2);
				$stat->check_keywords($hour1, $hour2);
			}
            $stat->show_keywords($page, $per_page, $params);
        }
        
        //популярность страниц
        if ($type == 'pages') {
            $stat->check_pages();
			if($hour1 != 00 || $hour2 != 24)
				$stat->check_pages($hour1, $hour2);
            $stat->show_pages($page, $per_page, $params);
        }
        
        //IPs адреса
        if ($type == 'ips') {
            $stat->check_ips();
			if($hour1 != 00 || $hour2 != 24)
				$stat->check_ips($hour1, $hour2);
            $stat->show_ips($page, $per_page, $params);
        }
        
        //user_agents
        if ($type == 'useragents') {
            $stat->check_useragents();
			if($hour1 != 00 || $hour2 != 24)
				$stat->check_useragents($hour1, $hour2);
            $stat->show_useragents($page, $per_page, $params);
        }
    }
 
} else $user->no_rules('view');

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>