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

namespace controller;

use service\DataService;
use think\Controller;
use think\db\Query;
use think\Db;
use service\LogService;


/**
 * 牛牛数据控制器
 * Class BasicAdmin
 * @package controller
 */
class BasicMed extends Controller {

    /**
     * 页面标题
     * @var string
     */
    public $title;

    /**
     * 默认操作数据表
     * @var string
     */
    public $table;

    /**
     * 默认检查用户登录状态
     * @var bool
     */
    public $checkLogin = true;

    /**
     * 默认检查节点访问权限
     * @var bool
     */
    public $checkAuth = true;

    /**
     * 表单默认操作
     * @param Query $dbQuery 数据库查询对象
     * @param string $tplFile 显示模板名字
     * @param string $pkField 更新主键规则
     * @param array $where 查询规则
     * @param array $extendData 扩展数据
     * @return array|string
     */
    protected function _form($dbQuery = null, $tplFile = '', $pkField = '', $where = [], $extendData = []) {
        $db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
        $pk = empty($pkField) ? ($db->getPk() ? $db->getPk() : 'id') : $pkField;
        $pkValue = $this->request->request($pk, isset($where[$pk]) ? $where[$pk] : (isset($extendData[$pk]) ? $extendData[$pk] : null));
        // 非POST请求, 获取数据并显示表单页面
        if (!$this->request->isPost()) {
            $vo = ($pkValue !== null) ? array_merge((array) $db->where($pk, $pkValue)->where($where)->find(), $extendData) : $extendData;
            if (false !== $this->_callback('_form_filter', $vo)) {
                empty($this->title) || $this->assign('title', $this->title);
                return $this->fetch($tplFile, ['vo' => $vo]);
            }
            return $vo;
        }
        // POST请求, 数据自动存库
        $data = array_merge($this->request->post(), $extendData);
        if (false !== $this->_callback('_form_filter', $data)) {
            $result = DataService::save($db, $data, $pk, $where);
            if (false !== $this->_callback('_form_result', $result)) {
                //更新关联数据表
                if (false !== $this->_callback('_form_relate', $data)) {
                    $result !== false ? $this->success('恭喜, 数据保存成功!', '') : $this->error('数据保存失败, 请稍候再试!');
                }

            }
        }
    }

    /**
     * 列表集成处理方法
     * @param Query $dbQuery 数据库查询对象
     * @param bool $isPage 是启用分页
     * @param bool $isDisplay 是否直接输出显示
     * @param bool $total 总记录数
     * @return array|string
     */
    protected function _list($dbQuery = null, $isPage = true, $isDisplay = true, $total = false) {
        $db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
        // 列表排序默认处理
        if ($this->request->isPost() && $this->request->post('action') === 'resort') {
            $data = $this->request->post();
            unset($data['action']);
            foreach ($data as $key => &$value) {
                if (false === $db->where('id', intval(ltrim($key, '_')))->setField('sort', $value)) {
                    $this->error('列表排序失败, 请稍候再试');
                }
            }
            $this->success('列表排序成功, 正在刷新列表', '');
        }
        // 列表数据查询与显示
        if (null === $db->getOptions('order')) {
            $fields = $db->getTableFields($db->getTable());
            in_array('sort', $fields) && $db->order('sort asc');
        }
        $result = array();
        if ($isPage) {
            $rowPage = intval($this->request->get('rows', cookie('rows')));
            cookie('rows', $rowPage >= 10 ? $rowPage : 20);
            $page = $db->paginate($rowPage, $total, ['query' => $this->request->get()]);
            $result['list'] = $page->all();
            $result['page'] = preg_replace(['|href="(.*?)"|', '|pagination|'], ['data-open="$1" href="javascript:void(0);"', 'pagination pull-right'], $page->render());
        } else {
            $result['list'] = $db->select();
        }
        if (false !== $this->_callback('_data_filter', $result['list']) && $isDisplay) {
            !empty($this->title) && $this->assign('title', $this->title);
            return $this->fetch('', $result);
        }
        return $result;
    }

    /**
     * 当前对象回调成员方法
     * @param string $method
     * @param array|bool $data
     * @return bool
     */
    protected function _callback($method, &$data) {
        foreach ([$method, "_" . $this->request->action() . "{$method}"] as $_method) {
            if (method_exists($this, $_method) && false === $this->$_method($data)) {
                return false;
            }
        }
        return true;
    }


    /**
     * 返回检查页面
     * @param Query $checkedStr 显示字符串
     * @return array|string
     */
    protected function _resultView($checkedStr) {

        $this->assign('strtest', $checkedStr);
        $this->display();

        return view();
    }

    /**
     * 检查结果字符串
     * @param Query $dbQuery 数据库查询对象
     * @param bool $isPage 是启用分页
     * @param bool $isDisplay 是否直接输出显示
     * @param bool $total 总记录数
     * @return array|string
     */
    protected function _resultStr($checkedStr) {

        $result = array('aaa' => $checkedStr);

        return $result;
    }

    /**
     * 检查结果字符串
     * @param Query $dbQuery 数据库查询对象
     * @param bool $isPage 是启用分页
     * @param bool $isDisplay 是否直接输出显示
     * @param bool $total 总记录数
     * @return array|string
     */
    protected function _setTagStr($color, $startPos, $endPos, $checkedStr) {

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
            $tagStr = '[缺少信息-' . '职务' . ']';
        }else{
            $tagStr = mb_substr($checkedStr, $startPos, $tagLen, 'utf-8');
        }


        $headerStr = mb_substr($checkedStr, 0, $startPos, 'utf-8');
        $endStr = mb_substr($checkedStr, $endPos, $strLen, 'utf-8');

        $newTagStr = $tagS . $tagStr . $tagE;

        $result = $headerStr . $newTagStr . $endStr;
        return $result;
    }

    /**
     * 检查结果字符串
     * @param $list 原始列表
     * @param $curNode 当前列表节点
     * @param $perCode 前节点编码
     * @param $nextCode 后节点编码
     * @return array   排序后的数组列表
     */
    protected function _gitLeaderList($list, $curNode, $perCode, $nextCode) {
        $curUser = $list[0];
        $curPerCode = $curUser['pre_code'];
        $curNextCode = $curUser['next_code'];

        $newList = array();
        //赋值第一个节点
        $newList[] = $curUser;
        $pNode = $curPerCode;
        $nNode = $curNextCode;

        //后续节点
        while($nNode != ''){
            $isFind = false;
            foreach($list as $pos => $vo){
                $voCode = $vo['med_code'];
                if($voCode == $nNode){
                    $isFind = true;
                    break;
                }
            }

            if($isFind == true){
                $inNode = $list[$pos];
                array_push($newList, $inNode);
                $nNode = $vo['next_code'];
            }else{
                $nNode = '';
            }

        }

        //前节点
        while($pNode != ''){
            $isFind = false;
            foreach($list as $pos => $vo){
                $voCode = $vo['med_code'];
                if($voCode == $pNode){
                    $isFind = true;
                    break;
                }
            }

            if($isFind == true){
                $inNode = $list[$pos];
                array_unshift($newList, $inNode);
                $pNode = $vo['pre_code'];
            }else{
                $pNode = '';
            }

        }

        //检查排序
        $newCount = count($newList);
        $arrCount = count($list);
        if($arrCount != $newCount){
            //补充缺少的节点
            foreach($list as $pos => $vo){
                $newFind = false;

                foreach($newList as $index => $newVos){
                    $orgCode = $vo['med_code'];
                    $newCode = $newVos['med_code'];
                    if($orgCode == $newCode){
                        $newFind = true;
                        break;
                    }
                } //foreach($newList as $index => $newVos){
                if($newFind == false){
                    $inNode = $vo;
                    array_push($newList, $inNode);
                }

            } //foreach($list as $pos => $vo){
        } //if($arrCount != $newCount){

        return $newList;

    }



}
