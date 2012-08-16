<?
 $xajax->processRequest();
 function microtime_float()
   {
     list($usec, $sec) = explode(" ", microtime());
     return ((float)$usec + (float)$sec);
   }
 define('PAGE_LOAD_TIME',microtime_float());
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<?php
// Конфигурация
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/page_class.php");
$page = new Page;
?>
<title><?=$page->page_title?></title>
<meta http-equiv="Content-Type" content="text/html" charset="utf-8">
<!-- CSS -->
<link rel="stylesheet" type="text/css" href="/admin/css/style.css">
<link rel="stylesheet" type="text/css" href="/admin/css/calendar.css">
<link rel="stylesheet" type="text/css" href="/admin/css/drag-drop-folder-tree.css">
<link rel="stylesheet" type="text/css" href="/admin/css/context-menu.css">
<link rel="stylesheet" type="text/css" href="/admin/css/qa.css">
<link rel="stylesheet" type="text/css" href="/admin/css/jquery.fancybox-1.2.6.css">
<link rel="stylesheet" type="text/css" href="/admin/css/trackbar.css">
<link rel="stylesheet" type="text/css" href="/admin/js/jquery/ui/development-bundle/themes/base/jquery.ui.all.css" rel="stylesheet" />
<!-- JavaScript -->
<script type="text/javascript" src="/admin/js/main.js"></script>
<script type="text/javascript" src="/admin/js/calendar.js"></script>
<script type="text/javascript" src="/admin/js/calendar-design.js"></script>
<script type="text/javascript" src="/admin/js/context-menu.js"></script>
<script type="text/javascript" src="/admin/js/drag-drop-folder-tree.js"></script>
<script type="text/javascript" src="/admin/js/qa.js"></script>
<script type="text/javascript" src="/admin/js/jquery/jquery.js"></script>
<script type="text/javascript" src="/admin/js/jquery/jquery.fancybox-1.2.6.pack.js"></script>
<script type="text/javascript">
$(document).ready(function() {
  $("a.zoom").fancybox({
    'zoomSpeedIn' : 500,
    'zoomSpeedOut': 500
  });
  $("a.iframe").fancybox({
    'frameWidth' : 850,
    'frameHeight': 500,
    'hideOnContentClick': false
  });
});
</script>
<script type="text/javascript" src="/admin/js/jquery/jquery.trackbar.js"></script>
<script type="text/javascript" src="/admin/js/jquery/jquery.corner.js"></script>
<script type="text/javascript">$('.h_menu, .h_menu_sel').corner("keep top 3px"); $('.databox').corner("keep 10px");</script>
<script type="text/javascript" src="/admin/editarea/edit_area/edit_area_full.js"></script>
<? $xajax->printJavascript(); ?>

<script type="text/javascript" src="/admin/js/jquery/ui/development-bundle/ui/jquery.ui.core.js"></script>
<script type="text/javascript" src="/admin/js/jquery/ui/development-bundle/ui/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="/admin/js/jquery/ui/development-bundle/ui/i18n/jquery.ui.datepicker-ru.js"></script>
<script type="text/javascript">
  $(function() {
  $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['ru']));
  $(".datepicker").datepicker($.datepicker.regional['ru']);
  });
</script>

</head>

<body>
<table width="100%" height="100%" cellpadding="0" cellspacing="0" border="0">

<tr><td width="100%" class="document">
 <table width="100%" cellpadding="0" cellspacing="0" border="0" class="top">
   <tr>
    <td align="center" nowrap id="border" class="top_main_td">
      <a href="/admin/admin.php"><img src="/admin/images/logo.gif" alt="Smart Tools CMS" border="0" vspace="1" hspace="20"></a><br/>
      <span class="transparent">система управления сайтом</span>
    </td>
    <td align="left" nowrap class="top_main_td">
      <?
        echo '<table cellspacing="0" cellpadding="0" style="margin-bottom: 4px;"><tr valign="top">';
        if (file_exists($_SERVER['DOCUMENT_ROOT'].'/favicon.ico') &&
            !preg_match('/msie/i', $_SERVER['HTTP_USER_AGENT'])) echo '<td nowrap style="padding-top: 2px;"><img src="/favicon.ico" alt="" width="16" height="16">&nbsp;&nbsp;</td>';
        echo '<td style="padding: 0px;" nowrap><a class="h3" target="_blank" href="http://'.$_SERVER['HTTP_HOST'].'">'.$_SERVER['HTTP_HOST'].'</a></td></tr></table>';
        echo '<span class="small"><strong>'.$user->username.'</strong> ('.$user->user_type_name.')</span>';
      ?>
    </td>
    <td align="center" nowrap width="100%" class="top_main_td">
      <?
         if ($user->get_cms_option('eco_mode') == 1) echo '<strong class="green">Режим экономии траффика</strong>';
         else echo '&nbsp;';
      ?>
    </td>
<?

if ($user->check_user_rules('view', '/admin/messages.php') ||
    $user->check_user_rules('view', '/admin/help.php') ||
    $user->check_user_rules('view', '/admin/help_options.php') ||
    $user->check_user_rules('view', '/admin/help_support.php') ||
    $user->check_user_rules('view', '/admin/settings.php'))
 {
   echo '<td class="top_main_td"><table cellspacing="4" cellpadding="8" border="0"><tr>';
     
   if ($user->check_user_rules('view', '/admin/messages.php'))
    {
      echo '<td align="center" class="top_menu_td"><table cellspacing="0" cellpadding="4"><tr><td><img src="/admin/images/icons/balloon.png" alt=""></td><td><a class="grey" href="/admin/messages.php">Сообщения</a></td></tr></table>
            <div class="green">';
      $result = mysql_query("select * from messages where to_user_id = $user->user_id and status = 0 ");
      if ($result && mysql_num_rows($result) > 0)
       {
         if (mysql_num_rows($result) == 1) echo '(<strong>1 новое</strong>)';
         if (mysql_num_rows($result) > 1) echo '(<strong>'.mysql_num_rows($result).' новых</strong>)';
       }
      echo '</div></td>';
    }
   if ($user->check_user_rules('view', '/admin/help.php') ||
       $user->check_user_rules('view', '/admin/help_options.php') ||
       $user->check_user_rules('view', '/admin/help_support.php')) echo '<td class="top_menu_td"><table cellspacing="0" cellpadding="4"><tr><td><img src="/admin/images/icons/lifebuoy.png" alt=""></td><td><a class="grey" href="/admin/help.php">Помощь</a></td></tr></table></td>';
   if ($user->check_user_rules('view', '/admin/settings.php')) echo '<td class="top_menu_td"><table cellspacing="0" cellpadding="4"><tr><td><img src="/admin/images/icons/wrench-screwdriver.png" alt=""></td><td><a class="grey" href="/admin/settings.php">Настройки</a></td></tr></table></td>';

   echo '<td class="top_menu_td"><table cellspacing="0" cellpadding="4"><tr><td><img src="/admin/images/icons/door-open-out.png" alt=""></td><td><a class="grey" href="javascript:if(confirm(\'Вы действительно хотите выйти из Smart Tools CMS?\')){location.href=\'/admin/logout.php\'}">Выход</a></td></tr></table></td>
     </tr>
    </table>
    </td>';
 }
?>
   </tr>
 </table>
</td></tr>

<tr valign="top"><td height="100%">
 <table width="100%" cellpadding="0" cellspacing="0" border="0">
   <tr valign="top">

<!-- menu -->
     <td nowrap class="menu_part">
<?

     echo '<table cellpadding="0" cellspacing="0">';

//------------------------------------------------------------------------------
     echo '<tr><td><img src="/admin/images/icons/home.png" alt=""></td><td width="100%" nowrap><a href="/admin/admin.php">Главная</a></td></tr>';
     echo '<tr><td colspan="2">&nbsp;</td></tr>';

     if ($user->check_user_rules('view', '/admin/structure.php') ||
         $user->check_user_rules('view', '/admin/modules.php') ||
         $user->check_user_rules('view', '/admin/content.php') ||
         $user->check_user_rules('view', '/admin/designs.php'))
     echo '<tr><td colspan="2"><strong>Сайт</strong></td></tr>';

     if ($user->check_user_rules('view', '/admin/structure.php'))
     echo '<tr><td><img src="/admin/images/icons/node.png" alt=""></td><td nowrap><a href="/admin/structure.php">Структура</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/content.php'))
     echo '<tr><td><img src="/admin/images/icons/edit.png" alt=""></td><td nowrap><a href="/admin/content.php">Тексты</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/modules.php'))
     echo '<tr><td><img src="/admin/images/icons/scripts.png" alt=""></td><td nowrap><a href="/admin/modules.php">Модули</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/designs.php'))
     echo '<tr><td><img src="/admin/images/icons/table.png" alt=""></td><td nowrap><a href="/admin/designs.php">Шаблоны страниц</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/structure.php') ||
         $user->check_user_rules('view', '/admin/modules.php') ||
         $user->check_user_rules('view', '/admin/content.php') ||
         $user->check_user_rules('view', '/admin/designs.php'))
     echo '<tr><td colspan="2">&nbsp;</td></tr>';

//------------------------------------------------------------------------------

     if ($user->check_user_rules('view', '/admin/news.php') ||
         $user->check_user_rules('view', '/admin/shop_catalog.php') ||
         $user->check_user_rules('view', '/admin/publications.php') ||
         $user->check_user_rules('view', '/admin/gallery.php') ||
         $user->check_user_rules('view', '/admin/video.php') ||
         $user->check_user_rules('view', '/admin/polls.php') ||
         $user->check_user_rules('view', '/admin/files.php') ||
         $user->check_user_rules('view', '/admin/guestbook.php') ||
         $user->check_user_rules('view', '/admin/links.php') ||
         $user->get_cms_option('menu_special_module'))
     echo '<tr><td colspan="2"><strong>Модули</strong></td></tr>';

     if ($user->get_cms_option('menu_special_module') && $user->check_user_rules('view', $user->get_cms_option('menu_special_module')))
     echo '<tr><td><img src="'.(($user->get_cms_option('menu_special_module_image')) ? $user->get_cms_option('menu_special_module_image') : '/admin/images/icons/block.png').'" alt=""></td><td nowrap><a href="'.str_replace('index.php', '', $user->get_cms_option('menu_special_module')).'">'.$user->get_cms_option_name('menu_special_module').'</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/news.php') ||
         $user->check_user_rules('view', '/admin/news_groups.php') ||
         $user->check_user_rules('view', '/admin/news_structure.php') ||
         $user->check_user_rules('view', '/admin/news_structure_elements.php') ||
         $user->check_user_rules('view', '/admin/news_import.php'))
     echo '<tr><td><img src="/admin/images/icons/newspaper.png" alt=""></td><td nowrap><a href="/admin/news.php">Новости</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/gallery.php') ||
         $user->check_user_rules('view', '/admin/gallery_groups.php') ||
         $user->check_user_rules('view', '/admin/gallery_stucture.php'))
     echo '<tr><td><img src="/admin/images/icons/camera.png" alt=""></td><td nowrap><a href="/admin/gallery.php">Галерея</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/video.php') ||
         $user->check_user_rules('view', '/admin/video_groups.php') ||
         $user->check_user_rules('view', '/admin/video_stucture.php'))
     echo '<tr><td><img src="/admin/images/icons/camcorder.png" alt=""></td><td nowrap><a href="/admin/video.php">Видео</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/shop_catalog.php') ||
         $user->check_user_rules('view', '/admin/shop_cat_groups.php') ||
         $user->check_user_rules('view', '/admin/shop_cat_structure.php') ||
         $user->check_user_rules('view', '/admin/shop_cat_grids.php') ||
         $user->check_user_rules('view', '/admin/shop_cat_sizes.php') ||
         $user->check_user_rules('view', '/admin/shop_cat_producers.php') ||
         $user->check_user_rules('view', '/admin/shop_cat_sites.php') ||
         $user->check_user_rules('view', '/admin/shop_incoming.php') ||
         $user->check_user_rules('view', '/admin/shop_incoming_all.php') ||
         $user->check_user_rules('view', '/admin/shop_outgoing.php') ||
         $user->check_user_rules('view', '/admin/shop_outgoing_all.php') ||
         $user->check_user_rules('view', '/admin/shop_places.php') ||
         $user->check_user_rules('view', '/admin/shop_sale.php') ||
         $user->check_user_rules('view', '/admin/shop_orders.php') ||
         $user->check_user_rules('view', '/admin/shop_payments.php') ||
         $user->check_user_rules('view', '/admin/shop_payment_options.php'))
     echo '<tr><td><img src="/admin/images/icons/present.png" alt=""></td><td nowrap><a href="/admin/shop_catalog.php">Магазин</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/publications.php') ||
         $user->check_user_rules('view', '/admin/pub_groups.php') ||
         $user->check_user_rules('view', '/admin/pub_structure.php') ||
         $user->check_user_rules('view', '/admin/pub_structure_elements.php') ||
         $user->check_user_rules('view', '/admin/pub_import.php'))
     echo '<tr><td><img src="/admin/images/icons/newspaper--pencil.png" alt=""></td><td nowrap><a href="/admin/publications.php">Публикации</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/polls.php'))
     echo '<tr><td><img src="/admin/images/icons/clipboard.png" alt=""></td><td nowrap><a href="/admin/polls.php">Опросы</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/files.php'))
     echo '<tr><td><img src="/admin/images/icons/disk.png" alt=""></td><td nowrap><a href="/admin/files.php">Файлы</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/guestbook.php') ||
         $user->check_user_rules('view', '/admin/guest_answ.php') ||
         $user->check_user_rules('view', '/admin/guest_edit.php'))
     echo '<tr><td><img src="/admin/images/icons/balloon.png" alt=""></td><td nowrap><a href="/admin/guestbook.php">Вопрос - ответ</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/forum.php') ||
         $user->check_user_rules('view', '/admin/forum_groups.php') ||
         $user->check_user_rules('view', '/admin/forum_stucture.php'))
     echo '<tr><td><img src="/admin/images/icons/balloons.png" alt=""></td><td nowrap><a href="/admin/forum.php">Форум</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/links.php') ||
         $user->check_user_rules('view', '/admin/link_cats.php') ||
         $user->check_user_rules('view', '/admin/forum_stucture.php'))
     echo '<tr><td><img src="/admin/images/icons/globe.png" alt=""></td><td nowrap><a href="/admin/links.php">Ссылки</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/news.php') ||
         $user->check_user_rules('view', '/admin/shop_catalog.php') ||
         $user->check_user_rules('view', '/admin/publications.php') ||
         $user->check_user_rules('view', '/admin/gallery.php') ||
         $user->check_user_rules('view', '/admin/video.php') ||
         $user->check_user_rules('view', '/admin/polls.php') ||
         $user->check_user_rules('view', '/admin/files.php') ||
         $user->check_user_rules('view', '/admin/guestbook.php') ||
         $user->check_user_rules('view', '/admin/links.php') ||
         $user->get_cms_option('menu_special_module'))
     echo '<tr><td colspan="2">&nbsp;</td></tr>';

//------------------------------------------------------------------------------
     if ($user->check_user_rules('view', '/admin/statistic.php') ||
         $user->check_user_rules('view', '/admin/stat_per_day.php') ||
         $user->check_user_rules('view', '/admin/stat_global.php') ||
         $user->check_user_rules('view', '/admin/stat_ips.php') ||
         $user->check_user_rules('view', '/admin/distribution.php') ||
         $user->check_user_rules('view', '/admin/distr_msg.php') ||
         $user->check_user_rules('view', '/admin/distr_users.php') ||
         $user->check_user_rules('view', '/admin/distr_reg_users.php') ||
         $user->check_user_rules('view', '/admin/manager.php') ||
         $user->check_user_rules('view', '/admin/advertising.php'))
     echo '<tr><td colspan="2"><strong>Инструменты</strong></td></tr>';

     if ($user->check_user_rules('view', '/admin/advertising.php'))
     echo '<tr><td><img src="/admin/images/icons/target.png" alt=""></td><td nowrap><a href="/admin/advertising.php">Реклама</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/statistic.php') ||
         $user->check_user_rules('view', '/admin/stat_per_day.php') ||
         $user->check_user_rules('view', '/admin/stat_global.php') ||
         $user->check_user_rules('view', '/admin/stat_ips.php'))
     echo '<tr><td><img src="/admin/images/icons/chart.png" alt=""></td><td nowrap><a href="/admin/statistic.php">Статистика</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/distribution.php') ||
         $user->check_user_rules('view', '/admin/distr_msg.php') ||
         $user->check_user_rules('view', '/admin/distr_users.php') ||
         $user->check_user_rules('view', '/admin/distr_reg_users.php') ||
         $user->check_user_rules('view', '/admin/distr_reg.php'))
     echo '<tr><td><img src="/admin/images/icons/mails.png" alt=""></td><td nowrap><a href="/admin/distribution.php">Рассылка сообщений</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/manager.php'))
     echo '<tr><td><img src="/admin/images/icons/folder-horizontal-open.png" alt=""></td><td nowrap><a href="/admin/manager.php">Файловый менеджер</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/statistic.php') ||
         $user->check_user_rules('view', '/admin/stat_ips.php') ||
         $user->check_user_rules('view', '/admin/distribution.php') ||
         $user->check_user_rules('view', '/admin/distr_msg.php') ||
         $user->check_user_rules('view', '/admin/distr_users.php') ||
         $user->check_user_rules('view', '/admin/distr_reg_users.php') ||
         $user->check_user_rules('view', '/admin/manager.php') ||
         $user->check_user_rules('view', '/admin/advertising.php'))
     echo '<tr><td colspan="2">&nbsp;</td></tr>';

//------------------------------------------------------------------------------
     if ($user->check_user_rules('view', '/admin/reserv.php') ||
         $user->check_user_rules('view', '/admin/sql.php') ||
         $user->check_user_rules('view', '/admin/cache.php') ||
         $user->check_user_rules('view', '/admin/auth.php') ||
         $user->check_user_rules('view', '/admin/auth_users.php') ||
         $user->check_user_rules('view', '/admin/auth_rules.php') ||
         $user->check_user_rules('view', '/admin/auth_scripts.php') ||
         $user->check_user_rules('view', '/admin/auth_history.php') ||
         $user->check_user_rules('view', '/admin/auth_site.php') ||
         $user->check_user_rules('view', '/admin/auth_site_users.php') ||
         $user->check_user_rules('view', '/admin/auth_site_rules.php') ||
         $user->check_user_rules('view', '/admin/auth_site_scripts.php') ||
         $user->check_user_rules('view', '/admin/auth_site_history.php'))
     echo '<tr><td colspan="2"><strong>Администрирование</strong></td></tr>';

     if ($user->check_user_rules('view', '/admin/auth.php') ||
         $user->check_user_rules('view', '/admin/auth_users.php') ||
         $user->check_user_rules('view', '/admin/auth_rules.php') ||
         $user->check_user_rules('view', '/admin/auth_scripts.php') ||
         $user->check_user_rules('view', '/admin/auth_history.php'))
     echo '<tr><td><img src="/admin/images/icons/gear.png" alt=""></td><td nowrap><a href="/admin/auth.php">Система</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/auth_site.php') ||
         $user->check_user_rules('view', '/admin/auth_site_users.php') ||
         $user->check_user_rules('view', '/admin/auth_site_rules.php') ||
         $user->check_user_rules('view', '/admin/auth_site_scripts.php') ||
         $user->check_user_rules('view', '/admin/auth_site_history.php'))
     echo '<tr><td><img src="/admin/images/icons/users.png" alt=""></td><td nowrap><a href="/admin/auth_site.php">Пользователи сайта</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/reserv.php') ||
         $user->check_user_rules('view', '/admin/sql.php'))
     echo '<tr><td><img src="/admin/images/icons/database.png" alt=""></td><td nowrap><a href="/admin/sql.php">База данных</a></td></tr>';

     if ($user->check_user_rules('view', '/admin/reserv.php') ||
         $user->check_user_rules('view', '/admin/sql.php') ||
         $user->check_user_rules('view', '/admin/cache.php') ||
         $user->check_user_rules('view', '/admin/auth.php') ||
         $user->check_user_rules('view', '/admin/auth_users.php') ||
         $user->check_user_rules('view', '/admin/auth_rules.php') ||
         $user->check_user_rules('view', '/admin/auth_scripts.php') ||
         $user->check_user_rules('view', '/admin/auth_history.php') ||
         $user->check_user_rules('view', '/admin/auth_site.php') ||
         $user->check_user_rules('view', '/admin/auth_site_users.php') ||
         $user->check_user_rules('view', '/admin/auth_site_rules.php') ||
         $user->check_user_rules('view', '/admin/auth_site_scripts.php') ||
         $user->check_user_rules('view', '/admin/auth_site_history.php'))
     echo '<tr><td colspan="2">&nbsp;</td></tr>';

//------------------------------------------------------------------------------

     echo '</table>';
?>
     <br/>
    </td>
<!-- end menu -->

<!--content-->
    <td width="100%" class="content">