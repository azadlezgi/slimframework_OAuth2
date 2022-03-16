<?php
namespace App\Controller;


class App
{
    public static function debug($arr)
    {
        echo '<pre>'. print_r($arr, true) .'</pre>';
    }

    public static function stripinput($text) {
        if (!is_array($text)) {
            $text = stripslashes(trim($text));
            $text = preg_replace("/(&amp;)+(?=\#([0-9]{2,3});)/i", "&", $text);
            $search = array("&", "\"", "'", "\\", '\"', "\'", "<", ">", "&nbsp;");
            $replace = array("&amp;", "&quot;", "&#39;", "&#92;", "&quot;", "&#39;", "&lt;", "&gt;", " ");
            $text = str_replace($search, $replace, $text);
        } else {
            foreach ($text as $key => $value) {
                $text[$key] = self::stripinput($value);
            }
        }
        return $text;
    }
}