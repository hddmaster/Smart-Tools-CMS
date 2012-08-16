<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['head'])) {
    if ($user->check_user_rules('add')) {

        //if (trim($_POST['date'])=='' || trim($_POST['date2'])=='' || !isset($_POST['type'])) {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
        $head = $_POST['head'];
        $code = ''; if (isset($_POST['code'])) $code = $_POST['code'];
        $url = ''; if (isset($_POST['url'])) $url = $_POST['url'];
        $adv_type = $_POST['type'];
            
        if ($adv_type == 0 && !is_uploaded_file($_FILES['picture']['tmp_name'])) {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
        if ($adv_type == 1 && !is_uploaded_file($_FILES['flash']['tmp_name'])) {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
        if ($adv_type == 2 && trim($_POST['code']) == '') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
        
        $hour1 = intval($_POST['hour1']); if ($hour1 > 23) $hour1 = 00; if ($hour1 < 10) $hour1 = '0'.$hour1;
        $minute1 = intval($_POST['minute1']); if ($minute1 > 59) $minute1 = 00; if ($minute1 < 10) $minute1 = '0'.$minute1;
        $second1 = intval($_POST['second1']); if ($second1 > 59) $second1 = 00; if ($second1 < 10) $second1 = '0'.$second1;
        $date1 = substr($_POST['date1'],6,4).substr($_POST['date1'],3,2).substr($_POST['date1'],0,2).$hour1.$minute1.$second1;
        
        $hour2 = intval($_POST['hour2']); if ($hour2 > 23) $hour2 = 00; if ($hour2 < 10) $hour2 = '0'.$hour2;
        $minute2 = intval($_POST['minute2']); if ($minute2 > 59) $minute2 = 00; if ($minute2 < 10) $minute2 = '0'.$minute2;
        $second2 = intval($_POST['second2']); if ($second2 > 59) $second2 = 00; if ($second2 < 10) $second2 = '0'.$second2;
        $date2 = substr($_POST['date2'],6,4).substr($_POST['date2'],3,2).substr($_POST['date2'],0,2).$hour2.$minute2.$second2;
        
        if(isset($_FILES['picture']['name']) && is_uploaded_file($_FILES['picture']['tmp_name'])) {
            $user_file_name = mb_strtolower($_FILES['picture']['name'],'UTF-8');
            $type = basename($_FILES['picture']['type']);
        
            switch ($type) {
                case 'jpeg': break;
                case 'pjpeg': break;
                case 'png': break;
                case 'x-png': break;
                case 'gif': break;
                case 'bmp': break;
                case 'wbmp': break;
                default: Header("Location: ".$_SERVER['PHP_SELF']."?message=incorrectfiletype"); exit(); break;
            }
        
            //Проверка на наличие файла, замена имени, пока такого файла не будет
            $file = pathinfo($user_file_name);
            $ext = $file['extension'];
            $name_clear = str_replace(".$ext",'',$user_file_name);
            $name = $name_clear;
            $i = 1;
            while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/banners/$name.$ext")) {
                $name = $name_clear." ($i)";
                $i ++;
            }
            $user_file_name =  $name.'.'.$ext;
        }

        if(isset($_FILES['flash']['name']) && is_uploaded_file($_FILES['flash']['tmp_name'])) {
            $user_file_name = mb_strtolower($_FILES['flash']['name'],'UTF-8');
            $type = basename($_FILES['flash']['type']);
            
            switch ($type) {
                case 'x-shockwave-flash': break;
                default: Header("Location: ".$_SERVER['PHP_SELF']."?message=incorrectfiletype"); exit(); break;
            }
            
            //Проверка на наличие файла, замена имени, пока такого файла не будет
            $file = pathinfo($user_file_name);
            $ext = $file['extension'];
            $name_clear = str_replace(".$ext",'',$user_file_name);
            $name = $name_clear;
            $i = 1;
            while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/banners/$name.$ext")) {
                $name = $name_clear." ($i)";
                $i ++;
            }
            $user_file_name =  $name.'.'.$ext;
        }


        //Добавляем...
        $query = "insert into advertising
                (adv_type, date1, date2, head, img_path, flash_path, code, url)
                values
                ($adv_type, '$date1', '$date2', '$head'";
                if(isset($_FILES['picture']['name']) && is_uploaded_file($_FILES['picture']['tmp_name'])) $query .= ",'/userfiles/banners/$user_file_name'"; else $query .= ",''";
                if(isset($_FILES['flash']['name']) && is_uploaded_file($_FILES['flash']['tmp_name'])) $query .= ",'/userfiles/banners/$user_file_name'"; else $query .= ",''";
        $query .= ", '$code', '$url')";
        
        $result = mysql_query($query);
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
    
        if (isset($_FILES['picture']['name']) && is_uploaded_file($_FILES['picture']['tmp_name'])) {
            $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/banners/$user_file_name";
            copy($_FILES['picture']['tmp_name'], $filename);
            chmod($filename,0666);
        }
    
        if (isset($_FILES['flash']['name']) && is_uploaded_file($_FILES['flash']['tmp_name'])) {
            $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/banners/$user_file_name";
            copy($_FILES['flash']['tmp_name'], $filename);
            chmod($filename,0666);
        }

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();

        $page = 1; $per_page = 20;
        if (isset($_GET['page'])) $page = $_GET['page'];
        if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
        Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
        exit();
    } else $user->no_rules('add');
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $adv_id = (int)$_GET['id'];

    if ($action == 'del') {
        if ($user->check_user_rules('delete')) {
            //запоминаем имя файла и удаляем его
            $result = mysql_query("select * from advertising where adv_id=$adv_id");
            if (mysql_num_rows($result) > 0) {
                $row = mysql_fetch_array($result);
                if ($row['img_path']) {
                    $filename = $row['img_path'];
                    if (!use_file($filename,'advertising','img_path'))
                    @unlink($_SERVER['DOCUMENT_ROOT'].$filename);
                }
            
                if ($row['flash_path']) {
                    $filename = $row['flash_path'];
                    if (!use_file($filename,'advertising','flash_path'))
                    @unlink($_SERVER['DOCUMENT_ROOT'].$filename);
                }
            }

            //удаляем из бд
            $result = mysql_query("delete from advertising where adv_id=$adv_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

            $result = mysql_query("delete from adv_pages where adv_id=$adv_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

            //Обновление кэша связанных модулей на сайте
            $cache = new Cache; $cache->clear_cache_by_module();
        } else $user->no_rules('delete');
    }

    if ($action == 'activate') {
        if ($user->check_user_rules('action')) {
            mysql_query("update advertising set status=1 where adv_id=$adv_id");
            //Обновление кэша связанных модулей на сайте
            $cache = new Cache; $cache->clear_cache_by_module();
        } else $user->no_rules('action');
    }

    if ($action == 'reserve') {
        if ($user->check_user_rules('action')) {
            mysql_query("update advertising set status=0 where adv_id=$adv_id");
            //Обновление кэша связанных модулей на сайте
            $cache = new Cache; $cache->clear_cache_by_module();
        } else $user->no_rules('action');
    }
}

//------------------------------------------------------------------------------
// AJAX
function show_input($type = 0) {
    $objResponse = new xajaxResponse();
    $text = '';
    
    if ($type == 0) $text = '<input style="width:280px" type="text" name="url" maxlength="255" value="http://'.$_SERVER['HTTP_HOST'].'"><br /><input style="width:280px" type="file" name="picture">';
    if ($type == 1) $text = '<img src="/admin/images/px.gif" alt="" width="1" height="10"><br /><input style="width:280px" type="file" name="flash">';
    if ($type == 2) $text = '<textarea style="width:280px; height: 38px;" name="code"></textarea>';
    
    $objResponse->assign('b_input', 'innerHTML', $text);
    return $objResponse;
}

$xajax->registerFunction("show_input");
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Реклама</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/advertising.php')) $tabs->add_tab('/admin/advertising.php', 'Баннеры');
if ($user->check_user_rules('view','/admin/adv_groups.php')) $tabs->add_tab('/admin/adv_groups.php', 'Рекламные кампании');
$tabs->show_tabs();

if ($user->check_user_rules('view')) {

    if (isset($_GET['message'])) {
        $message = new Message;
        $message->get_message($_GET['message']);
    }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить баннер</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form enctype="multipart/form-data" action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название</td>
      <td><input style="width:280px" type="text" name="head" maxlength="255"></td></tr>
    <tr>
      <td>Даты показа <sup class="red">*</sup></td>
        <td>
            <table cellspacing="0" cellpadding="0">
                <tr>    
                    <td style="padding-right: 4px;">с</td>
                    <td><input style="width: 65px" type="text" name="date1" class="datepicker" value="'.date('d.m.Y').'"></td>
                    <td style="padding-left: 8px;padding-right: 4px;">по</td>
                    <td><input style="width: 65px" type="text" name="date2" class="datepicker" value="'.date('d.m.Y').'"></td>
                </tr>
            </table>


        </td>
    </tr>
    <tr>
      <td>Время показа <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td>
        <table cellspacing="0" cellpadding="0">
         <tr>
           <td>с&nbsp;</td>
           <td>
             <input type="text" name="hour1" value="00" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
             <input type="text" name="minute1"  value="00" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
             <input type="text" name="second1" value="01" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
           </td>
           <td>&nbsp;&nbsp;по&nbsp;</td>
           <td>
             <input type="text" name="hour2" value="23" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
             <input type="text" name="minute2"  value="59" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
             <input type="text" name="second2" value="59" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
           </td>
         </tr>
        </table> 
      </td>
    </tr>
    <tr>
      <td>Тип <sup class="red">*</sup></td>
      <td>
        <table cellspacing="0" cellpadding="0">
         <tr>
           <td><input type="radio" name="type" value="0" onclick="xajax_show_input(0);" checked></td>
           <td><span class="grey">картинка</span></td>
         </tr>
         <tr>
           <td><input type="radio" name="type" value="1" onclick="xajax_show_input(1);"></td>
           <td><span class="grey">flash</span></td>
         </tr>
         <tr>
           <td><input type="radio" name="type" value="2" onclick="xajax_show_input(2);"></td>
           <td><span class="grey">код</span></td>
         </tr>
        </table>
      </td></tr>
    <tr>
      <td>Баннер <sup class="red">*</sup></td>
      <td><div id="b_input" style="height: 40px;"><img src="/admin/images/px.gif" alt="" width="1" height="13"><br /><span class="small">Выберите тип баннера...</span></div></td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>
  
    <script>
        $(document).ready(function(){
            xajax_show_input(0);
        });
    </script>

<form action="" method="GET">
  <table cellspacing="0" cellpadding="0" border="0" width="100%">
   <tr>

   <td width="100%">&nbsp;</td>

   <td>
   <table cellspacing="0" cellpadding="4" border="0">
   <tr><td><img src="/admin/images/icons/magnifier.png" alt=""></td><td>
   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td><input style="width:280px" type="text" name="query_str" value="'; if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') echo htmlspecialchars($_GET['query_str']); echo '"></input></td>
      <td><button type="SUBMIT">Найти</button></td>
    </tr>
  </table></td></tr></table>
  </td></tr></table></form>';

    // постраничный вывод
    if (isset($_GET['page'])) $page=($_GET['page']-1); else $page=0;
    if (isset($_GET['per_page'])) $per_page=($_GET['per_page']); else $per_page=20;
    $start=abs($page*$per_page);

    // сортировка
    if (isset($_GET['sort_by']) && isset($_GET['order'])) {
        $sort_by = $_GET['sort_by'];
        $order  = $_GET['order'];
    } else {
        $sort_by = 'adv_id';
        $order = 'desc';
    }

    $add = '';
    $params = array();

    if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') {
        $params['query_str'] = strtolower(trim($_GET['query_str']));
        $query_str = '%'.strtolower(trim($_GET['query_str'])).'%';

        $add .= "   and (adv_id like '$query_str' or
                    head like '$query_str' or
                    code like '$query_str')";
    }

    $query = "  select
                *,
                date_format(date1, '%d.%m.%Y (%H:%i:%s)') as date1_,
                date_format(date2, '%d.%m.%Y (%H:%i:%s)') as date2_
                from
                advertising where type = 0 $add";
    $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
    $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=adv_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'adv_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=adv_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'adv_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=head&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'head' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата начала&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date1&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date1' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date1&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date1' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата завершения&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date2&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date2' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date2&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date2' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Тип&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=adv_type&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'adv_type' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=adv_type&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'adv_type' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Файл</td>
         <td nowrap>Рекламные кампании</td>
         <td nowrap>Просмотров</td>
         <td nowrap>Переходов</td>
         <td>&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['adv_id'].'</td>
           <td>'; if ($row['head']) echo '<strong><a href="javascript:sw(\'/admin/editors/edit_adv.php?id='.$row['adv_id'].'\');">'.htmlspecialchars($row['head']).'</a></strong>'; else echo '&nbsp;'; echo '</td>
           <td align="center">'.$row['date1_'].'</td>
           <td align="center">'.$row['date2_'].'</td>
           <td align="center">';

           if ($row['adv_type'] == 0) echo '<span class="small">картинка</span>';
           if ($row['adv_type'] == 1) echo '<span class="small">flash</span>';
           if ($row['adv_type'] == 2) echo '<span class="small">код</span>';

           echo '</td><td>';
           if($row['img_path']) echo '<a href="'.$row['img_path'].'" class="zoom" rel="group">'.$row['img_path'].'</a>';
           elseif($row['file_path']) echo '<a href="'.$row['flash_path'].'">'.$row['flash_path'].'</a>';
           else echo '&nbsp;';
           echo '</td><td align="center">';

           $res = mysql_query("select sum(value) as sum_value from adv_results where adv_id = ".$row['adv_id']." group by adv_id");
           if (mysql_num_rows($res) > 0)
            {
              $r = mysql_fetch_array($res);
              echo $r['sum_value'];
            } else echo '&nbsp;';

           echo '</td>
           <td>&nbsp;</td>
           <td>&nbsp;</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_adv.php?id='.$row['adv_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать пользователя"></a>&nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['adv_id'].'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность пользователя"></a>';
           else echo '<a href="?action=reserve&id='.$row['adv_id'].'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность пользователя"></a>';
           echo '&nbsp;<a href="javascript:if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?action=del&id='.$row['adv_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'\';}"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>';
  }
  echo '</table></div>';
 navigation($page, $per_page, $total_rows, $params);
  }
else echo '<p align="center">Не найдено</p>';
} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>