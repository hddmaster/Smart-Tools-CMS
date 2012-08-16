<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

 if ($user->check_user_rules('view'))
  {

if (isset($_GET['action']))
 {
   $action = $_GET['action'];
   if ($action == 'reserv')
    {

 if ($user->check_user_rules('action'))
  {

//------------------------------------------------------------------------------
  // исп. только текущую БД
  $now = date("YmdHis");
  $now_ = date("d-m-Y H.i.s");
  $data = "--\n-- Smart Tools CMS\n-- Копия основной базы данных на $now_\n--";
  echo '<h2>Создание копии базы данных...</h2>';
  ob_end_flush();
  flush();


  $pathdir = $_SERVER['DOCUMENT_ROOT']."/sql_dumps";
  $filename = "sql_dump $now_.sql";

  //Проверка на наличие файла, замена имени, пока такого файла не будет
  $file = pathinfo($filename);
  $ext = $file['extension'];
  $name_clear = str_replace(".$ext",'',$filename);
  $name = $name_clear;
  $i = 1;
  while (file_exists($_SERVER['DOCUMENT_ROOT']."/sql_dumps/$name.$ext"))
   {
     $name = $name_clear." ($i)";
     $i ++;
   }

  $filename = $name.'.'.$ext;

  if (!file_exists($pathdir)) {umask(0);@mkdir($pathdir, 0777);}

  $f = fopen("$pathdir/$filename", "w");
  flock($f, LOCK_EX);


  $tables = mysql_query("show tables");
  if (mysql_num_rows($tables) > 0)
   {
     echo '<p>Таблиц для обработки: <strong>'.mysql_num_rows($tables).'</strong></p>';
     ob_end_flush();
     flush();
     
     $loopsize = mysql_num_rows($tables);
     $percent_per_loop = 100 / $loopsize;
     $percent_last = 0;
     $i_bar = 1;
     echo '<div class="mailbar">
           <div class="baritems">';

     while ($tables_row = mysql_fetch_array($tables))
      {
     	$percent_now = round($i_bar * $percent_per_loop);
    	if($percent_now != $percent_last)
         {
           $difference = $percent_now - $percent_last;
           for($j=1;$j<=$difference;$j++)
             echo '<img src="/admin/images/bar/mailerbar-single.gif" width="5" height="15">';
           $percent_last = $percent_now;
	   	 }
        
		ob_end_flush();
	    flush();


        $header = mysql_query("show create table $tables_row[0]");
        if (mysql_num_rows($header) > 0)
         {
           while ($header_row = mysql_fetch_array($header))
            {
              $data .= "\n\n--\n-- Структура таблицы $tables_row[0]\n--\n";
              $data .= "DROP TABLE IF EXISTS `$tables_row[0]`;\n";
              $data .= $header_row[1].';';
              
            
//------------------------------------------------------------------------------
        if ($tables_row[0] !== 'auth_online' &&
            $tables_row[0] !== 'auth_site_online' &&
            $tables_row[0] !== 'cache' &&
            $tables_row[0] !== 'history' &&
            $tables_row[0] !== 'site_history' &&
            $tables_row[0] !== 'stat_global_hits' &&
            $tables_row[0] !== 'stat_global_ips' &&
            $tables_row[0] !== 'stat_global_referrers' &&
            $tables_row[0] !== 'stat_global_scripts' &&
            $tables_row[0] !== 'stat_global_uniques' &&
            $tables_row[0] !== 'stat_global_user_agents' &&
            $tables_row[0] !== 'stat_today' &&
            $tables_row[0] !== 'stat_today_time')
        {

              //работаем со столбцами: типы кладем в массив, подсчитываем число столбцов
              $is_char_data = array();
              $res = mysql_query("show columns from $tables_row[0]");
              while ($row = mysql_fetch_array($res))
               {
                $type = strtok($row[1], '(');
                switch ($type)
                 {
                   case 'tinyint':
                   case 'smallint':
                   case 'mediumint':
                   case 'int':
                   case 'bigint':
                   case 'float':
                   case 'double':
                   case 'decimal': $is_char_data[] = 0; break;
                   default: $is_char_data[] = 1; break;
                 }
               }
              $num_columns = mysql_num_rows($res);

              //выбираем все строки из таблицы, создаем дамп данных. Ставим кавычки для строковых типов.
              $res = mysql_query("select * from $tables_row[0]");
              $data .= "\n\n--\n-- Данные таблицы $tables_row[0], строк: ".mysql_num_rows($res)."\n--\n";

              if (mysql_num_rows($res) > 0)
               {
                 while ($row = mysql_fetch_array($res))
                  {

                    $data .= "INSERT INTO `$tables_row[0]` VALUES (";
                    $i = 0;
                    while ($i < $num_columns)
                     {
                       if ($is_char_data[$i] == 1)
                        {
                          $search = array("\x00", "\x0a", "\x0d", "\x1a"); //\x08\\x09, not required
                          $replace = array('\0',  '\n',   '\r',   '\Z');
                          $string = str_replace($search, $replace, $row[$i]);
                          $string = str_replace("\'", "\\\'", $string);
                          $string = str_replace("'", "\'", $string);

                          $data .= "'$string'"; //внимание на строковые переменные '' !!!!!!!!
                        }
                       else $data .= "$row[$i]";

                       if ($i != ($num_columns-1)) $data .= ",";
                       $i++;
                     }
                    $data .= ");\n";

                  }
               }
        }
//------------------------------------------------------------------------------
              fwrite($f, $data);
              $data = '';
            }
         }
       $i_bar++;
      }

              echo '</div></div><br/><br/>';

   }

  flock($f, LOCK_UN);
  fclose($f);

  if (file_exists("$pathdir/$filename") && filesize("$pathdir/$filename") > 0)
   {
     //архивирование в zip
     $filename_arc = $name.'.zip';
     $archive = new PclZip("$pathdir/$filename_arc");
     $list = $archive->create("$pathdir/$filename",PCLZIP_OPT_REMOVE_PATH, "$pathdir");
     if ($list == 0) {echo "<p>ERROR : ".$archive->errorInfo(true)."</p>";}
     unlink("$pathdir/$filename");

     mysql_query("insert into sql_dumps values(null, $now, '$filename_arc')") or die (mysql_error());
     echo '<h2 class="green">Копия создана!</h2>';
   }
  else
   {
     unlink("$pathdir/$filename");
     echo '<h2 class="red">Ошибка!</h2>';
   }

  ob_end_flush();
	flush();
 
  } else $user->no_rules('action');
 }
 }

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>