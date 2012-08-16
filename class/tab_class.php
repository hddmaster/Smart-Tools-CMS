<?
class Tabs
 {
    public $tab_values = array();
    public $tabs = '';
    public $auto_detect_page = true;
    public $max_priority = 0;
    public $level = 0;
    public $no_priority = true;
    public $php_self;
    public $request_uri;
    
   function __construct()
    {
      $this->php_self = $_SERVER['PHP_SELF'];
      //$this->php_self = str_replace('index.php','',$this->php_self);
      $this->request_uri = $_SERVER['REQUEST_URI'];
      $this->request_uri = str_replace('/?','/index.php?',$this->request_uri);
      if (substr($this->request_uri, strlen($this->request_uri)-1, 1) == '/') $this->request_uri .= 'index.php';
    }

   public function add_tab($url, $tab_name, $status = 0)
    {
      $this->tab_values[$url] = array($tab_name, $status);
    }

   public function show_tabs()
    {
      if (count($this->tab_values) > 0)
       {
         foreach ($this->tab_values as $url => $tab)
          {
            //if(substr($url,-1,1) == '/') $url .= 'index.php';
            $url_c = str_replace(array('/','?','-','.','!'),
                                 array('\/','\?','\-','\.','\!'),$url);
            $current_priority = 0;
            
            $this->tab_values[$url][2] = 0;
            $this->tab_values[$url][3] = '';
            
            //1. проверка базового url
            if ($this->php_self == $url)
             {
               $v = 2;
               $this->tab_values[$url][2]+=$v;
               $current_priority+=$v;
               $this->tab_values[$url][3].='1';
             }
            else $this->tab_values[$url][3].='0';
            //2. проверка базового url c экранированными спецсимволами
            if (preg_match('/'.$url_c.'/', $this->php_self)) {$v = 1; $this->tab_values[$url][2]+=$v; $current_priority+=$v; $this->tab_values[$url][3].='1';} else $this->tab_values[$url][3].='0';

            if ($this->php_self !== $this->request_uri &&
                $url !== $this->php_self)
             {
               //3. проверка строки с параметрами в url
               if ($this->request_uri == $url) {$v = 1; $this->tab_values[$url][2]+=$v; $current_priority+=$v; $this->tab_values[$url][3].='1';} else $this->tab_values[$url][3].='0';
               //4. проверка строки с параметрами в url c экранированными спецсимволами
               if (preg_match('/'.$url_c.'/', $this->request_uri)) {$v = 1; $this->tab_values[$url][2]+=$v; $current_priority+=$v; $this->tab_values[$url][3].='1';} else $this->tab_values[$url][3].='0';
             }
            else $this->tab_values[$url][3].='00'; 

            if ($current_priority > $this->max_priority) $this->max_priority = $current_priority;
            if ($current_priority !== $this->max_priority) $this->no_priority = false;
          }
          
         $this->tabs .= '<div class="tabs"><table cellpadding="0" cellspacing="0" border="0" width="100%"><tr>';
         if ($this->level > 0) $this->tabs .= '<td><img src="/admin/images/px.gif" alt="" width="'.($this->level*20).'" height="1"></td>';
         $i = 0;
         foreach ($this->tab_values as $url => $tab)
          {
            $this->tabs .= '<td class="h_menu_sep">&nbsp;</td>';
            $this->tabs .= '<td id="tab_'.$this->level.'_'.$i.'" nowrap class="h_menu';
            if ($this->auto_detect_page &&
                $tab[2] == $this->max_priority &&
                !$this->no_priority) $this->tabs .= '_sel';
            elseif ($tab[1] == 1) $this->tabs .= '_sel';
            $this->tabs .= '"><a href="'.$url.'" class="hmenu">'.$tab[0].'</a></td>';
            $i++;
          }
         if (count($this->tab_values) > 0)
          {
            $this->tabs .= '<td class="h_menu_sep" width="100%">&nbsp;</td></tr></table></div>';
            echo $this->tabs;
         }
       }
    }
 }
?>
