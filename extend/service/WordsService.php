<?php

// +----------------------------------------------------------------------
// | Think.Admin
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/Think.Admin
// +----------------------------------------------------------------------

namespace service;

use think\Db;
use think\Log;

/**
 * 敏感词服务
 * Class WordsService
 * @package service
 * @author zuohy
 * @date 2017/12/22 15:32
 */
class WordsService {

    // trie-tree对象
    private static $_resTrie = null;
    // 字典树的更新时间
    private static $_mtime = null;

    private static $_allTag = array(",", "/", "\\", ".", ";", ":", "\"", "!", "~", "`", "^", "(", ")", "?", "-", "\t", "\n", "'", "<", ">", "\r", "\r\n","$", "&", "%", "#", "@", "+", "=", "{", "}", "[", "]", "：", "）", "（", "．", "。", "，", "！", "；", "“", "”", "‘", "’", "［", "］", "、", "—", "　", "《", "》", "－", "…", "【", "】",);
    private static $_midTag = array(".", ",", "!", "?", "\r", "\r\n", "。", "，", "！", "？", "；");
    public $wordLeader = array('习近平', '张文强', '曾微', '张宗良');
    public $wordJob = array('中共中央总书记', '黔南州委常委、瓮安县委书记', '瓮安县委常委', '瓮安县委常委');

    /**
     * 防止初始化
     */
    //private function __construct() {}

    /**
     * 防止释放对象
     */
    public function __destruct()
    {
        if (is_resource(self::$_resTrie)) {
            trie_filter_free(self::$_resTrie);
        }
    }

    /**
     * 防止克隆对象
     */
    private function __clone() {}



    /**
     * 生成字典文件
     *
     * @param $tree_file 字典树文件路径
     * @param $arr_words 关键字
     * @return null
     */
    static public function makeResTrie($tree_file, $arr_words) {

        //$arrWord = array('习近平', '张文强', '曾微', '张宗良', 'a', 'ab', 'abc', 'b');
        $arrWord = $arr_words;
        $resTrie = trie_filter_new(); //create an empty trie tree
        foreach ($arrWord as $k => $v) {
            trie_filter_store($resTrie, $v);
        }
        trie_filter_save($resTrie, __DIR__ . '/blackword.tree');
    }


    /**
     * 提供trie-tree对象
     *
     * @param $tree_file 字典树文件路径
     * @param $new_mtime 当前调用时字典树的更新时间
     * @return null
     */
    static public function getResTrie($tree_file, $new_mtime) {

        if (is_null(self::$_mtime)) {
            self::$_mtime = $new_mtime;
        }

        if (($new_mtime != self::$_mtime) || is_null(self::$_resTrie)) {
            self::$_resTrie = trie_filter_load($tree_file);
            self::$_mtime = $new_mtime;

            // 输出字典文件重载时间
            //echo date('Y-m-d H:i:s') . "\tdictionary reload success!\n";
        }

        return self::$_resTrie;
    }

    /**
     * 从原字符串中提取过滤出的敏感词
     *
     * @param $str 原字符串
     * @param $res 1-3 表示 从位置1开始，3个字符长度
     * @return array
     */
    static public function getFilterWords($str, $res)
    {
        $result = array();
        foreach ($res as $k => $v) {
            $word = substr($str, $v[0], $v[1]);

            if (!in_array($word, $result)) {
                $result[] = $word;
            }
        }

        return $result;
    }


    /**
     * 查找第一个匹配的关键字
     *
     * @param $treeHandle 字典树句柄
     * @param $strContent 检测的字符串
     * @return null
     */
    static public function searchFirstWords($treeHandle, $strContent) {

        if (is_null(self::$_resTrie)) {
            return;
        }

        $arrRet = trie_filter_search(self::$_resTrie, $strContent);
        //print_all($str,array($arrRet)); //Array(0 => 6, 1 => 5)


        return $arrRet;
    }

    /**
     * 查找所有匹配的关键字
     *
     * @param $treeHandle 字典树句柄
     * @param $strContent 检测的字符串
     * @return null
     */
    static public function searchAllWords($treeHandle, $strContent) {

        if (is_null(self::$_resTrie)) {
            return;
        }

        $arrRet = trie_filter_search_all(self::$_resTrie, $strContent);
        //print_all($str, $arrRet); //Array(0 => 6, 1 => 5)


        return $arrRet;
    }


    /**
     * 固定匹配关键字 (匹配职务) 纯英文
     *
     * @param $startType 匹配的方式， 0 为倒序匹配，1 为正向匹配
     * @param $startPos 匹配检测的起始位置
     * @param $keyStr 匹配的关键字
     * @param $strContent 匹配的目标字符串
     * @return null
     */
    static public function matchPosString($startType=0, $keyStr, $startPos, $strContent) {

        $isMatch = true;

        if (is_null($strContent)) {
            return;
        }

        $strLen = strlen($strContent);
        $keyStrLen = strlen($keyStr);
        $pos=$keyStrLen-1;   //匹配开始的位置 默认反向
        //异常检查
        if($startPos > $strLen){
            return;
        }

        if( 0 == $startType ){
            //反向匹配
            //$pos=$keyStrLen;   //匹配开始的位置
            for( $pos; $pos>=0; $pos-- ){
                if($startPos < 0 ){
                    //检测的字符串没有关键字长，返回false
                    $isMatch = false;
                    break;
                }

                if($keyStr[$pos] != $strContent[$startPos]){
                    $isMatch = false;
                    break;
                }

                $startPos--;
            }

            if($pos < 0 ){
                //完全匹配
                $isMatch = true;
            }
        }else{
            //正向匹配
            $pos=0;   //匹配开始的位置
            for( $pos; $pos<$keyStrLen; $pos++ ){
                if($startPos > $strLen ){
                    //检测的字符串没有关键字长，返回false
                    $isMatch = false;
                    break;
                }

                if($keyStr[$pos] != $strContent[$startPos]){
                    $isMatch = false;
                    break;
                }

                $startPos++;
            }

            if($pos > $keyStrLen ){
                //完全匹配
                $isMatch = true;
            }

        }

        $arrRet = array(
            'isMatch' => $isMatch,
            'pos' => $startPos
            );
        return $arrRet;
    }

    /**
     * 字符比对匹配关键字 纯英文
     *
     * @param $startType 匹配的方式， 0 为倒序匹配，1 为正向匹配
     * @param $startPos 匹配检测的起始位置
     * @param $keyStr 匹配的关键字
     * @param $strContent 匹配的目标字符串
     * @return null
     */
    static public function matchString($startType=0, $keyStr, $strContent) {

        $isMatch = true;

        if (is_null($strContent)) {
            return;
        }

        $strLen = strlen($strContent);
        $keyStrLen = strlen($keyStr);
        $pos=$strLen;   //匹配开始的位置 默认反向
        $keyPos = 0;   //关键字开始的位置


        if( 0 == $startType ){
            //反向匹配
            //$pos=$keyStrLen;   //匹配开始的位置
            for( $pos; $pos>=0; $pos-- ){


                if($keyStr[$keyPos] != $strContent[$pos]){
                    $isMatch = false;
                    $keyPos = 0;
                    continue;
                }

                if($keyPos > $keyStrLen ){
                    //完全匹配
                    $isMatch = true;
                    break;
                }
            }


        }else{
            //正向匹配
            $pos=0;   //匹配开始的位置
            for( $pos; $pos<$strLen; $pos++ ){

                $keyValue = $keyStr[$keyPos];
                $strValue = $strContent[$pos];
                if($keyStr[$keyPos] != $strContent[$pos]){
                    $isMatch = false;
                    $keyPos = 0;
                    continue;
                }

                $keyPos++;

                if($keyPos >= $keyStrLen ){
                    //完全匹配
                    $isMatch = true;
                    break;
                }
            }

        }

        $arrRet = array(
            'isMatch' => $isMatch,
            'pos' => $pos
        );
        return $arrRet;
    }


    /**
     * 根据标点符号 将字符串分组
     *
     * @param $strContent 检测的字符串
     * @return null
     */
    static public function groupStr($strContent) {

        if (is_null($strContent)) {
            return;
        }

        $strLen = strlen($strContent);
        $tags = self::$_midTag;

        $mbStrLen = mb_strlen($strContent, 'utf8');
        $aryPhase = array();  //段落分隔
        $aryJu = array();     //句子分隔
        $aryPos = array();    //特殊分隔符号位置

        $aryJu = explode('，', $strContent);
        $contJu = count($aryJu);
        for($index=0; $index<($contJu-1); $index++){
            //补充逗号
            $aryJu[$index] = $aryJu[$index] . '，';
        }
/*
        //查找到特殊字符的位置
        for($pos=0; $pos<=$mbStrLen; $pos++){
            $isFind = false;

            foreach($tags as $key => $keyValue ){
                $strValue = $strContent[$pos];

                $isFind = mb_strpos($strValue, $keyValue);
                if($isFind == true){
                    //找到分隔特殊字符
                    $isFind = false;
                    $aryPos[] = $pos;
                }
            }
            //$retPos = mb_strpos($strContent, '，');
        }

        $startPos = 0;
        foreach($aryPos as $key => $posValue){
            $aryJu[] = mb_substr($strContent, $startPos, $posValue, 'utf-8');
            $startPos = $posValue;
        }
*/

        return $aryJu;
    }


static public function str_split_unicode($str, $l = 0) {
        if ($l > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }


    /**
     * 固定匹配关键字 (匹配职务) 混合汉字 英文
     *
     * @param $startType 匹配的方式， 0 为倒序匹配，1 为正向匹配
     * @param $startPos 匹配检测的起始位置
     * @param $keyStr 匹配的关键字
     * @param $strContent 匹配的目标字符串
     * @return null
     */
    static public function c_matchPosString($startType=0, $keyStr, $startPos, $strContent) {

        $isMatch = true;

        if (is_null($strContent)) {
            return;
        }

        $strLen = mb_strlen($strContent, 'utf8');
        $keyStrLen = mb_strlen($keyStr, 'utf8');
        $pos=$keyStrLen-1;   //匹配开始的位置 默认反向

        $c_strContent = self::str_split_unicode($strContent);
        $c_keyStr = self::str_split_unicode($keyStr);

        //异常检查
        if($startPos > $strLen){
            return;
        }

        if( 0 == $startType ){
            //反向匹配
            //$pos=$keyStrLen;   //匹配开始的位置
            for( $pos; $pos>=0; $pos-- ){
                if($startPos < 0 ){
                    //检测的字符串没有关键字长，返回false
                    $isMatch = false;
                    break;
                }

                if($c_keyStr[$pos] != $c_strContent[$startPos]){
                    $isMatch = false;
                    break;
                }

                $startPos--;
            }

            if($pos < 0 ){
                //完全匹配
                $isMatch = true;
            }
        }else{
            //正向匹配
            $pos=0;   //匹配开始的位置
            for( $pos; $pos<$keyStrLen; $pos++ ){
                if($startPos > $strLen ){
                    //检测的字符串没有关键字长，返回false
                    $isMatch = false;
                    break;
                }

                if($c_keyStr[$pos] != $c_strContent[$startPos]){
                    $isMatch = false;
                    break;
                }

                $startPos++;
            }

            if($pos > $keyStrLen ){
                //完全匹配
                $isMatch = true;
            }

        }

        $arrRet = array(
            'isMatch' => $isMatch,
            'pos' => $startPos
        );
        return $arrRet;
    }

    /**
     * 字符比对匹配关键字 混合汉字 英文
     *
     * @param $startType 匹配的方式， 0 为倒序匹配，1 为正向匹配
     * @param $startPos 匹配检测的起始位置
     * @param $keyStr 匹配的关键字
     * @param $strContent 匹配的目标字符串
     * @return null
     */
    static public function c_matchString($startType=0, $keyStr, $strContent) {

        $isMatch = true;

        if (is_null($strContent)) {
            return;
        }

        $strLen = mb_strlen($strContent, 'utf8');
        $keyStrLen = mb_strlen($keyStr, 'utf8');
        $pos=$strLen;   //匹配开始的位置 默认反向
        $keyPos = 0;   //关键字开始的位置

        $c_strContent = self::str_split_unicode($strContent);
        $c_keyStr = self::str_split_unicode($keyStr);

        if( 0 == $startType ){
            //反向匹配
            //$pos=$keyStrLen;   //匹配开始的位置
            for( $pos; $pos>=0; $pos-- ){


                if($c_keyStr[$keyPos] != $c_strContent[$pos]){
                    $isMatch = false;
                    $keyPos = 0;
                    continue;
                }

                if($keyPos > $keyStrLen ){
                    //完全匹配
                    $isMatch = true;
                    break;
                }
            }


        }else{
            //正向匹配
            $pos=0;   //匹配开始的位置
            for( $pos; $pos<$strLen; $pos++ ){

                $keyValue = $c_keyStr[$keyPos];
                $strValue = $c_strContent[$pos];
                if($keyValue != $strValue){
                    $isMatch = false;
                    $keyPos = 0;
                    continue;
                }

                $keyPos++;

                if($keyPos >= $keyStrLen ){
                    //完全匹配
                    $isMatch = true;
                    break;
                }
            }

        }

        $arrRet = array(
            'isMatch' => $isMatch,
            'pos' => $pos
        );
        return $arrRet;
    }

}
