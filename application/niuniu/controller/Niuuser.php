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


use service\DataService;
use think\Db;
use controller\BasicNiu;
use service\LogService;
/**
 * 系统用户管理控制器
 * Class User
 * @package app\admin\controller
 * @author Anyon <zoujingli@qq.com>
 * @date 2017/02/15 18:12
 */
class Niuuser extends BasicNiu {

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'NiuUser';

    /**
     * 用户列表
     */
    public function index() {
        // 设置页面标题
        $this->title = '牛牛游戏';
        // 获取到所有GET参数
        $get = $this->request->get();
        // 实例Query对象
        $db = Db::name($this->table)->where('is_deleted', '0');
        // 应用搜索条件
        foreach (['username', 'phone'] as $key) {
            if (isset($get[$key]) && $get[$key] !== '') {
                $db->where($key, 'like', "%{$get[$key]}%");
            }
        }
        // 实例化并显示
        return parent::_list($db);
    }


    /**
 * 用户添加
 */
    public function add() {
        $extData = array();
        if ($this->request->isPost()) {
            $postData = $this->request->post();
            $extData = ['create_by' => session('user.id')];

            $retPoints = parent::pointsCharge(0, $postData['username'], $postData['points_charge']);
            if( is_array($retPoints) ){
                $extData = array_merge($extData, $retPoints);
            }

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
     * 表单数据默认处理
     * @param array $data
     */
    public function _form_filter(&$data) {

        if ($this->request->isPost()) {

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
        }
    }

}
