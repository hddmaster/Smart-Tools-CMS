<?
define('LOWERCASE', 1);
define('UPPERCASE', 1);

class Url_parser {
    //описание параметров поисковых систем, содержащих поисковые слова
    public $searchengines = array(
                                    'www.yandex.ru' => 'text',          //Yandex
                                    'yandex.ru' => 'text',
                                    'yandex.com' => 'text',
                                    'yandex.kz' => 'text',
                                    'images.yandex.ru' => 'text',
                                    'market.yandex.ru' => 'text',
                                    'direct.yandex.ru' => 'text',
                                    'adresa.yandex.ru' => 'what',
                                    'search.yaca.yandex.ru' => 'text',
                                    'www.rambler.ru' => 'words',        //Rambler
                                    'search.rambler.ru' => 'words',     //Rambler
                                    'nova.rambler.ru' => 'query',       //Rambler
                                    'go.mail.ru' => 'q',                //Mail
                                    'search.list.mail.ru' => 'q',
                                    'search.msn.com' => 'q',            //MSN
                                    'search.live.com' => 'q',
                                    'sm.aport.ru' => 'r',               //Aport
                                    'search.yahoo.com' => 'p',          //Yahoo
                                    'www.alloy.ru' => 'query',          //Alloy
                                    'find.ru' => 'text',                //Find
                                    'webalta.ru' => 'q',                //Webalta
                                    'www.webalta.ru' => 'q',
                                    'gde.ru' => 'keywords',             //Gde.ru
                                    'www.gde.ru' => 'keywords',
                                    'www.nigma.ru' => 's',              //Nigma
                                    'gogo.ru' => 'q',                   //Gogo.ru
                                    'www.gogo.ru' => 'q',
                                    'poisk.ru' => 'text',               //Poisk.ru
                                    'www.poisk.ru' => 'text',   
                                    'www.top4top.ru' => 'query',        //Top4top.ru
                                    'search.qip.ru' => 'query',         //Qip
                                    'www.daemon-search.com' => 'q',     //Daemon Tools
                                    'bing.com' => 'q'                   //Microsoft
                                );
    public $params = array();

    function __construct() {
    }

    public function is_searchengine_url($url) {
        $components = parse_url($url);
        if (is_array($components) && isset($components['host']) && isset($components['query'])) {
            if (array_key_exists('host',$components)) {
                if (array_key_exists($components['host'], $this->searchengines) ||
                    (strpos($url,'.google.') && $components['host'] !== 'images.google.com')) {
                    // есть определенный параметр для этого хоста
                    //if (strpos($components['query'],$this->searchengines[$components['host']]))
                    return true;
                    //else return false;
                }
                else return false;
            }
            else return false;
        }
        else return false;
    }

    public function get_engine_params($url) {
        $components = parse_url($url);
      
        if (isset($components['host']) && isset($components['query'])) {
      
            $this->params['host'] = $components['host'];
            $params = array();
            parse_str($components['query'],$params);

            //описание дополнительных правил для некоторых поисковых систем

            //правила для Yandex
            if ($this->params['host'] == 'www.yandex.ru' ||
                $this->params['host'] == 'yandex.ru' ||
                $this->params['host'] == 'direct.yandex.ru' ||
                $this->params['host'] == 'market.yandex.ru' ||
                $this->params['host'] == 'adresa.yandex.ru' ||
                $this->params['host'] == 'search.yaca.yandex.ru' ||
                $this->params['host'] == 'images.yandex.ru') {
                if (array_key_exists('qs', $params)) {
                    $str = array();
                    parse_str ($params['qs'],$str);
                    $word = str_replace('+', ' ', $str['text']);
                    $word = iconv("KOI8-R", "WINDOWS-1251", $word);
                    $this->params['word'] = stripslashes($word);
                } else {
                    if (isset($params[$this->searchengines[$components['host']]]))
                        $this->params['word'] = stripslashes(urldecode($params[$this->searchengines[$components['host']]]));
                    else
                        $this->params['word'] = '';
                }
            }

            //привило для Google
            elseif (strpos($this->params['host'],'google')) {
                if (isset($params['q']))
                    $this->params['word'] = stripslashes(urldecode($params['q']));
                else
                    $this->params['word'] = '';
            }

            else {
                $this->params['word'] = stripslashes(urldecode($params[$this->searchengines[$components['host']]]));
            }

            //перекодировка из UTF-8, если необходимо
            if ($this->params['host'] == 'search.msn.com' ||
                $this->params['host'] == 'search.live.com' ||
                $this->params['host'] == 'search.yahoo.com' ||
                strpos($this->params['host'],'google')) {
                $word = iconv("UTF-8", "WINDOWS-1251", $this->params['word']);
                if ($word == '') $word = $this->params['word'];
                $this->params['word'] = $word;
            }


            //перекодировка в windows-1251, если это необходимо
            $code = $this->detect_cyr_charset($this->params['word']);
            if ($code == 'iso-8859-5') $this->params['word'] = iconv("UTF-8", "WINDOWS-1251", $this->params['word']);
            //$this->params['word'] .= ' ('.$code.')';

            if ($this->params['word'] == '') $this->params['word'] = "<no detection>";
        }
        else
            $this->params['word'] = "<no detection>";
      
        if(preg_match_all('/%u\d{4}/',$this->params['word'],$match)) {
            if (count($match[0]) > 2)
                $this->params['word'] = iconv("UTF-8", "WINDOWS-1251", $this->decode_unicode_url($this->params['word']));
        }
       
        //для системы управления
         $this->params['word'] = iconv("WINDOWS-1251", "UTF-8", $this->params['word']);

        return $this->params;
    }

    public function detect_cyr_charset($str) {
        $charsets = Array(
                            'koi8-r' => 0,
                            'win-1251' => 0,
                            'cp866' => 0,
                            'iso-8859-5' => 0,
                            'mac' => 0
                            );
                        
        for ( $i = 0, $length = strlen($str); $i < $length; $i++ ) {
            $char = ord($str[$i]);
            //non-russian characters
            if ($char < 128 || $char > 256) continue;

            //CP866
            if (($char > 159 && $char < 176) || ($char > 223 && $char < 242)) $charsets['cp866']+=LOWERCASE;
            if (($char > 127 && $char < 160)) $charsets['cp866']+=UPPERCASE;

            //KOI8-R
            if (($char > 191 && $char < 223)) $charsets['koi8-r']+=LOWERCASE;
            if (($char > 222 && $char < 256)) $charsets['koi8-r']+=UPPERCASE;

            //WIN-1251
            if ($char > 223 && $char < 256) $charsets['win-1251']+=LOWERCASE;
            if ($char > 191 && $char < 224) $charsets['win-1251']+=UPPERCASE;

            //MAC
            if ($char > 221 && $char < 255) $charsets['mac']+=LOWERCASE;
            if ($char > 127 && $char < 160) $charsets['mac']+=UPPERCASE;

            //ISO-8859-5
            if ($char > 207 && $char < 240) $charsets['iso-8859-5']+=LOWERCASE;
            if ($char > 175 && $char < 208) $charsets['iso-8859-5']+=UPPERCASE;
        }
      
        arsort($charsets);
        return key($charsets);
    }

    public function decode_unicode_url($str) {
        $res = '';

        $i = 0;
        $max = strlen($str) - 6;
        while ($i <= $max) {
            $character = $str[$i];
            if ($character == '%' && $str[$i + 1] == 'u') {
                $value = hexdec(substr($str, $i + 2, 4));
                $i += 6;

                if ($value < 0x0080) // 1 byte: 0xxxxxxx
                    $character = chr($value);
                elseif ($value < 0x0800) // 2 bytes: 110xxxxx 10xxxxxx
                    $character = chr((($value & 0x07c0) >> 6) | 0xc0).chr(($value & 0x3f) | 0x80);
                else // 3 bytes: 1110xxxx 10xxxxxx 10xxxxxx
                    $character = chr((($value & 0xf000) >> 12) | 0xe0).chr((($value & 0x0fc0) >> 6) | 0x80).chr(($value & 0x3f) | 0x80);
            }
            else $i++;
            $res .= $character;
        }
        return $res.substr($str, $i);
    }
}
?>