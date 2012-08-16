<?
session_start();
// поддерживаются ли Cookies и JavaScript
$cookies = ((isset($_COOKIE['stcms']) && $_COOKIE['stcms'] == '1' && isset($_GET['cookies']) && $_GET['cookies'] == 'yes') ? true : false);

if (isset($_SERVER['HTTP_REFERER'])) {
    include ($_SERVER['DOCUMENT_ROOT'].'/admin/functions/config.php');
    include ($_SERVER['DOCUMENT_ROOT'].'/admin/class/auth_class.php');
    $stat_users = new Statistic;
    $link = ((isset($_GET['ref']) && trim($_GET['ref']) !== '' && $stat_users->is_link(trim($_GET['ref']))) ? trim($_GET['ref']) : '');
    $page = $_SERVER['HTTP_REFERER'];
    $useragent = $_SERVER['HTTP_USER_AGENT'];
    $ip = $_SERVER['REMOTE_ADDR'];

    $user_id = 0;
    if ($cookies) { //если поддерживаются cookies
        if (isset($_COOKIE['stcmsid'])) { //если в cookies хранится идентификатор пользователя
            $result = mysql_query("select * from stat_users where user_id = ".(int)$_COOKIE['stcmsid']);
            if (mysql_num_rows($result) > 0) {
                $row = mysql_fetch_array($result);
                $user_id = $row['user_id'];

                $user = new auth;
                $user->check_valid_user();

                $site_user = new auth_site;
                $site_user->check_valid_user();

                if ($user->user_id) mysql_query("update stat_users set admin_user_id = $user->user_id where user_id = $user_id");
                if ($site_user->user_id) mysql_query("update stat_users set site_user_id = $site_user->user_id where user_id = $user_id");
            }
       } else { //если идентификатор не установлен
            //проверка на ошибку с cookies (если уже есть такой пользователь)
            //+ склеивание пользователей с "подобными" характеристиками (ip, useragent, поддержка cookies)
            $result = mysql_query("select * from stat_users where ip = '$ip' and useragent = '$useragent' and cookies = 1");
            if (mysql_num_rows($result) > 0) {
                $row = mysql_fetch_array($result);
                $user_id = $row['user_id'];
            } else { //новый пользователь
                $res = mysql_query("insert into stat_users (date, ip, useragent, cookies) values (now(), '$ip', '$useragent', 1)");
                $user_id = mysql_insert_id();
            }

            //устанавливаем cookie на 10 лет
            setcookie('stcmsid', $user_id, time()+24*60*60*365*10, '/', '.'.DOMAIN);         
        }
    } else { //cookies отключены, уникальность по IP-адресу + User agent
        $result = mysql_query("select * from stat_users where ip = '$ip' and useragent = '$useragent' and cookies = 0");
        if (mysql_num_rows($result) > 0) { //такой ip - useragent уже есть
            $row = mysql_fetch_array($result);
            $user_id = $row['user_id'];
        } else { //новый пользователь
            $res = mysql_query("insert into stat_users (date, ip, useragent, cookies) values (now(), '$ip', '$useragent', 0)");
            $user_id = mysql_insert_id();
        }
    }
    
    //обсчет захода
    if ($user_id > 0) mysql_query("insert into stat_hits (user_id, date, page, link) values ($user_id, now(), '$page', '$link')");
}

    //отрисовка картинки баннера счетчика
    header("Content-type: image/png");
    
    $name = "Smart Tools CMS";
    $imageSX = 88;
    $imageSY = 31;
    
    $image = imagecreatetruecolor($imageSX, $imageSY);
    imageantialias($image, true);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image,0,0,0);
    $lightgrey = imagecolorallocate($image,250,250,250);
    $grey = imagecolorallocate($image,190,190,190);
    $darkgrey = imagecolorallocate($image,110,110,110);
    $red = imagecolorallocate($image,255,0,0);
    $green = imagecolorallocate($image,0,150,0);
    imagefilledrectangle($image, 0, 0, $imageSX, $imageSY, $grey);
    imagefilledrectangle($image, 1, 1, $imageSX-2, $imageSY-2, $lightgrey);
    imagefilledrectangle($image, 1, $imageSY-10, $imageSX-2, $imageSY-2, $grey);

    /*
    if (isset($_SERVER['HTTP_REFERER'])) {
        $stat_users->check_uniques();
        $stat_users->check_hits();
        imagestring($image, 1, 3, 3, $stat_users->total_uniques, $darkgrey);
        imagestring($image, 1, 3, 12, $stat_users->total_hits, $darkgrey);
    }
    */
  
    imagerectangle($image, $imageSX-7, 2, $imageSX-3, 6, $grey);
    imagerectangle($image, $imageSX-7-7, 2, $imageSX-3-7, 6, $grey);

    // Рисуем красный квадрат, если нет поддержки cookies
    if (!$cookies) imagerectangle($image, $imageSX-7, 2, $imageSX-3, 6, $red);
    if (isset($_COOKIE['stcms'])) imagerectangle($image, $imageSX-7-7, 2, $imageSX-3-7, 6, $green);
    
    imagestring($image, 1, 6, 22, $name, $darkgrey);
    imagepng($image);
?>