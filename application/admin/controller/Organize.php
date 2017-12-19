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

namespace app\admin\controller;

use controller\BasicAdmin;
use service\DataService;
use service\NodeService;
use service\ToolsService;
use think\Db;

/**
 * 系统后台管理管理
 * Class Menu
 * @package app\admin\controller
 * @author Anyon <zoujingli@qq.com>
 * @date 2017/02/15
 */
class Organize extends BasicAdmin {

    /**
     * 绑定操作模型
     * @var string
     */
    public $table = 'SystemMenu';

    /**
     * 菜单列表
     */
    public function index() {
        $this->title = '组织结构';
        $db = Db::name($this->table)->order('sort asc,id asc');
        return view('', ['title' => '系统管理', 'menus' => 'aaa']);


    }



}
