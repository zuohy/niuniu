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

namespace app\niuniu\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;
use controller\BasicNiu;

/**
 * 系统用户管理控制器
 * Class User
 * @package app\admin\controller
 * @author Anyon <zoujingli@qq.com>
 * @date 2017/02/15 18:12
 */
class Niugame extends BasicNiu {

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'NiuGameUser';
    //public $table_host = 'NiuGameHost';
    //public $table_user = 'NiuUser';

    /**
     * 用户列表
     */
    public function index() {
        // 设置页面标题
        $this->title = '游戏局';
        // 获取到所有GET参数
        $get = $this->request->get();
        // 实例Query对象
        $db = Db::name($this->table)->where('is_deleted', '0')
                                     ->where('is_done', '0');
        // 应用搜索条件
        foreach (['username'] as $key) {
            if (isset($get[$key]) && $get[$key] !== '') {
                $db->where($key, 'like', "%{$get[$key]}%");
            }
        }

        $hostList = Db::name($this->table)->where('is_deleted', '0')
            ->where('is_banker', '1')
            ->where('is_done', '0')
            ->select();


        if( isset($hostList[0]) ){
            $hostInfo = $hostList[0];
            $this->assign('game_code', $hostInfo['game_code']);
            $this->assign('best_ending', $hostInfo['best_ending']);
        }

        // 实例化并显示
        return parent::_list($db);
    }

    /**
     * 获得牛用户列表
     */
    private function getNiuUsers() {
        // 实例Query对象
        $result = Db::name($this->table_user)
            ->field('niu_code,username')
            ->where('is_deleted', '0')
            ->order('id ASC')
            ->select();

        return $result;

    }

    /**
     * 获得游戏当前局信息
     */
    private function getGameHost() {
        // 实例Query对象
        $result = Db::name($this->table_host)
            ->where('is_deleted', '0')
            ->where('is_done', '0')
            ->order('id ASC')
            ->select();

        return $result;

    }
    /**
     * 创建游戏当前局信息
     */
    private function createGameHost($inData) {

        $gameSeq = DataService::createSequence(10, 'GAME');
        $gameCode = date("Ymd") . '-' . $gameSeq;

        // 创建游戏局记录
        $arrData = array(
            'game_code' => $gameCode
        );
        if ( DataService::save($this->table_host, $arrData, 'game_code', []) ) {
            //创建成功
        }else{
            $this->error("创建游戏局失败，请稍候再试！");
        }

        return $gameCode;
    }

    /**
     * 用户添加
     */
    public function add() {
        $extData = array();
        if ($this->request->isPost()) {
            $postData = $this->request->post();


            //获得当前局信息
            $gameInfo = $this->getGameHost();
            if( count($gameInfo) < 1 ){
                //创建游戏局
                $gameCode = $this->createGameHost('');
            }else{
                $gameCode = $gameInfo[0]['game_code'];
            }

            $extData['game_code'] = $gameCode;
            $extData['create_by'] = session('user.id');

            $gUser = Db::name($this->table)
                     ->where('username', $postData['username'])
                ->where('is_deleted', '0')
                ->where('is_done', '0');
            if ($gUser->find()) {
                $this->error('用户正在游戏中，请使用其它账号！');
            }
        }else{
            $niuUsers = $this->getNiuUsers();
            $extData['niu_users'] = $niuUsers;

        }

        return $this->_form($this->table, 'form', '', [], $extData);

    }

    /**
     * 用户编辑
     */
    public function edit() {
        return $this->_form($this->table, 'form');
    }

    /**
     * 删除用户
     */
    public function del() {

        if (DataService::update($this->table)) {
            $this->success("用户删除成功！", '');
        }
        $this->error("用户删除失败，请稍候再试！");
    }


    /**
     * 生成赔付结果
     */
    public function countpay() {


        $ttt = parent::makePay(0);   //全部计算

        $this->success("计算成功！", '', '');



    }

    /**
     * 保存赔付结果
     */
    public function gamedone() {


        $ttt = parent::makeGameDone();

        $this->success("积分保存成功！", '', '');



    }

    /**
     * 表单数据默认处理
     * @param array $data
     */
    public function _form_filter(&$data) {
        if ($this->request->isPost()) {

            if( isset($data['is_banker']) ){
                $oneBanker = Db::name($this->table)
                    ->where('is_deleted', '0')
                    ->where('is_done', '0')
                    ->where('is_banker', '1')
                    ->find();

                if( $oneBanker && $data['is_banker'] == 1){

                    if($oneBanker['username'] != $data['username']){
                        $this->error('只能有一个庄家！');
                    }


                }
            }

        } else {
            //$data['authorize'] = explode(',', isset($data['authorize']) ? $data['authorize'] : '');
            //$this->assign('authorizes', Db::name('SystemAuth')->select());
        }
    }

    /**
     * 列表数据处理
     * @param type $list
     */
    protected function _data_filter(&$list) {

        /*foreach ($list as &$vo) {

        }*/
        //$this->assign('game_code', '123123');
    }



}
