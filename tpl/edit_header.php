<? $xajax->processRequest(); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<?php
// Конфигурация
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/page_class.php");
$page = new Page;
?>
<title><?
if (!in_array($_SERVER['PHP_SELF'], array('/admin/editors/edit_content.php', '/admin/editors/edit_design.php'))) echo $page->page_title;
if (isset($_GET['id']) && (int)$_GET['id'] > 0)
 {
   if ($_SERVER['PHP_SELF'] == '/admin/editors/edit_content.php')
    {
      $res = mysql_query("select content_name from content where obj_id = ".(int)$_GET['id']);
      if (mysql_num_rows($res) > 0)
       {
         $r = mysql_fetch_object($res);
         echo htmlspecialchars($r->content_name);
       }
    }
   if ($_SERVER['PHP_SELF'] == '/admin/editors/edit_design.php')
    {
      $res = mysql_query("select tpl_name from designs where tpl_id = ".(int)$_GET['id']);
      if (mysql_num_rows($res) > 0)
       {
         $r = mysql_fetch_object($res);
         echo htmlspecialchars($r->tpl_name);
       }
    }
 }
?></title>
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
<script type="text/javascript">$('.h_menu, .h_menu_sel').corner("keep top 3px");$('.databox').corner("keep 10px");</script>
<script type="text/javascript" src="/admin/editarea/edit_area/edit_area_full.js"></script>
<? $xajax->printJavascript(); ?>

<script type="text/javascript" src="/admin/js/jquery/ui/development-bundle/ui/jquery.ui.core.js"></script>
<script type="text/javascript" src="/admin/js/jquery/ui/development-bundle/ui/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="/admin/js/jquery/ui/development-bundle/ui/i18n/jquery.ui.datepicker-ru.js"></script>
<script type="text/javascript">
  $(function() {
  $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['']));
  $(".datepicker").datepicker($.datepicker.regional['ru']);
  });
</script>

</head>

<body <?
if (BODY_ADDED_ATTR !== '') echo BODY_ADDED_ATTR;
if (isset($_SESSION['smart_tools_refresh']) && $_SESSION['smart_tools_refresh'] == 'enable' && $user->get_cms_option('eco_mode') == 0)
 {
   echo 'onunload="if(self.opener) self.opener.location.reload();"';
   $_SESSION['smart_tools_refresh'] = 'disable';
 }
?> class="content">