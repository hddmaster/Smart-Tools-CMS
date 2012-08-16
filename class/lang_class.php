<?
class Language
 {
    public $lang = false;
    public $lang_id = false;
    public $data = array();

    function __construct($lang = '')
     {
       $this->lang = (($lang) ? $lang : 'RU');

       if ($this->lang == 'RU')
         $result = mysql_query("select * from lang_constants");
       else
         $result = mysql_query("select
                                *
                                from
                                languages, lang_constants, lang_constant_values
                                where languages.lang_code = '".addslashes($this->lang)."' and
                                languages.lang_id = lang_constant_values.lang_id and
                                lang_constants.constant_id = lang_constant_values.constant_id");
       if (mysql_num_rows($result) > 0)
        {
          while ($row = mysql_fetch_array($result))
           {
             $this->lang_id = $row['lang_id'];
             $this->data[$row['constant_name']] = $row['constant_value'];
           }
        }
     }

    public function gettext($value)
     {
       if(isset($this->data[$value]) && $this->data[$value] !== '') return $this->data[$value];
       else
        {
          //создание словаря непереведенных фраз
          if ($this->lang_id)
           {
             $res = mysql_query("select * from lang_constant_empty_values where lang_id = ".$this->lang_id." and constant_value = '$value'");
             if (mysql_num_rows($res) == 0) mysql_query("insert into lang_constant_empty_values values (null, ".$this->lang_id.", '$value')");
           }
          return $value;
        }
     }

    public function gettext_db($table, $column, $rowkey, $row)
     {
       $value = '';
       $query = "select $column from $table where $rowkey = $row";
       $res = mysql_query($query);
       if (!$res) return false;
       $r_value = mysql_fetch_array($res);
       $value = htmlspecialchars($r_value[$column]);

       if($this->lang_id)
        {
          $query = "select
                    value
                    from
                    lang_database
                    where
                    lang_id = {$this->lang_id} and
                    `table` = '$table' and
                    `column` = '$column' and
                    `rowkey` = '$rowkey' and
                    `row` = $row";
          $res = mysql_query($query);
          if (mysql_num_rows($res) > 0)
           {
             $r_value = mysql_fetch_array($res);
             $value = htmlspecialchars($r_value['value']);
           }
        }
       return $value;
     }
 }
?>