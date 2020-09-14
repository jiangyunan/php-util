class Str
{
    /**
     * 截断,如果超出,到指定字数的右边最后一个句号
     * @param $content
     */
    public static function substr($content, $start, $length)
    {
        $content = mb_substr($content, $start, $length);
        if (mb_strlen($content) < $length) {
            return $content;
        }

        //获取最后一个符号
        if (!preg_match('#^(.+)[\p{Po}\p{Pe}]#u', $content, $matches)) {
            //获取最后一个分隔符
            preg_match('#^(.+)\pZ#u', $content, $matches);
        }

        if (isset($matches[1])) {
            return $matches[1];
        }

        return $content;
    }

    public static function lang($content)
    {
        $len = strlen($content);
        if ($len <= 0) {
            return 'cn';
        }
        $regEn = '#\w#';

        if (preg_match_all($regEn, $content) / $len > 0.5) {
            return 'en';
        }

        return 'cn';
    }

    /**
     * 清理JSON
     */
    public static function jsonFormat($content)
    {
        $content = urldecode($content);
        $content = html_entity_decode($content);
        $content = stripslashes($content);

        for ($i = 0; $i <= 31; ++$i) {
            $content = str_replace(chr($i), "", $content);
        }
        $content = str_replace(chr(127), "", $content);

        if (0 === strpos(bin2hex($content), 'efbbbf')) {
            $content = substr($content, 3);
        }

        return $content;
    }

    /**
     * 去掉链接
     * @param $content
     * @return string|string[]|null
     */
    public static function delLink($content)
    {
        return preg_replace('#[a-zA-Z]+://[^\s]+#', '', $content);
    }

    /**
     * 去掉@
     * @param $content
     * @return string|string[]|null
     */
    public static function delAt($content)
    {
        return preg_replace('#@(\w+)?#', '', $content);
    }

    /**
     * 去掉话题
     */
    public static function delSymbol($content)
    {
        return str_replace(['#'], '', $content);
    }

    /**
     * 删除emoj字符
     * @author jiangyunan<jiangyunan@carnoc.com>
     * @since 2020-09-14
     * @method
     */
    public static function removeEmoji($string) {
        // Match Emoticons
        $regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clear_string = preg_replace($regex_emoticons, '', $string);

        // Match Miscellaneous Symbols and Pictographs
        $regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clear_string = preg_replace($regex_symbols, '', $clear_string);

        // Match Transport And Map Symbols
        $regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clear_string = preg_replace($regex_transport, '', $clear_string);

        // Match Miscellaneous Symbols
        $regex_misc = '/[\x{2600}-\x{26FF}]/u';
        $clear_string = preg_replace($regex_misc, '', $clear_string);

        // Match Dingbats
        $regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
        $clear_string = preg_replace($regex_dingbats, '', $clear_string);

        return $clear_string;
    }
}
