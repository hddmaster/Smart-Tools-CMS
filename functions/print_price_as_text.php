<?
function print_price_as_text($value,
                             $currency_first_word = 'руб.',
                             $currency_second_word = 'коп.') {
    $digits = array('один',
                    'два',
                    'три',
                    'четыре',
                    'пять',
                    'шесть',
                    'семь',
                    'восемь',
                    'девять');
    $decades  = array(  'десять',
                        'двадцать',
                        'тридцать',
                        'сорок',
                        'пятьдесят',
                        'шестьдесят',
                        'семьдесят',
                        'восемьдесят',
                        'девяносто');
    $decades_20  = array(   'одиннадцать',
                            'двенадцать',
                            'тринадцать',
                            'четырнадцать',
                            'пятнадцать',
                            'шестнадцать',
                            'семнадцать',
                            'восемьнадцать',
                            'девятнадцать');
    $hundreds = array(  'сто',
                        'двести',
                        'триста',
                        'четыреста',
                        'пятьсот',
                        'шестьсот',
                        'семьсот',
                        'восемьсот',
                        'девятьсот');
    $thousands = array('одна',
                        'две',
                        'три',
                        'четыре',
                        'пять',
                        'шесть',
                        'семь',
                        'восемь',
                        'девять');
   
   $rub = floor($value);
   $kop = round($value*100 - $rub*100);
   if ($kop < 10) $kop = '0'.$kop;
   $str = '';
   
   if ($rub > 999999) return $value;
  
   if (strlen(strval($rub)) >= 2)
    {
      //десятки
      $dec = $rub-floor($rub/100)*100;
      if ($dec < 10 ) $str .= $digits[$dec-1];
      elseif ($dec > 10 && $dec < 20) $str .= $decades_20[$dec-11];
      else
       {
         $str .= $decades[floor($dec/10)-1];
         $dec_ = $dec - floor($dec/10)*10;
         if ($dec_ > 0) $str .= ' '.$digits[$dec_-1];
       }
       
      //сотни
      $hun = floor(($rub-floor($rub/1000)*1000)/100);
      if ($hun > 0) $str = $hundreds[$hun-1].' '.$str;
      
      //тысячи
      $thous = floor(($rub-floor($rub/1000000)*1000000)/1000);
      //добавление слова
      if ($thous > 0)
       {
          $thousand_word = 'тысяч';
          $last_symbol_thousand = $thous - floor($thous/10)*10;
          $last_two_symbols_thousand = $thous - floor($thous/100)*100;
          if ($last_two_symbols_thousand != 11 && $last_symbol_thousand == 1) $thousand_word = 'тысяча';
          elseif (($last_symbol_thousand == 2 || $last_symbol_thousand == 3 || $last_symbol_thousand == 4) && ($thous < 5 || $thous > 20)) $thousand_word = 'тысячи';
          $str = ' '.$thousand_word.' '.$str;
       }
      if ($thous < 10 ) $str = $thousands[$thous-1].$str;
      elseif ($thous > 10 && $thous < 20) $str = $decades_20[$thous-11].$str;
      elseif ($thous >= 20 && $thous < 100 || $thous == 10)
       {
         $dec_ = $thous - floor($thous/10)*10;
         if ($dec_ > 0) $str = $thousands[$dec_-1].' '.$str;
         $str = $decades[floor($thous/10)-1].' '.$str;
       }
      else
       {
        $dec_ = $thous - floor($thous/100)*100;
        if ($dec_ < 10) $str = $thousands[$dec_-1].' '.$str;
        elseif ($dec_ > 10 && $dec_ < 20) $str = $decades_20[$dec_-11].' '.$str;
        else //($dec_ >= 20 && $dec_ < 100 || $dec_ == 10)
         {
           $dig_ = $dec_ - floor($dec_/10)*10;
           if ($dig_ > 0) $str = $thousands[$dig_-1].' '.$str;
           $str = $decades[floor($dec_/10)-1].' '.$str;
         }
        $str = $hundreds[floor($thous/100)-1].' '.$str;
       }
       
       //для миллионов
       //...
       //...
    }
   else
    {
      if ($rub == 0) $str .= 'ноль';
      else $str .= $digits[$rub-1];
    }
   
   if ($currency_first_word == 'руб.' && $currency_second_word == 'коп.')
   {
   $rub_word = 'рублей';
   $last_symbol_rub = $rub - floor($rub/10)*10;
   $last_two_symbols_rub = $rub - floor($rub/100)*100;
   if ($last_two_symbols_rub != 11 && $last_symbol_rub == 1) $rub_word = 'рубль';
   elseif (($last_symbol_rub == 2 || $last_symbol_rub == 3 || $last_symbol_rub == 4) && ($rub < 5 || $rub > 20)) $rub_word = 'рубля';

   $kop_word = 'копеек';
   $last_symbol_kop = $kop - floor($kop/10)*10;
   $last_two_symbols_kop = $kop - floor($kop/100)*100;
   if ($last_two_symbols_kop != 11 && $last_symbol_kop == 1) $kop_word = 'копейка';
   elseif (($last_symbol_kop == 2 || $last_symbol_kop == 3 || $last_symbol_kop == 4) && ($kop < 5 || $kop > 20)) $kop_word = 'копейки';
   }
   else
   {
     $rub_word = $currency_first_word;
     $kop_word = $currency_second_word;
   }
   return $str.' '.$rub_word.' '.$kop.' '.$kop_word;
 }
 
function show_price($value)
 {
   $price = strval(intval($value));
   $i = 0;
   $out = '';
   $out_price = array();
   $out_price_str = '';
   $k = 0;
   for($i = strlen($price); $i >= 0; $i--)
    {
      $out_price[] = substr($price,$i,1);
      if ($k == 3) {$k = 0; $out_price[] = ' ';}
      $k++;
    }  
   $out_price = array_reverse($out_price);
   foreach ($out_price as $val) $out_price_str .= $val;
   $out .= $out_price_str;
   return $out;
 }
?>