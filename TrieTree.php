<?php
/**
 * 这是一个支持中文的字典树
 * Class TrieTree
 * 
 * 在PHP-TrieTree基础上修改而成
 * @see https://github.com/abelzhou/PHP-TrieTree
 * 
 * 增加从文件中获取词
 * 增加序列化存储
 * 增加模糊搜索， 用于敏感词过滤
 */
class TrieTree {
    protected $nodeTree = [];

    /**
     * 从文件初始化 格式:每行一个词
     */
    public function loadFile($file)
    {
        if (!is_file($file)) {
            return false;
        }

        $handle = @fopen($file, "r");
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                $buffer = trim(str_replace(["\n", "\r"], "", $buffer));
                if (!$buffer) {
                    continue;
                }

                $this->append($buffer);
            }

            if (!feof($handle)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 序列化存储
     * @param $file 序列化文件路径
     */
    public function serialize($file)
    {
        return @file_put_contents($file, serialize($this));
    }

    /**
     * 从序列化文件中读取
     * @param $file 序列化文件路径
     * @return TrieTree
     */
    public static function unserialize($file)
    {
        return unserialize(file_get_contents($file));
    }
    /**
     * 从树种摘除一个文本
     * @param $indexStr
     */
    public function delete($indexStr, $deltree = false, $isPy = false, $chinese = "") {
        $str = trim($indexStr);
        $chinese = trim($chinese);
        if ($isPy && empty($chinese)) {
            return false;
        }

        $delstrArr = $this->convertStrToH($str);
        $len = count($delstrArr);
        //提取树
        $childTree = &$this->nodeTree;
        $delIndex = array();
        //提取树中的相关索引
        for ($i = 0; $i < $len; $i++) {
            $code = $delstrArr[$i];
            //命中将其纳入索引范围
            if (isset($childTree[$code])) {
                //del tree
                $delIndex[$i] = [
                    'code' => $code,
                    'index' => &$childTree[$code]
                ];
                //若检索到最后一个字，检查是否是一个关键词的末梢
                if ($i == ($len - 1) && !$childTree[$code]['end']) {
                    return false;
                }
                $childTree = &$childTree[$code]['child'];
            } else {
                //发现没有命中 删除失败
                return false;
            }
        }
        $idx = $len - 1;
        //删除整棵树
        if ($deltree) {
            //清空子集
            $delIndex[$idx]['index']['child'] = array();
        }
        //只有一个字 直接删除
        if ($idx == 0) {
            //如果是拼音 只删除相应的拼音索引
            if ($isPy) {
                //清除单个拼音索引
                if (isset($this->nodeTree[$delIndex[$idx]['code']]['chineseList'])) {
                    $isDel = false;
                    foreach ($this->nodeTree[$delIndex[$idx]['code']]['chineseList'] as $key=>$node) {
                        if ($node['word'] == $chinese){
                            unset($this->nodeTree[$delIndex[$idx]['code']]['chineseList'][$key]);
                            $isDel = true;
                            break;
                        }
                    }
                    if($isDel && 0 != count($this->nodeTree[$delIndex[$idx]['code']]['chineseList'])){
                         return true;
                    }
                    if(!$isDel){
                        return false;
                    }
                    //如果依然存在中文数据 则继续向下跑删除节点
                }
            }else{
                if (count($delIndex[$idx]['index']['child']) == 0) {
                    unset($this->nodeTree[$delIndex[$idx]['code']]);
                    return true;
                }
            }

        }
        //末梢为关键词结尾，且存在子集 清除结尾标签
        if (count($delIndex[$idx]['index']['child']) > 0) {
            $delIndex[$idx]['index']['end'] = false;
            $delIndex[$idx]['index']['data'] = array();
            unset($delIndex[$idx]['index']['full']);
            return true;
        }
        //以下为末梢不存在子集的情况
        //倒序检索 子集大于2的 清除child
        for (; $idx >= 0; $idx--) {
            //检测子集 若发现联字情况 检测是否为其他关键词结尾
            if (count($delIndex[$idx]['index']['child']) > 0) {
                //遇到结束标记或者count>1的未结束节点直接清空子集跳出
                if ($delIndex[$idx]['index']['end'] == true || $delIndex[$idx]['index']['child'] > 1) {
                    //清空子集
                    $childCode = $delIndex[$idx + 1]['code'];
                    unset($delIndex[$idx]['index']['child'][$childCode]);
                    return true;
                }

            }
        }
        return false;
    }

    /**
     * ADD word [UTF8]
     * 增加新特性，在质感末梢增加自定义数组
     * @param $indexStr 添加的词
     * @param array $data 添加词的附加属性
     * @return $this
     */
    public function append($indexStr, $data = array(), $isPy = false, $chinese = '') {
        $str = trim($indexStr);
        $chinese = trim($chinese);
        if ($isPy && empty($chinese)) {
            return false;
        }

        $childTree = &$this->nodeTree;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $asciiCode = ord($str[$i]);
            $code = NULL;
            $word = NULL;
            $isEnd = false;

            if (($asciiCode >> 7) == 0) {
                $code = dechex(ord($str[$i]));
                $word = $str[$i];

            } else if (($asciiCode >> 4) == 15) {    //1111 xxxx, 四字节
                if ($i < $len - 3) {
                    $code = dechex(ord($str[$i])) . dechex(ord($str[$i + 1])) . dechex(ord($str[$i + 2])) . dechex(ord($str[$i + 3]));
                    $word = $str[$i] . $str[$i + 1] . $str[$i + 2] . $str[$i + 3];
                    $i += 3;
                }
            } else if (($asciiCode >> 5) == 7) {    //111x xxxx, 三字节
                if ($i < $len - 2) {
                    $code = dechex(ord($str[$i])) . dechex(ord($str[$i + 1])) . dechex(ord($str[$i + 2]));
                    $word = $str[$i] . $str[$i + 1] . $str[$i + 2];
                    $i += 2;
                }
            } else if (($asciiCode >> 6) == 3) {    //11xx xxxx, 2字节
                if ($i < $len - 1) {
                    $code = dechex(ord($str[$i])) . dechex(ord($str[$i + 1]));
                    $word = $str[$i] . $str[$i + 1];
                    $i++;
                }
            }
            if ($i == ($len - 1)) {
                $isEnd = true;
                if ($isPy) {
                    $str = $chinese;
                }
            }
            $childTree = &$this->AppendWordToTree($childTree, $code, $word, $isEnd, $data, $str, $isPy);
        }
        unset($childTree);
        return $this;
    }

    /**
     * 追加一个字[中英文]到树中
     * @param $tree
     * @param $code
     * @param $word
     * @param bool $end
     * @param array $data
     * @param string $fullStr
     * @return mixed
     */
    private function &AppendWordToTree(&$tree, $code, $word, $end = false, $data = array(), $fullStr = '', $isPy = false) {
        if (!isset($tree[$code])) {
            $tree[$code] = array(
                'end' => $end,
                'child' => array(),
                'value' => $word,
            );
        }
        if ($end) {
            $tree[$code]['end'] = true;
            $tree[$code]['isPy'] = $isPy;
            //拼音不需要full 拼音根据读音多样性对应多个词语 重复词语覆盖data
            if ($isPy) {
                $isChange = false;
                if(isset($tree[$code]["chineseList"]) && count($tree[$code]["chineseList"])>0) {
                    foreach ($tree[$code]["chineseList"] as $key => &$node) {
                        if ($node['word'] == $fullStr) {
                            $node['data'] = $data;
                            $isChange = true;
                            break;
                        }
                    }
                }
                if(!$isChange){
                    $tree[$code]['chineseList'][] = ["word" => $fullStr, "data" => $data];
                }
            } else {
                $tree[$code]['full'] = $fullStr;
                $tree[$code]['data'] = $data;
            }
        }

        return $tree[$code]['child'];
    }

    /**
     * 获得整棵树
     * @return array
     */
    public function getTree() {
        return $this->nodeTree;
    }

    /**
     * 匹配下面的全部词语
     * @param $word
     * @param int $deep 检索深度 检索之后的词语数量可能会大于这个数字
     * @return array|bool
     */
    public function getTreeWord($word, $deep = 0) {
        $search = trim($word);
        if (empty($search)) {
            return false;
        }
        if ($deep === 0) {
            $deep = 999;
        }

        $wordKeys = $this->convertStrToH($search);
        $tree = &$this->nodeTree;
        $keyCount = count($wordKeys);
        $words = [];
        foreach ($wordKeys as $key => $val) {
            if (isset($tree[$val])) {
                //检测当前词语是否已命中
                if ($key == $keyCount - 1 && $tree[$val]['end'] == true) {
                    if (isset($tree[$val]['chineseList'])) {
                        $words = arrayMerge($words, $tree[$val]['chineseList']);
                    } else {
                        $words[] = ["word" => $tree[$val]['full'], "data" => $tree[$val]['data']];
                    }
                }
                $tree = &$tree[$val]["child"];
            } else {
                //遇到没有命中的返回
//                if ($key == 0) {
                    return [];
//                }
            }
        }
        $this->_getTreeWord($tree, $deep, $words);
        return $words;
    }

    private function _getTreeWord(&$child, $deep, &$words = array()) {
        foreach ($child as $node) {
            if ($node['end'] == true) {
                if (isset($node['chineseList'])) {
                    $words = arrayMerge($words, $node['chineseList']);
                } else {
                    $words[] = ["word" => $node['full'], "data" => $node['data']];
                }
            }
            if (!empty($node['child']) && $deep >= count($words)) {
                $this->_getTreeWord($node['child'], $deep, $words);
            }
        }
    }

    /**
     * overwrite tostring.
     * @return string
     */
    public function _ToString() {
        // TODO: Implement _ToString() method.
        return jsonEncode($this->nodeTree);
    }


    /**
     * 检索
     * @param $search
     * @return array|bool
     */
    public function search($search) {
        $search = trim($search);
        if (empty($search)) {
            return false;
        }
        $searchKeys = $this->convertStrToH($search);
        //命中集合
        $hitArr = array();
        $tree = &$this->nodeTree;
        $arrLen = count($searchKeys);
        $currentIndex = 0;
        for ($i = 0; $i < $arrLen; $i++) {
            //若命中了一个索引 则继续向下寻找
            if (isset($tree[$searchKeys[$i]])) {
                $node = $tree[$searchKeys[$i]];
                if ($node['end']) {
                    //发现结尾 将原词以及自定义属性纳入返回结果集中 3.1 增加词频统计
                    $key = md5($node['full']);
                    if (isset($hitArr[$key])) {
                        $hitArr[$key]['count'] += 1;
                    } else {
                        $hitArr[$key] = array(
                            'word' => $node['full'],
                            'data' => $node['data'],
                            'count' => 1
                        );
                    }
                    if (empty($node['child'])) {
                        //若不存在子集，检索游标还原
                        $i = $currentIndex;
                        //还原检索集合
                        $tree = &$this->nodeTree;
                        //字码游标下移
                        $currentIndex++;
                    } else {
                        //存在子集重定义检索tree
                        $tree = &$tree[$searchKeys[$i]]['child'];
                    }
                    continue;
                } else {
                    //没发现结尾继续向下检索
                    $tree = &$tree[$searchKeys[$i]]['child'];
                    continue;
                }
            } else {
                //还原检索起点
                $i = $currentIndex;
                //还原tree
                $tree = &$this->nodeTree;
                //字码位移
                $currentIndex++;
                continue;
            }
        }

        unset($tree, $searchKeys);
        return $hitArr;
    }

    /**
     * 模糊查找
     * @param $search 要查找的文本
     * @param $len 两字之间允许的非汉字距离, 比如为3, "安123徽" 匹配, "安1234徽" 不匹配, "安一微"不匹配
     */
    public function fuzzySearch($search, $len = 5)
    {
        $len += 1;
        $search = trim($search);
        if (empty($search)) {
            return false;
        }
        $searchKeys = $this->convertStrToH($search);
        //命中集合
        $hitArr = array();
        while ($searchKeys) {
            $tree = &$this->nodeTree;
            $arrLen = count($searchKeys);
            $currentIndex = 0;
            $wordLen = 0;
            for ($i = 0; $i < $arrLen; $i++) {
                if ($wordLen > $len) {
                    break;
                }
                //若命中了一个索引 则继续向下寻找
                if (isset($tree[$searchKeys[$i]])) {
                    $node = $tree[$searchKeys[$i]];
                    $wordLen = 1;
                    if ($node['end']) {
                        //发现结尾 将原词以及自定义属性纳入返回结果集中 3.1 增加词频统计
                        $key = md5($node['full']);
                        $hitArr[$key] = $node['full'];
                        if (empty($node['child'])) {
                            //若不存在子集，检索游标还原
                            $i = $currentIndex;
                            //还原检索集合
                            $tree = &$this->nodeTree;
                            //字码游标下移
                            $currentIndex++;
                        } else {
                            //存在子集重定义检索tree
                            $tree = &$tree[$searchKeys[$i]]['child'];
                        }
                        continue;
                    } else {
                        //没发现结尾继续向下检索
                        $tree = &$tree[$searchKeys[$i]]['child'];
                        continue;
                    }
                } else {
                    if ($wordLen > 0) {
                        $wordLen ++;
                    }
                    // 如果字符为汉字, 还原检索起点
                    if (preg_match("/\p{Han}/u", $this->hexToChar($searchKeys[$i]))) {
                        //还原检索起点
                        $i = $currentIndex;
                        //还原tree
                        $tree = &$this->nodeTree;
                    }
                    //字码位移
                    $currentIndex++;
                    continue;
                }
            }
            array_shift($searchKeys);
        }

        unset($tree, $searchKeys);
        return $hitArr;
    }

    /**
     * 将字符转为16进制标示
     * @param $str
     * @return array
     */
    public function convertStrToH($str) {
        $len = strlen($str);
        $chars = [];
        for ($i = 0; $i < $len; $i++) {
            $asciiCode = ord($str[$i]);
            if (($asciiCode >> 7) == 0) {
                $chars[] = dechex(ord($str[$i]));
            } else if (($asciiCode >> 4) == 15) {    //1111 xxxx, 四字节
                if ($i < $len - 3) {
                    $chars[] = dechex(ord($str[$i])) . dechex(ord($str[$i + 1])) . dechex(ord($str[$i + 2])) . dechex(ord($str[$i + 3]));
                    $i += 3;
                }
            } else if (($asciiCode >> 5) == 7) {    //111x xxxx, 三字节
                if ($i < $len - 2) {
                    $chars[] = dechex(ord($str[$i])) . dechex(ord($str[$i + 1])) . dechex(ord($str[$i + 2]));
                    $i += 2;
                }
            } else if (($asciiCode >> 6) == 3) {    //11xx xxxx, 2字节
                if ($i < $len - 1) {
                    $chars[] = dechex(ord($str[$i])) . dechex(ord($str[$i + 1]));
                    $i++;
                }
            }
        }
        return $chars;
    }

    public function hexToChar($hex)
    {
        $str = '';
        $len = strlen($hex);
        for ($i = 0; $i < $len - 1; $i += 2) {
            $str .= chr(hexdec($hex[$i]. $hex[$i+1]));
        }

        return $str;
    }
}
/*
$tree = new TrieTree();

$tree->loadFile("账号敏感词.txt");
// 序列化存储
$r = $tree->serialize("TrieTree_wfc");

// 从序列化文件载入
//$tree = TrieTree::unserialize('TrieTree_wfc');
// var_dump($res);

$r = $tree->fuzzySearch('习12a近34#！@平', 7);
var_dump($r);

$r = $tree->convertStrToH('习12近34#！@平');
var_dump($r);
foreach ($r as $v) {
    var_dump($tree->hexToChar($v));
}
*/
