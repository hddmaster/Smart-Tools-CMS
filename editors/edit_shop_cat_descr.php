<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['id']) && isset($_GET['mode']) && count($_POST) > 0)
 {
 if ($user->check_user_rules('edit'))
  {
   $element_id = (int)$_GET['id'];
   $mode = $_GET['mode'];
   $producer_id = (isset($_GET['producer_id']) && (int)$_GET['producer_id']) ? (int)$_GET['producer_id'] : 0;

   if (isset($_POST[$mode]))
    {
      $data = mysql_real_escape_string(trim($_POST[$mode]));
      $field = '';
      switch($mode)
       {
         case 'brief': $field = 'description'; break;
         case 'full': $field = 'description_full'; break;
         case 'extra': $field = 'description_extra'; break;
       }
    }

   if ($producer_id)
    {
      $res = mysql_query("select * from shop_cat_producer_descr where producer_id = $producer_id and element_id = $element_id");
      if (mysql_num_rows($res) > 0)
       {
         $result = mysql_query("update shop_cat_producer_descr set $field = '$data' where producer_id = $producer_id and  element_id=$element_id");
         if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&mode=$mode&producer_id=$producer_id&message=db"); exit();}
       }
      else
       {
         $result = mysql_query("insert into shop_cat_producer_descr (producer_id, element_id, $field) value ($producer_id, $element_id, '$data')");
         if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&mode=$mode&producer_id=$producer_id&message=db"); exit();}        
       }
    }
   else
    {
      $result = mysql_query("update shop_cat_elements set $field = '$data' where element_id=$element_id");
      if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&mode=$mode&message=db"); exit();}     
    }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  Header("Location: ".$_SERVER['PHP_SELF']."?id=$element_id&mode=$mode".(($producer_id) ? "&producer_id=$producer_id" : '')); exit();
  } else $user->no_rules('edit');
 }

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {

    $element_id = (int)$_GET['id'];
    $producer_id = (isset($_GET['producer_id']) && (int)$_GET['producer_id']) ? (int)$_GET['producer_id'] : 0;
    $result = mysql_query("select * from shop_cat_elements where element_id = $element_id");
    if (!$result) exit();
    $row = mysql_fetch_object($result);

 echo '<table cellspacing="0" cellpadding="0" width="100%"><tr valign="top"><td width="100%">';
 echo '<h2 class="nomargins">'.htmlspecialchars($row->element_name).'</h2>';
 if ($producer_id)
  {
    $res = mysql_query("select * from shop_cat_producers where producer_id = ".(int)$_GET['producer_id']);
    if (mysql_num_rows($res) > 0)
     {
       $r = mysql_fetch_object($res);
       echo '<div class="h3 grey">Описание для производителя '.htmlspecialchars($r->producer_name).'</div>';
     }
  }
 echo '</td><td style="padding-left: 20px;">';
 
 $res = mysql_query("select * from shop_cat_producers order by producer_name asc");
 if (mysql_num_rows($res) > 0 && $row->type == 1)
  {
    echo '<form method="get" action="">
          <input type="hidden" name="id" value="'.(int)$_GET['id'].'">
          <input type="hidden" name="mode" value="'.$_GET['mode'].'">
          <table cellspacing="0" cellpadding="2"><tr>
          <td class="small" nowrap>Задать описание данной группы у производителя:</td>
          <td>
          <select name="producer_id" style="width: 180px;">
          <option value="0">---НЕТ---</option>';
    while($r = mysql_fetch_object($res))
      echo '<option value="'.$r->producer_id.'"'.((isset($_GET['producer_id']) && $r->producer_id == (int)$_GET['producer_id']) ? ' selected' : '').'>'.htmlspecialchars($r->producer_name).(($r->producer_descr) ? ' ('.htmlspecialchars($r->producer_descr).')' : '').'</option>';
    echo '</select></td>
          <td></td>
          <td><button type="submit">ОК</button></td>
          </tr></table>
          </form>'; 
  }
 echo '</td></tr></table><div>&nbsp;</div>'; 
 
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_descr.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_descr.php?id='.$element_id.'&mode=brief'.(($producer_id) ? "&producer_id=$producer_id" : ''), 'Краткое описание', ((preg_match('/brief/', $_SERVER['REQUEST_URI'])) ? 1 : 0));
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_descr.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_descr.php?id='.$element_id.'&mode=full'.(($producer_id) ? "&producer_id=$producer_id" : ''), 'Подробное описание', ((preg_match('/full/', $_SERVER['REQUEST_URI'])) ? 1 : 0));
if ($user->check_user_rules('view','/admin/editors/edit_shop_cat_descr.php')) $tabs->add_tab('/admin/editors/edit_shop_cat_descr.php?id='.$element_id.'&mode=extra'.(($producer_id) ? "&producer_id=$producer_id" : ''), 'Дополнительное описание', ((preg_match('/extra/', $_SERVER['REQUEST_URI'])) ? 1 : 0));
$tabs->show_tabs();

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

if (isset($_GET['mode']) && $_GET['mode'] !== '')
 {
   $text = '';
   if($producer_id)
    {
      $res = mysql_query("select * from shop_cat_producer_descr where producer_id = $producer_id and element_id = $element_id");
      if (mysql_num_rows($res) > 0)
       {
         $r = mysql_fetch_object($res);
         switch($_GET['mode'])
          {
            case 'brief': $text = $r->description; break;
            case 'full': $text = $r->description_full; break;
            case 'extra': $text = $r->description_extra; break;
          }        
       }
    }
   else
    {
      switch($_GET['mode'])
       {
         case 'brief': $text = $row->description; break;
         case 'full': $text = $row->description_full; break;
         case 'extra': $text = $row->description_extra; break;
       }
    }
    
   echo '<form action="?id='.$element_id.'&mode='.$_GET['mode'].(($producer_id) ? "&producer_id=$producer_id" : '').'" method="post">';
   $oFCKeditor = new FCKeditor($_GET['mode']);
   $oFCKeditor->BasePath = '/admin/fckeditor/';
   $oFCKeditor->ToolbarSet = 'Main' ;
   $oFCKeditor->Value = $text;
   $oFCKeditor->Width  = '100%' ;
   $oFCKeditor->Height = '410' ;
   $oFCKeditor->Create() ;
   echo'<div>&nbsp;</div><button type="SUBMIT">Сохранить</button></form>';
 }//mode

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>