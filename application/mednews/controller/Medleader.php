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
/**
 * 敏感词关键词
 * Class User
 * @package app\admin\controller
 * @author Anyon <zoujingli@qq.com>
 * @date 2017/02/15 18:12
 */
class Medleader extends BasicMed {

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
        return parent::_list($db);
    }

    /**
     * 添加领导信息
     */
    public function add() {
        $extData = array();
        if ($this->request->isPost()) {
            $postData = $this->request->post();
            $extData = ['create_by' => session('user.id'), 'type' => '0', 'next_code' => '', 'leader_level' => '3',];

            //查询前一个领导编号
            $preCode = $postData['pre_code'];
            $retUserInfo = Db::name($this->table)
                ->where('med_code', $preCode)
                ->select();
            if( isset($retUserInfo) ){
                $leaderInfo = $retUserInfo[0];
                $extData['next_code'] = $leaderInfo['next_code'];
            }

            $extData = array_merge($extData, $postData);

        }

        return $this->_form($this->table, 'form', '', [], $extData);
    }

    /**
     * 列表数据处理
     * @param type $list
     */
    protected function _data_filter(&$list) {

        $curUser = $list[0];
        $curPerCode = $curUser['pre_code'];
        $curNextCode = $curUser['next_code'];
        foreach ($list as &$vo) {

            $medCode = $vo['med_code'];
            if($curPerCode == $medCode){
                //找到上一个领导
                break;

            }

        }



    }


    /**
     * 表单数据默认处理
     * @param array $data
     */
    public function _form_filter(&$data) {

       if ($this->request->isPost()) {

            if (isset($data['med_code']) && $data['med_code'] == '') {
                //新增领导
                $data['med_code'] = DataService::createSequence(10, 'NIU');

            }

            /*if (isset($data['id'])) {
                $retPoints = parent::pointsCharge($data['id'], '', $data['points_charge']);
                if( is_array($retPoints) ){
                    $data = array_merge($data, $retPoints);
                }
                unset($data['username']);
            } elseif (Db::name($this->table)->where('username', $data['username'])->find()) {
                $this->error('用户昵称已经存在，请使用其它昵称！');
            }*/
        } else {
            //$arrLeader = $data['list'];

        }
    }


    /**
     * 表单相关表更新
     * @param array $data
     */
    public function _form_relate(&$data) {

        if ($this->request->isPost()) {

            if( isset($data['next_code']) ){
                //插入领导位置 更新插入位置的领导链接
                $curCode = $data['med_code'];
                $nextCode = $data['next_code'];
                $preCode = $data['pre_code'];
                $ret= Db::name($this->table)
                         ->where('med_code', $preCode)->update(['next_code' => $curCode]);
                $ret = Db::name($this->table)
                         ->where('med_code', $nextCode)->update(['pre_code' => $curCode]);

            }

        } else {
            //$data['authorize'] = explode(',', isset($data['authorize']) ? $data['authorize'] : '');
            //$this->assign('authorizes', Db::name('SystemAuth')->select());
        }
    }




}
