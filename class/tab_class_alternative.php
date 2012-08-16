<?
class Tabs
 {
   public $tab_values = array();
   public $tabs = '';
   public $auto_detect_page = true;
   public $level = 0;
   public $script;
    
   public function __construct($script)
    {
      $this->script = (($script) ? $script : $_SERVER['PHP_SELF']);
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
           if($url == $this->script) $this->tab_values[$url][1] = 1;
          
         $this->tabs .= '<div class="tabs"><table cellpadding="0" cellspacing="0" border="0" width="100%"><tr>';
         if ($this->level > 0) $this->tabs .= '<td><img src="/admin/images/px.gif" alt="" width="'.($this->level*30).'" height="1"></td>';
         foreach ($this->tab_values as $url => $tab)
          {
            $this->tabs .= '<td class="h_menu_sep"><img src="/admin/images/px.gif" alt="" width="1" height="1"></td>';
            $this->tabs .= '<td nowrap><div class="h_menu';
            if ($tab[1] == 1) $this->tabs .= '_sel';
            $this->tabs .= '" onclick="location.href=\''.$url.'\'"><a href="'.$url.'" class="hmenu">'.$tab[0].'</a></div></td>';
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
