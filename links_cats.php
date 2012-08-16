<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['cat_name']))
 {
 if ($user->check_user_rules('add'))
  {
   if (trim($_POST['cat_name'])=='') {Header("Location: /admin/links_cats.php?message=formvalues"); exit();}

   $cat_name = trim($_POST['cat_name']);
   if (use_field($cat_name,'links_cats','cat_name')) {Header("Location: /admin/links_cats.php?message=duplicate");exit();}

   $result = mysql_query("insert into links_cats values (0, null, '$cat_name')");
   if (!$result) {Header("Location: /admin/links_cats.php?message=db");exit();}

   // перенумеровываем
   $result = mysql_query("select * from links_cats order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['cat_id'];
         mysql_query("update links_cats set order_id=$i where cat_id=$id");
         $i++;
       }
    }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   Header("Location: /admin/links_cats.php");
   exit();
  } else $user->no_rules('add');
 }

if (isset($_POST['variants']))
 {
 if ($user->check_user_rules('edit'))
  {

   $variants = $_POST['variants'];

//проверка на наличие элементов: вариантов ответов
   if(count($variants) == 0)
    {
      Header("Location: /admin/links_cats.php?message=formvalues");
      exit();
    }
//проверка на пустые поля вариантов ответов
   foreach ($variants as $key)
    {
     if($key == ''){Header("Location: /admin/links_cats.php?&error=formvalues");exit();}
    }
//проверка на повторные записи в списке ответов
   $temp_array = $variants;
   foreach ($temp_array as $key)
    {
      $i = 0;
      foreach($variants as $current)
       {
         if($key == $current) $i++;
         if($i > 1){Header("Location: /admin/links_cats.php?message=duplicate");exit();}
       }
    }

//Обновляем последовательность категорий в БД
  $i = 1;
  foreach ($variants as $id => $name)
   {
    $result = @mysql_query("update links_cats set order_id=$i where cat_id=$id");
    if (!$result) {Header("Location: /admin/links_cats.php?message=db");exit();}
    $i++;
   }

//Изменяем названия категорий в БД
  foreach ($variants as $id => $name)
   {
    $result = @mysql_query("update links_cats set cat_name='$name' where cat_id=$id");
    if (!$result) {Header("Location: /admin/links_cats.php?message=db");exit();}
   }

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

  Header("Location: /admin/links_cats.php"); exit();
  } else $user->no_rules('edit');
 }

if (isset($_GET['action']) && $_GET['action']!=='' && isset($_GET['category']) && $_GET['category']!=='')
 {
   $cat_id = $_GET['category'];
   $action = $_GET['action'];

   if ($action == 'up')
   {
    if ($user->check_user_rules('action'))
     {
    $old_order = 0;
     //последовательно пронумеровываем элементы
     @$result = mysql_query("select * from links_cats order by order_id asc");
     if (@mysql_num_rows($result) > 0)
      {
        $order = 1;
        $values = array();
        while ($row = mysql_fetch_array($result))
         {
           $cid = $row['cat_id'];
           mysql_query("update links_cats set order_id = $order where cat_id = $cid");
           $values[$order] = $cid;
           if ($cid == $cat_id) $old_order = $order;
           $order++;
         }

        //для текущего
        $q1 = 'update links_cats set order_id = '.($old_order-1).' where cat_id = '.$values[$old_order];
        //для предыдущего
        $q2 = 'update links_cats set order_id = '.$old_order.' where cat_id = '.$values[$old_order-1];
        mysql_query($q1);mysql_query($q2);

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

      }
     } else $user->no_rules('action');
   }
  if ($action == 'down')
   {
     if ($user->check_user_rules('action'))
      {
     $old_order = 0;
     //последовательно пронумеровываем элементы
     @$result = mysql_query("select * from links_cats order by order_id asc");
     if (@mysql_num_rows($result) > 0)
      {
        $order = 1;
        $values = array();
        while ($row = mysql_fetch_array($result))
         {
           $cid = $row['cat_id'];
           mysql_query("update links_cats set order_id = $order where cat_id = $cid");
           $values[$order] = $cid;
           if ($cid == $cat_id) $old_order = $order;
           $order++;
         }

        //для текущего
        $q1 = 'update links_cats set order_id = '.($old_order+1).' where cat_id = '.$values[$old_order];
        //для следующего
        $q2 = 'update links_cats set order_id = '.$old_order.' where cat_id = '.$values[$old_order+1];
        mysql_query($q1);mysql_query($q2);

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

     }
    } else $user->no_rules('action');
   }
  if ($action == 'delete')
   {
     if ($user->check_user_rules('delete'))
      {
     $result = mysql_query("select * from links where cat_id = $cat_id");
     if (@mysql_num_rows($result) > 0)
      {
        Header("Location: /admin/links_cats.php?message=use");exit();
      }
     else mysql_query("delete from links_cats where cat_id = $cat_id");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

      } else $user->no_rules('delete');
   }
  }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Ссылки</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/links.php')) $tabs->add_tab('/admin/links.php', 'Ссылки');
if ($user->check_user_rules('view','/admin/links_cats.php')) $tabs->add_tab('/admin/links_cats.php', 'Cписок категорий');
$tabs->show_tabs();

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
		   <td><img src="/admin/images/icons/plus.gif" alt=""></td>
		   <td><h2 class="nomargins">Добавить категорию</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название категории</td>
      <td>
       <input style="width:280px" type="text" name="cat_name" maxlength="255">
      </td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

 echo '<form action="links_cats.php" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form_light">
    <tr>
      <td>';

$result = mysql_query("select * from links_cats order by order_id asc");
$i = 1;
if (@mysql_num_rows($result) > 0)
 {
   while ($row = mysql_fetch_array($result))
    {
      echo '<input style="width:280px" type="text" name="variants['.$row['cat_id'].']" value="'.htmlspecialchars($row['cat_name']).'" maxlength="255">&nbsp;';

      //если элемент первый на определенном уровне, блокируем стрелку "вверх"
      if ($i == 1) echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
      else echo '<a href="?category='.$row['cat_id'].'&action=up"><img align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0"></a>';

      if ($i == mysql_num_rows($result)) echo '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
      else echo '<a href="?category='.$row['cat_id'].'&action=down"><img align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0"></a>';

      echo '<a href="';
      echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?category=".$row['cat_id']."&action=delete';}";
      echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';

      $i++;
    }
 }
else
  echo 'Нет категорий';

echo'      </td>
    </tr>
   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';

} else $user->no_rules('view');
require_once ("$DOCUMENT_ROOT/admin/tpl/admin_footer.php");
?>