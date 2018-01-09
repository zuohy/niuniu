<?php

// +----------------------------------------------------------------------
// | Think.Admin
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/Think.Admin
// +----------------------------------------------------------------------

namespace app\mednews\controller;


use service\DataService;
use think\Db;
use controller\BasicMed;
use service\LogService;
use service\WordsService;
/**
 * 检测敏感词关键词
 * Class User
 * @package app\admin\controller
 * @author Anyon <zoujingli@qq.com>
 * @date 2017/02/15 18:12
 */
class Medwords extends BasicMed {

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'MedLeaderWords';

    /**
     * 用户列表
     */
    public function index() {
        // 设置页面标题
        $this->title = '领导敏感词';
        // 获取到所有GET参数
        $get = $this->request->get();
        // 实例Query对象
        $db = Db::name($this->table)->where('is_deleted', '0');
        // 应用搜索条件
        foreach (['type', 'value'] as $key) {
            if (isset($get[$key]) && $get[$key] !== '') {
                $db->where($key, 'like', "%{$get[$key]}%");
            }
        }
        // 实例化并显示
        //return parent::_list($db);
        return $this->_resultView('aassddddfff');

        //return view();
    }


    /**
     * 关键词检测
     */
    public function findwords() {
        $extData = array();
        if ($this->request->isPost()) {
            $postData = $this->request->post();
            $extData = ['create_by' => session('user.id')];

        }

        $checkWords =  new WordsService();
        $wordLeader = $checkWords->getWordLeader();
        $wordJob = $checkWords->getWordJob();
        $test1 = $postData['content_org'];

        //分段
        $arrChapter = preg_split("/(\n|\r\n)/",$test1, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        //$result = preg_split('/[;\r\n]+/s', $test1);

        //分句
        $retTagStr = '';
        foreach($arrChapter as $index => $phase){
            $phase = $phase . "<br>";
            $retGroup = preg_split("/(，|。|；)/", $phase, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);


            foreach($retGroup as $index => $phaseWords){

                $exPhaseWords = $checkWords->c_findLeader(0, $wordLeader, $phaseWords);


                $retTagStr = $retTagStr . $exPhaseWords;


            }  //foreach($retGroup as $index => $phaseWords){

        } //foreach($arrChapter as $index => $phase){

        $test = $this->_resultStr( $retTagStr );
        return $test;

/*
        //分段
        $arrChapter = preg_split("/(\n|\r\n)/",$test1, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        //$result = preg_split('/[;\r\n]+/s', $test1);

        //分句
        $retTagStr = '';
        foreach($arrChapter as $index => $phase){
            $phase = $phase . "<br> ";
            $retGroup = preg_split("/(，|。|；)/", $phase, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);


        foreach($retGroup as $index => $phaseWords){

            $checkWords->c_findLeader(0, $wordLeader, $phaseWords);
            //用于运算检查的字符串
            $exPhaseWords = $phaseWords;

            //关键词数组个数
            $countCheckKey = count($wordLeader);
            //异常字符位置
            $aryExPos = array();
            for($checkPos=0; $checkPos<$countCheckKey; $checkPos++){
                //检查领导职务
                //查找领导
                $retName = $checkWords->c_matchString(1, $wordLeader[$checkPos], $exPhaseWords);
                if($retName['isMatch'] ==false){
                    continue;
                }

                $nameLen = mb_strlen($wordLeader[$checkPos],'utf8');
                $keyPos = $retName['pos'] - $nameLen;
                $aryPhase = $checkWords->str_split_unicode($exPhaseWords);
                if($keyPos <= 0
                    || ($aryPhase[$keyPos] == ' ')
                    || ($aryPhase[$keyPos] == '。')
                    || ($aryPhase[$keyPos] == '；') ){  //排除句子开头
                    //领导前面不需要职务
                    continue;
                }

                //匹配领导职务
                $retJob = $checkWords->c_matchPosString(0, $wordJob[$checkPos], $keyPos, $exPhaseWords);
                if($retJob['isMatch'] == false){
                    //标记异常字符, 保存异常字符位置
                    $exWordsPos = array(
                        'start' => $retJob['pos'],
                        'end' => $keyPos
                    );
                    $aryExPos[] = $exWordsPos;
                    $exPhaseWords = $this->_setTagStr('red', $exWordsPos['start'], $exWordsPos['end'], $exPhaseWords);
                }



            }  //for($checkPos=0; $checkPos<$countCheckKey; $checkPos++){

            $retTagStr = $retTagStr . $exPhaseWords;


        }  //foreach($retGroup as $index => $phaseWords){

        } //foreach($arrChapter as $index => $phase){


        $test = $this->_resultStr( $retTagStr );
        return $test;
        */
    }

    /**
     * 表单数据默认处理
     * @param array $data
     */
    public function _form_filter(&$data) {

       /* if ($this->request->isPost()) {

            if (isset($data['niu_code']) && $data['niu_code'] == '') {
                $data['niu_code'] = DataService::createSequence(10, 'NIU');

            }

            if (isset($data['id'])) {
                $retPoints = parent::pointsCharge($data['id'], '', $data['points_charge']);
                if( is_array($retPoints) ){
                    $data = array_merge($data, $retPoints);
                }
                unset($data['username']);
            } elseif (Db::name($this->table)->where('username', $data['username'])->find()) {
                $this->error('用户昵称已经存在，请使用其它昵称！');
            }
        } else {
            //$data['authorize'] = explode(',', isset($data['authorize']) ? $data['authorize'] : '');
            //$this->assign('authorizes', Db::name('SystemAuth')->select());
        }*/
    }

}
