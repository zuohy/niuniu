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
    public static $wordLeader = array('习近平',
                                         '张文强', '黄桂林', '潘玉华', '陈博', '曾薇',
                                         '张宗良', '孟先锋', '陈刚', '黄成', '夏吉友',
                                         '喻正斌', '符清和', '吴雪嫩', '唐邦水', '贾青鸿',
                                         '黎建明', '李军', '阮顺坤', '罗惠平', '王华', '刘奉贤',
        '姚明凤', '张远军', '洪湖',
        '曾明康', '龚传海', '杨昌勇', '肖福勇', '桂国全',
        '郑毅', '符合', '王建军', '喻松', '余江', '陈廷高', '王天国');
    public static $wordJob = array('中共中央总书记',
                                      '黔南州委常委、瓮安县委书记', '瓮安县委副书记、县长', '瓮安县人大常委会主任', '瓮安县政协主席', '瓮安县委副书记',
                                      '瓮安县委常委、常务副县长', '瓮安县委常委、统战部部长', '瓮安县委常委、纪委书记', '瓮安县委常委、公安局局长', '瓮安县委常委、宣传部部长',
                                      '瓮安县委常委、组织部部长', '瓮安县委常委、县委办公室主任', '瓮安县委常委、副县长', '瓮安县委常委、副县长', '瓮安县委常委、副县长',
                                      '瓮安县委常委、副县长', '瓮安县委常委、副县长', '瓮安县政府党组成员', '瓮安县人大常委会副主任', '瓮安县人大常委会副主任', '瓮安县人大常委会副主任',
        '瓮安县人大常委会副主任', '瓮安县人大常委会副主任', '瓮安县人大常委会副主任',
        '瓮安县人民政府副县长', '瓮安县人民政府副县长', '瓮安县人民政府副县长', '瓮安县人民政府副县长', '瓮安县人民政府副县长',
        '瓮安县政协副主席', '瓮安县政协副主席', '瓮安县政协副主席', '瓮安县政协副主席', '瓮安县政协副主席', '瓮安县政协副主席', '瓮安县政协副主席');


    //检查领导结构化
    public static $arrTagPhase = array(
        'orgStr' => '',  //原字符串
        'orgLen' => 0,    //原字符串长度
        'checkTag' => array(),  //结构化解析
        'tagStr' => '',  //标记后字符串
    );
    public static $arrLeaderUnit = array(
        'jobTitle' => '',
        'name' => '',
        'startPos' => 0,
        'endPos' => 0,
    );

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
     * 初始化 $arrTagPhase
     */
    static public function __initTagPhase(){
        self::$arrTagPhase['orgStr'] = '';
        self::$arrTagPhase['orgLen'] = 0;
        self::$arrTagPhase['checkTag'] = array();
        self::$arrTagPhase['tagStr'] = '';

        return self::$arrTagPhase;

    }
    /**
     * 初始化 $arrLeaderUnit
     */
    static public function __initLeaderUnit(){
        self::$arrLeaderUnit['jobTitle'] = '';
        self::$arrLeaderUnit['name'] = '';
        self::$arrLeaderUnit['startPos'] = 0;
        self::$arrLeaderUnit['endPos'] = 0;

        return self::$arrLeaderUnit;

    }
    /**
     * 获取 $wordLeader
     */
    static public function getWordLeader(){

        return self::$wordLeader;

    }
    /**
     * 获取 $wordJob
     */
    static public function getWordJob(){

        return self::$wordJob;

    }

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
    /*
    static public function matchPosString($startType=0, $keyStr, $startPos, $strContent) {

        $isMatch = true;

        if (is_null($strContent)) {
            return;
        }

        $strLen = strlen($strContent);
        $keyStrLen = strlen($keyStr);
        $keyPos=$keyStrLen-1;   //匹配开始的位置 默认反向
        //异常检查
        if($startPos > $strLen){
            return;
        }

        if( 0 == $startType ){
            //反向匹配
            //$pos=$keyStrLen;   //匹配开始的位置
            for( $keyPos; $keyPos>=0; $keyPos-- ){
                if($startPos < 0 ){
                    //检测的字符串没有关键字长，返回false
                    $isMatch = false;
                    break;
                }

                if($keyStr[$keyPos] != $strContent[$startPos]){
                    $isMatch = false;
                    break;
                }

                $startPos--;
            }

            if($keyPos < 0 ){
                //完全匹配
                $isMatch = true;
            }
        }else{
            //正向匹配
            $keyPos=0;   //匹配开始的位置
            for( $keyPos; $keyPos<$keyStrLen; $keyPos++ ){
                if($startPos > $strLen ){
                    //检测的字符串没有关键字长，返回false
                    $isMatch = false;
                    break;
                }

                if($keyStr[$keyPos] != $strContent[$startPos]){
                    $isMatch = false;
                    break;
                }

                $startPos++;
            }

            if($keyPos > $keyStrLen ){
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
*/

    /**
     * 字符比对匹配关键字 纯英文
     *
     * @param $startType 匹配的方式， 0 为倒序匹配，1 为正向匹配
     * @param $startPos 匹配检测的起始位置
     * @param $keyStr 匹配的关键字
     * @param $strContent 匹配的目标字符串
     * @return null
     */
    /*
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
*/

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

        $isMatch = false;
        $arrRet = array(
            'isMatch' => $isMatch,
            'pos' => 0
        );
        if (is_null($strContent)) {
            return $arrRet;
        }

        $strLen = mb_strlen($strContent, 'utf8');
        $keyStrLen = mb_strlen($keyStr, 'utf8');
        $keyPos=$keyStrLen-1;   //匹配开始的位置 默认反向

        $c_strContent = self::str_split_unicode($strContent);
        $c_keyStr = self::str_split_unicode($keyStr);

        //异常检查
        if($startPos > $strLen){
            return $arrRet;
        }

        if( 0 == $startType ){
            //反向匹配
            //$pos=$keyStrLen;   //匹配开始的位置
            for( $keyPos; $keyPos>=0; $keyPos-- ){
                if($startPos < 0 ){
                    //检测的字符串没有关键字长，返回false
                    $isMatch = false;
                    $startPos = 0;
                    break;
                }

                if($c_keyStr[$keyPos] != $c_strContent[$startPos]){
                    $isMatch = false;
                    break;
                }

                $startPos--;
            }

            if($keyPos < 0 ){
                //完全匹配
                $isMatch = true;
            }
        }else{
            //正向匹配
            $keyPos=0;   //匹配开始的位置
            for( $keyPos; $keyPos<$keyStrLen; $keyPos++ ){
                if($startPos > $strLen ){
                    //检测的字符串没有关键字长，返回false
                    $isMatch = false;
                    break;
                }

                if($c_keyStr[$keyPos] != $c_strContent[$startPos]){
                    $isMatch = false;
                    break;
                }

                $startPos++;
            }

            if($keyPos > $keyStrLen ){
                //完全匹配
                $isMatch = true;
            }

        }
        $arrRet['isMatch'] = $isMatch;
        $arrRet['pos'] = $startPos;

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

        $isMatch = false;
        $arrRet = array(
            'isMatch' => $isMatch,
            'pos' => 0
        );

        if (is_null($strContent)) {
            return $arrRet;
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

        $arrRet['isMatch'] = $isMatch;
        $arrRet['pos'] = $pos;

        return $arrRet;
    }

    /**
     * 查找领导名字 模糊查找 混合汉字 英文
     *
     * @param $findType 匹配的方式，
     * @param $keyAry 领导关键字数组
     * @param $strContent 匹配的目标字符串
     * @return $tagAry    解析结构
     */
    static public function c_findLeader($findType=0, $keyAry, $strContent) {

        $tagAry = self::__initTagPhase();

        if (is_null($strContent)) {
            return $tagAry;
        }

        $strLen = mb_strlen($strContent, 'utf8');   //字符串长度

        $tagAry['orgStr'] = $strContent;
        $tagAry['orgLen'] = $strLen;
        $tagAry['tagStr'] = $strContent;

        $keyPos = 0;   //关键字开始的位置


        foreach($keyAry as $index => $name){
            $leaderUnit = self::__initLeaderUnit();
            $c_name = self::str_split_unicode($name);
            $nameLen = mb_strlen($name, 'utf8');   //关键字长度
            $startKey = 0;  //匹配开始位置
            $keyPos = 0;    //关键字位移

            $c_strContent = self::str_split_unicode($tagAry['tagStr']);  //每次循环更新标记的 最新的字符串
            foreach($c_strContent as $pos => $word){
                $strCount = count($c_strContent);

                //if($pos+$nameLen > $strCount){
                    //关键字长度大于字符串长度
                //    break;
                //}
                $fCount = 0;  //每次找到符合匹配的个数
                $isMach = true;  //是否匹配 模糊匹配

                for($keyPos=0; $keyPos<$nameLen; $keyPos++){
                    if($pos+$keyPos >= $strCount){
                    //关键字长度大于字符串长度
                        break;
                    }

                    $keyWord = $c_name[$keyPos];
                    $checkWord = $c_strContent[$pos+$keyPos];
                    if($checkWord != $keyWord){
                        $isMach = false;

                    }else{
                        $isMach = true;
                        $fCount++;
                    }

                    if($isMach){
                        if($keyPos == $nameLen-1){
                            //echo "个位置开始为＼n";
                            break;
                        }
                    }

                }  //for($keyPos=0; $keyPos<$nameLen; $keyPos++){


                //记录匹配开始 匹配至少两个字符
                if($fCount >= 2){
                    $leaderUnit['startPos'] = $pos;
                    $leaderUnit['endPos'] = $pos + ($nameLen-1);
                    $tagStr = mb_substr($tagAry['tagStr'], $leaderUnit['startPos'], $nameLen, 'utf-8');  //获取关键字
                    $leaderUnit['name'] = $tagStr;

                    $tagAry['checkTag'][] = $leaderUnit;

                    //标记关键字名字
                   if($fCount != $nameLen){
                       $nameEnd = $leaderUnit['startPos'] + $nameLen;
                       $tagAry['tagStr'] = self::setTagStr('名称', 'blue', $leaderUnit['startPos'], $nameEnd, $tagAry['tagStr'], '关键字错误');
                   }

                    //匹配领导职务
                    $jobEnd = $leaderUnit['startPos']-1;
                    $retJob = self::c_matchPosString(0, self::$wordJob[$index], $jobEnd, $tagAry['tagStr']);
                    if($retJob['isMatch'] == false){
                        //标记异常字符, 保存异常字符位置
                        $exWordsPos = array(
                            'start' => $retJob['pos'],
                            'end' => $leaderUnit['startPos']
                        );
                        $aryExPos[] = $exWordsPos;
                        $tagAry['tagStr'] = self::setTagStr('职务', 'red', $exWordsPos['start'], $exWordsPos['end'], $tagAry['tagStr'], '职务错误');
                    }

                }


            } // foreach($c_strContent as $pos => $word){



        }  //foreach($keyAry as $index => $name){


        //领导排序
        $arrLeaders = $tagAry['checkTag'];
        $arrNewLeaders = $arrLeaders;
        $arrTestCheck = array_column($arrNewLeaders,'startPos');
        array_multisort($arrTestCheck, SORT_ASC, SORT_NUMERIC, $arrNewLeaders);

        $leaderCount = count($arrLeaders);
        $curNames = '';
        $isAsc = false;
        for($i=0; $i<$leaderCount; $i++){
            $curLeaderPos = $arrLeaders[$i]['startPos'];
            $curLeaderName = $arrLeaders[$i]['name'];
            $curNames = $curNames . $curLeaderName . ',';

            $newLeaderPos = $arrNewLeaders[$i]['startPos'];
            $newLeaderName = $arrNewLeaders[$i]['name'];


            if($curLeaderPos != $newLeaderPos){
                $tagAry['tagStr'] = self::setTagStr('排序错误', 'red', 0, 0, $tagAry['tagStr'], $newLeaderName);
                $isAsc = true;
                break;
            }

        } // for($i=0; $i<$leaderCount; $i++){
        if($isAsc == true){
            $tagAry['tagStr'] = self::setTagStr('排序纠正', 'purple', 0, 0, $tagAry['tagStr'], $curNames);
        }

        return $tagAry['tagStr'];
    }


    /**
     * 检查结果字符串
     * @param  $type 类型 职务 名称 排序
     * @param $color 标签颜色
     * @param $startPos 标签起始位置
     * @param $endPos 标签结束位置
     * @param $checkedStr 标签字符串
     * @param $showStr  说明字符串
     * @return array|string
     */
    static public function setTagStr($type='职务', $color, $startPos, $endPos, $checkedStr, $showStr) {

        $tagS = '<span ' . 'style="font-weight:bold; color:' . $color . '"' . '>';
        $tagE = '</span>';

        if($endPos < 0){
            $endPos =0;
        }
        if($startPos < 0){
            $startPos=0;
        }
        //截断标记的位置
        $strLen = mb_strlen($checkedStr, 'utf8');
        $tagLen = $endPos - $startPos;
        if($startPos >= $endPos){
            if($type == '职务'){
                $tagStr = '[缺少信息-' . '职务' . ']';
            }else if($type == '名称'){
                $tagStr = '[名称信息-' . $showStr . ']';
            }else if($type == '排序错误'){
                $tagStr = '[排序错误-' . $showStr . ']';
            }else if($type == '排序纠正'){
                $tagStr = '[排序纠正-' . $showStr . ']';
            }else{
                $tagStr = '[缺少信息-' . '职务' . ']';
            }


        }else{
            $tagStr = mb_substr($checkedStr, $startPos, $tagLen, 'utf-8');
        }


        $headerStr = mb_substr($checkedStr, 0, $startPos, 'utf-8');
        $endStr = mb_substr($checkedStr, $endPos, $strLen, 'utf-8');

        $newTagStr = $tagS . $tagStr . $tagE;

        $result = $headerStr . $newTagStr . $endStr;
        return $result;
    }


}
