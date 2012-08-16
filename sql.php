<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

//------------------------------------------------------------------------------
function PMA_splitSqlFile(&$ret, $sql)
{
    // do not trim, see bug #1030644
    //$sql        = trim($sql);
    $sql          = rtrim($sql, "\n\r");
    $sql_len      = strlen($sql);
    $char         = '';
    $string_start = '';
    $in_string    = FALSE;
    $nothing      = TRUE;
    $time0        = time();

    for ($i = 0; $i < $sql_len; ++$i) {
        $char = $sql[$i];

        // We are in a string, check for not escaped end of strings except for
        // backquotes that can't be escaped
        if ($in_string) {
            for (;;) {
                $i         = strpos($sql, $string_start, $i);
                // No end of string found -> add the current substring to the
                // returned array
                if (!$i) {
                    $ret[] = array('query' => $sql, 'empty' => $nothing);
                    return TRUE;
                }
                // Backquotes or no backslashes before quotes: it's indeed the
                // end of the string -> exit the loop
                else if ($string_start == '`' || $sql[$i-1] != '\\') {
                    $string_start      = '';
                    $in_string         = FALSE;
                    break;
                }
                // one or more Backslashes before the presumed end of string...
                else {
                    // ... first checks for escaped backslashes
                    $j                     = 2;
                    $escaped_backslash     = FALSE;
                    while ($i-$j > 0 && $sql[$i-$j] == '\\') {
                        $escaped_backslash = !$escaped_backslash;
                        $j++;
                    }
                    // ... if escaped backslashes: it's really the end of the
                    // string -> exit the loop
                    if ($escaped_backslash) {
                        $string_start  = '';
                        $in_string     = FALSE;
                        break;
                    }
                    // ... else loop
                    else {
                        $i++;
                    }
                } // end if...elseif...else
            } // end for
        } // end if (in string)

        // lets skip comments (/*, -- and #)
        else if (($char == '-' && $sql_len > $i + 2 && $sql[$i + 1] == '-' && $sql[$i + 2] <= ' ') || $char == '#' || ($char == '/' && $sql_len > $i + 1 && $sql[$i + 1] == '*')) {
            $i = strpos($sql, $char == '/' ? '*/' : "\n", $i);
            // didn't we hit end of string?
            if ($i === FALSE) {
                break;
            }
            if ($char == '/') $i++;
        }

        // We are not in a string, first check for delimiter...
        else if ($char == ';') {
            // if delimiter found, add the parsed part to the returned array
            $ret[]      = array('query' => substr($sql, 0, $i), 'empty' => $nothing);
            $nothing    = TRUE;
            $sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
            $sql_len    = strlen($sql);
            if ($sql_len) {
                $i      = -1;
            } else {
                // The submited statement(s) end(s) here
                return TRUE;
            }
        } // end else if (is delimiter)

        // ... then check for start of a string,...
        else if (($char == '"') || ($char == '\'') || ($char == '`')) {
            $in_string    = TRUE;
            $nothing      = FALSE;
            $string_start = $char;
        } // end else if (is start of string)

        elseif ($nothing) {
            $nothing = FALSE;
        }

        // loic1: send a fake header each 30 sec. to bypass browser timeout
        $time1     = time();
        if ($time1 >= $time0 + 30) {
            $time0 = $time1;
            header('X-pmaPing: Pong');
        } // end if
    } // end for

    // add any rest to the returned array
    if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql)) {
        $ret[] = array('query' => $sql, 'empty' => $nothing);
    }

    return TRUE;
} // end of the 'PMA_splitSqlFile()' function

//------------------------------------------------------------------------------

function process_sql_query($query)
 {
    global $user;
    if (!$user->check_user_rules('action')) exit();
    $objResponse = new xajaxResponse();
    $text = '';
    $query_commands = array();
    PMA_splitSqlFile($query_commands, $query);

    $good_requests = 0;
    $i = 0;
    foreach ($query_commands as $q_values)
    {
    $q = $q_values['query'];
    if ($q) {
    $result = mysql_query($q);
    if ($result) $good_requests++;
    if (!$result) $text .= 'Ошибка: <span class="red">'.mysql_error().'</span><br/>Запрос: <span class="grey">'.$q.'</span><br/><br/>';
                        
    elseif (mysql_num_rows($result) > 0)
     {
       if (mysql_affected_rows() > 0) $text .= 'Запрос: <span class="grey">'.$q.'</span><br/>Затронуто: '.mysql_affected_rows().' рядов<br/>';
       
       $text .= '<br/><table width="100%" cellspacing="0" cellpadding="4" border="0">';
       $i = 1;
       while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
        {
          if ($i == 1)
           {
             $text .= '<tr class="header">';
             foreach ($row as $key => $value)
                $text .= '<td align="center">'.htmlspecialchars($key).'</td>';
           }

          $text .= '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">';
          foreach($row as $value)
           {
             if (trim($value) == '') $text .= '<td>&nbsp;</td>';
             else $text .= '<td>'.htmlspecialchars($value).'</td>';
           }
          $text .= '</tr>';

          $i++;
        }
       $text .= '</table><br/><br/>';
     }
    }
      if ($i == count($query_commands)-1) $text .=  '<br />'; $i++;
    }
    if ($good_requests > 0) $text .=  'Выполнено инструкций: '.$good_requests.'<div>&nbsp;</div>';

   $objResponse->assign('sql_result_div',"innerHTML",$text);
   $objResponse->assign('submitbutton',"disabled",false);
   return $objResponse;
 }

$xajax->registerFunction("process_sql_query");

//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');
echo '<h1>Администрирование БД</h1>';

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/sql.php')) $tabs->add_tab('/admin/sql.php', 'SQL', 0, '/admin/images/icons/databese-import.png');
if ($user->check_user_rules('view','/admin/reserv.php')) $tabs->add_tab('/admin/reserv.php', 'Резервирование базы данных');
$tabs->add_tab('/admin/phpmyadmin/', 'phpMyAdmin');
$tabs->show_tabs();

 if ($user->check_user_rules('view'))
  {
 if ($user->check_user_rules('action'))
  {

    echo '<div id="sql_result_div"></div>
          <form action="sql.php" name="form">
           <textarea name="query" style="width: 100%; height: 200px;">'; if (isset($_POST['query'])) echo stripslashes($_POST['query']); echo '</textarea>
           <div>&nbsp;</div><button id="submitbutton" type="button" onclick="if (document.form.query.value)
                                                                  {
                                                                    document.form.submitbutton.disabled = true;
                                                                    document.getElementById(\'sql_result_div\').innerHTML = \'<p align=center><img src=/admin/images/loading.gif alt=><br/><span class=small>Загрузка...</span></p>\';
                                                                    xajax_process_sql_query(document.form.query.value);
                                                                  }
                                                                 else alert(\'Введите SQL комманду!\');">Выполнить</button>
          </form>';
         
  } else $user->no_rules('action');
  } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>