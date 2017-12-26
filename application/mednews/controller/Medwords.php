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
        $test1 = $postData['content_org'];

        $test1 = str_replace( "\n","<br>", $test1);

        $retGroup = $checkWords->groupStr($test1);

        $retTagStr = '';
        foreach($retGroup as $index => $phaseWords){
            //检查领导职务
            //查找领导
            $retName = $checkWords->c_matchString(1, $checkWords->wordLeader[1], $phaseWords);

            if($retName['isMatch'] ==false){
                $retTagStr = $retTagStr . $phaseWords;
                continue;
            }
            $nameLen = mb_strlen($checkWords->wordLeader[1],'utf8');
            $keyPos = $retName['pos'] - $nameLen;

            $aryPhase = $checkWords->str_split_unicode($phaseWords);
            if($keyPos <= 0
                || ($aryPhase[$keyPos-1] == ' ')
                || ($aryPhase[$keyPos-1] == '。')
                || ($aryPhase[$keyPos-1] == '；') ){  //排除句子开头
                //领导前面不需要职务
                $retTagStr = $retTagStr . $phaseWords;
                continue;
            }

            //匹配领导职务
            $retJob = $checkWords->c_matchPosString(0, $checkWords->wordJob[1], $keyPos, $phaseWords);
            if($retJob['isMatch'] == false){
                $retTagStr = $retTagStr . $this->_setTagStr('red', $retJob['pos'], $keyPos, $phaseWords);
            }else{
                $retTagStr = $retTagStr . $phaseWords;
            }
        }


        $test = $this->_resultStr( $retTagStr );
        return $test;

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
