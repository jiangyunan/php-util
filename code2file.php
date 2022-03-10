<?php
/**
 * 变量生成php文件
*/
class Code2File
{
    /**
     * 符合新语法的var_export
     * @param any $expression 需要处理的变量
     * @return string
     * @see https://www.php.net/manual/zh/function.var-export.php#122853
     */
    public function varExport($expression) {
        $export = var_export($expression, true);
        $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [null, ']$1', ' => ['], $array);
        $export = join(PHP_EOL, array_filter(["["] + $array));
        return $export;
    }

    /**
     * 压缩变量生成的字符串
     * @param string $export 转成字符串的变量
     * @return string
     */
    public function compress($export)
    {
        $export = preg_replace("/\r\n/m", '', $export); //删除换行
        $export = preg_replace("/\s+/m", '', $export);  //删除空格
        return $export;
    }

    /**
     * 生成文件
     * @param string $export 转成字符串的变量
     * @param string $filepath 文件路径
     */
    public function saveFile($export, $filepath)
    {
        $export = "<?php\nreturn " . $export . ";\n";
        file_put_contents($filepath, $export);
    }
}