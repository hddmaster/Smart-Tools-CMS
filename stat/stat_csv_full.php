<?
header('Content-type: application/xls');
header('Content-Disposition: attachment; filename='.$_GET['type'].'.xls');
echo '<html>
		<head>
		<title></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<style>
			.number0 {mso-number-format:0;}
			.number2 {mso-number-format:Fixed;}
			td {
				border: 1px solid #555;
			}
		</style>
		</head>
		<body>';
echo '<table>';
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

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
        
        $stat->check_hits();
        $stat->check_uniques();

        //уникальные посетители
        if ($type == 'uniques') {
            $stat->check_uniques_full($hour1, $hour2);
            $stat->show_csv_uniques_full();
        }
        
        //рефереры
        if ($type == 'links') {
            $stat->check_links($hour1, $hour2);
            $stat->show_csv_links();
        }
        
        //вывод ключевых слов, если они определены
        if ($type == 'keywords') {
            $stat->check_links();
            $stat->check_keywords();
			if($hour1 != 00 || $hour2 != 24) {
				$stat->check_links($hour1, $hour2);
				$stat->check_keywords($hour1, $hour2);
			}
            $stat->show_csv_keywords();
        }
        
        //популярность страниц
        if ($type == 'pages') {
            $stat->check_pages();
			if($hour1 != 00 || $hour2 != 24)
				$stat->check_pages($hour1, $hour2);
            $stat->show_csv_pages();
        }
        
        //IPs адреса
        if ($type == 'ips') {
            $stat->check_ips();
			if($hour1 != 00 || $hour2 != 24)
				$stat->check_ips($hour1, $hour2);
            $stat->show_csv_ips();
        }
        
        //user_agents
        if ($type == 'useragents') {
            $stat->check_useragents();
			if($hour1 != 00 || $hour2 != 24)
				$stat->check_useragents($hour1, $hour2);
            $stat->show_csv_useragents();
        }
    }
 
} else $user->no_rules('view');

echo '</table>';
?>