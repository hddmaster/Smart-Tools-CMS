<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['tpl_name']) &&
    isset($_POST['tpl_description']))
 {

 if ($user->check_user_rules('add'))
  {
  if (trim($_POST['tpl_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
  $tpl_name = $_POST['tpl_name'];
  $tpl_description = $_POST['tpl_description'];

//проверка на повторы
  if (use_field($tpl_name,'designs','tpl_name')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate"); exit();}

  //Добавляем...
  $result = mysql_query("insert into designs values (null,'$tpl_name','$tpl_description','')");
  if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
  } else $user->no_rules('add');
 }

if (isset($_GET['action']) && isset($_GET['id']))
 {
   $action = $_GET['action'];
   $tpl_id = (int)$_GET['id'];

   if ($action == 'del')
    {
     if ($user->check_user_rules('delete'))
      {
      //Проверка на использование шаблона страницами сайта
      $result = mysql_query("select * from pages where tpl_id = $tpl_id");
      if (@mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=use"); exit();}
      else
       {
         $res = mysql_query("select * from designs where tpl_id = $tpl_id");
         if (mysql_num_rows($res) > 0)
          {
            $r = mysql_fetch_array($res);
            if($r['img_path'])
             {
               $filename = $r['img_path'];
               if (!use_file($filename,'designs','img_path')) @unlink($_SERVER['DOCUMENT_ROOT'].$filename);
             }
            mysql_query("delete from designs where tpl_id=$tpl_id");
          }
       }
      } else $user->no_rules('delete');
    }
   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Шаблоны</h1>';

 if ($user->check_user_rules('view'))
  {

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить шаблон</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';


 echo '<form enctype="multipart/form-data" action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="tpl_name" maxlength="255"></td>
    </tr>
    <tr>
      <td>Описание</td>
      <td><input style="width:280px" type="text" name="tpl_description" maxlength="255"></td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0" border="0" width="100%">
   <tr>

   <td width="100%">&nbsp;</td>

   <td>
   <table cellspacing="0" cellpadding="4" border="0">
   <tr><td><img src="/admin/images/icons/magnifier.png" alt=""></td><td>
   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td><input style="width:280px" type="text" name="query_str" value="'; if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') echo htmlspecialchars(stripcslashes($_GET['query_str'])); echo '"></input></td>
      <td><button type="SUBMIT">Найти</button></td>
    </tr>
  </table></td></tr></table>
  </td></tr></table></form>';

// постраничный вывод
 if (isset($_GET['page'])) $page=($_GET['page']-1); else $page=0;
 if (isset($_GET['per_page'])) $per_page=($_GET['per_page']); else $per_page=20;
 $start=abs($page*$per_page);

// сортировка
 if (isset($_GET['sort_by']) && isset($_GET['order']))
  {
    $sort_by = $_GET['sort_by'];
    $order  = $_GET['order'];
  }
 else
  {
    $sort_by = 'tpl_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {

   $params['query_str'] = mb_strtolower(trim($_GET['query_str']),'UTF-8');
   $query_str = '%'.mb_strtolower(trim($_GET['query_str']),'UTF-8').'%';

   $add .= " and (tpl_name like '$query_str' or
                  tpl_description like '$query_str' or
                  data like '$query_str')";
 }
  
 $query = "select * from designs where 1 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">';
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=tpl_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'tpl_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=tpl_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'tpl_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap width="30%">Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=tpl_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'tpl_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=tpl_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'tpl_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap width="30%">Описание&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=tpl_description&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'tpl_description' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=tpl_description&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'tpl_description' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap width="40%">Страницы сайта</td>
         <td>&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['tpl_id'].'</td>
           <td><a href="javascript:sw(\'/admin/editors/edit_design.php?id='.$row['tpl_id'].'\');"><strong>'.htmlspecialchars($row['tpl_name']).'</strong></a></td>
           <td>'; if ($row['tpl_description']) echo htmlspecialchars($row['tpl_description']); else echo '&nbsp;'; echo '</td>
           <td class="grey">';
	   
	   $res = mysql_query("select * from pages where tpl_id = ".$row['tpl_id']);
	   if (mysql_num_rows($res) > 0)
	    {
	      $i = 1;
	      while ($r = mysql_fetch_object($res))
	       {
		 echo '<a class="grey" href="#" onclick="sw(\'/admin/editors/edit.php?id='.$r->page_id.'\'); return false;">'.htmlspecialchars($r->page_name).'</a>';
		 if ($i < mysql_num_rows($res)) echo ', ';
		 $i++;
	       }
	    } else echo '&nbsp;';
 
	   echo '</td>
           <td nowrap align="center"><a href="javascript:if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?action=del&id='.$row['tpl_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'\';}"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>';
   }
  echo '</table></div>';
 navigation($page, $per_page, $total_rows, $params);
  }
else echo '<p align="center">Не найдено</p>';

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>