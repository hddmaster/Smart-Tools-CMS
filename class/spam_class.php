<?
// Класс определения спама

class Spam
 {
   // Текст для анализа
   var $text;

   // Переменные для принятия решения
   var $yes = 0;
   var $no = 0;
   
   // Запрещенные слова
   var $words = array ('casino',
                       'poker',
                       'blackjack',
                       'roulette',
                       'bingo');

   // Запрещенные выражения
   var $expressions = array ('<h1>',
                             '<h2>',
                             '<h3>',
                             '<p>',
                             '<span>',
                             '<div>',
                             '<strong>',
                             '<b>',
                             '<i>',
                             '<hr>',
                             '<u>',
                             '<br>',
                             '<a>',
                             'url',
                             'href');

   function Spam($text)
    {
      $this->text = strtolower($text);
      $this->anylise();
    }

   function add_yes($value)
    {
      $this->yes += $value;
    }
    
   function add_no($value)
    {
      $this->no += $value;
    }

   function check_words()
    {
      foreach ($this->words as $word)
      if (preg_match("/$word/", $this->text)) $this->add_yes(1);
    }
    
   function check_expressions()
    {
      foreach ($this->expressions as $expression)
      if (preg_match("/$expression/", $this->text)) $this->add_yes(1);
    }

   // Запускаем правила
   function anylise()
    {
      $this->check_words();
      $this->check_expressions();
    }

   function is_spam()
    {
      if ($this->yes > $this->no) return true;
      else return false;
    }
 }

?>
