<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['id']) && isset($_GET['mode']) && (isset($_POST['FCKeditor1']) || isset($_POST['FCKeditor2']) || isset($_POST['FCKeditor3']))) {
    if ($user->check_user_rules('edit')) {
   
        $producer_id = (int)$_GET['id'];
        $mode = $_GET['mode'];

        if (isset($_POST['FCKeditor1'])) {
            $description = $_POST['FCKeditor1'];
            $result = mysql_query("update shop_cat_producers set description='$description' where producer_id=$producer_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$producer_id&mode=$mode&message=db"); exit();}
        }

        if (isset($_POST['FCKeditor2'])) {
            $description_full = $_POST['FCKeditor2'];
            $result = mysql_query("update shop_cat_producers set description_full='$description_full' where producer_id=$producer_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$producer_id&mode=$mode&message=db"); exit();}
        }

        if (isset($_POST['FCKeditor3'])) {
            $description_extra = $_POST['FCKeditor3'];
            $result = mysql_query("update shop_cat_producers set description_extra='$description_extra' where producer_id=$producer_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$producer_id&mode=$mode&message=db"); exit();}
        }

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();

        Header("Location: ".$_SERVER['PHP_SELF']."?id=$producer_id&mode=$mode"); exit();
    } else $user->no_rules('edit');
}

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id'])) {
    if ($user->check_user_rules('view')) {
   
        $producer_id = (int)$_GET['id'];

        $result = mysql_query("select * from shop_cat_producers where producer_id = $producer_id");
        if (!$result) exit();
        $row = mysql_fetch_array($result);

        $name = $row['producer_name'];
        $description = $row['description'];
        $description_full = $row['description_full'];
        $description_extra = $row['description_extra'];
        
        echo '<h2>'.htmlspecialchars($name).'</h2>';

        $tabs = new Tabs;
        if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_producer_descr.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_producer_descr.php?id='.$producer_id.'&mode=brief', 'Краткое описание', ((preg_match('/brief/', $_SERVER['REQUEST_URI'])) ? 1 : 0));
        if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_producer_descr.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_producer_descr.php?id='.$producer_id.'&mode=full', 'Подробное описание', ((preg_match('/full/', $_SERVER['REQUEST_URI'])) ? 1 : 0));
        if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_producer_descr.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_producer_descr.php?id='.$producer_id.'&mode=extra', 'Дополнительное описание', ((preg_match('/extra/', $_SERVER['REQUEST_URI'])) ? 1 : 0));
        $tabs->show_tabs();

        if (isset($_GET['message'])) {
            $message = new Message;
            $message->get_message($_GET['message']);
        }

        if (isset($_GET['mode']) && $_GET['mode']!=='') {
            if ($_GET['mode'] == 'brief') {

                echo '<form action="?id='.$producer_id.'&mode=brief" method="post">';
                $oFCKeditor = new FCKeditor('FCKeditor1') ;
                $oFCKeditor->BasePath = '/admin/fckeditor/';
                $oFCKeditor->ToolbarSet = 'Main' ;
                $oFCKeditor->Value = $description;
                $oFCKeditor->Width  = '100%' ;
                $oFCKeditor->Height = '410' ;
                $oFCKeditor->Create() ;
                echo'<br /><br /><button type="SUBMIT">Сохранить</button></form>';
            }//full
 
            if ($_GET['mode'] == 'full') {
                echo '<form action="?id='.$producer_id.'&mode=full" method="post">';
                $oFCKeditor = new FCKeditor('FCKeditor2') ;
                $oFCKeditor->BasePath = '/admin/fckeditor/';
                $oFCKeditor->ToolbarSet = 'Main' ;
                $oFCKeditor->Value = $description_full;
                $oFCKeditor->Width  = '100%' ;
                $oFCKeditor->Height = '410' ;
                $oFCKeditor->Create() ;
                echo'<br /><br /><button type="SUBMIT">Сохранить</button></form>';
            }//full

            if ($_GET['mode'] == 'extra') {
                echo '<form action="?id='.$producer_id.'&mode=extra" method="post">';
                $oFCKeditor = new FCKeditor('FCKeditor3') ;
                $oFCKeditor->BasePath = '/admin/fckeditor/';
                $oFCKeditor->ToolbarSet = 'Main' ;
                $oFCKeditor->Value = $description_extra;
                $oFCKeditor->Width  = '100%' ;
                $oFCKeditor->Height = '410' ;
                $oFCKeditor->Create() ;
                echo'<br /><br /><button type="SUBMIT">Сохранить</button></form>';
            }//full
 
        }//mode

    } else $user->no_rules('view');
} else
    echo '<span class="red">Ошибка запуска функции!</span>';

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>