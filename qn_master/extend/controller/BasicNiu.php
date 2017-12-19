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

define("NIU_WIN_CLIENT", 0);            //闲家赢
define("NIU_WIN_HOST", 1);                //庄家赢
define("NIU_WIN_MIN", 2);                //平局

define("NIU_LEVEL_0", 0);            //牛牛
define("NIU_LEVEL_1", 1);                //牛一
define("NIU_LEVEL_2", 2);                //牛二
define("NIU_LEVEL_3", 3);
define("NIU_LEVEL_4", 4);
define("NIU_LEVEL_5", 5);
define("NIU_LEVEL_6", 6);
define("NIU_LEVEL_7", 7);
define("NIU_LEVEL_8", 8);
define("NIU_LEVEL_9", 9);

/**
 * 牛牛数据控制器
 * Class BasicAdmin
 * @package controller
 */
class BasicNiu extends Controller {

    public $table_game = 'NiuGameUser';
    public $table_host = 'NiuGameHost';
    public $table_user = 'NiuUser';

    public $isLog = true;  //是否打开log
    public $levelLog = 7;  //log 等级 1 2,3，错误等级， 4,5,6,7 info等级 8 测试等级
    /**
     * 下注数据单元
     * @var string
     */
    public $gUser = array(
        'id' => 0,
        'niu_code' => '',
        'username' => '',
        'bet_num' => 0,           //下注额
        'bet_times' => 1,          //下注倍数
        'open_ending' =>1,          //尾数
        'best_ending' =>1,         //最佳尾数
        //'niu_level' => 1,          //牛等级
        'is_banker' => 0,          //闲家
        'pay_result' => 0,        //赔付结果
        'points_per' => 0,        //上局积分
        'points_now' => 0,         //当前剩余积分
    );

    /**
     * 输赢规则
     * @var string
     */
    public $gWinRule = array(

        'is_banker' => 0,
    );

    /**
     * 赔付规则
     * @var string
     */
    public $gPayRule = array(

        'is_banker' => 0,
    );

    /**
     * 测试赔付函数
     * @param int $level 日志等级
     * @param string $action 日志操作
     * @param string $data 日志内容
     * @return int
     */
    protected function writeLog($level, $action, $data)
    {
        if($this->isLog == false){
            return;
        }
        if( is_array($data) ){
            $dataStr = json_encode($data);
        }else{
            $dataStr = $data;
        }
        if( $this->levelLog >= $level ){
            $logAction = '牛牛游戏 ' . $action;
            LogService::write($logAction, $dataStr);
        }


    }

    /**
     * 充值积分
     * @param int $userId 玩家ID
     * @param string $inCharge 充值金额
     * @return array
     */
    protected function pointsCharge($userId, $userName, $inCharge)
    {

        $retPoints = array(
            'points_total' => 0,
            'points_now' => 0,
            //'points_charge' => 0,
        );
        if( $inCharge > 0 ){

            //获得当前用户信息
            if($userId > 0){
                $userInfo = '';
                $retUserInfo = Db::name($this->table_user)
                    ->where('id', $userId)
                    ->select();
                if( isset($retUserInfo) ){
                    $userInfo = $retUserInfo[0];
                    $retPoints['points_total'] = $userInfo['points_total'] + $inCharge;
                    $retPoints['points_now'] = $userInfo['points_now'] + $inCharge;
                    $this->writeLog(7, '充值金额', $userInfo['username'] . ' 充值 ' . $inCharge);
                }
            }else{
                //更新总积分
                $retPoints['points_total'] = $inCharge;
                $retPoints['points_now'] = $inCharge;
                $this->writeLog(7, '充值金额', $userName . ' 充值 ' . $inCharge);
            }

        }else{
            return '';
        }


        return $retPoints;

    }


    /**
     * 测试赔付函数
     * @param int $isWin 庄家输赢
     * @param string $gHostUser 庄家尾数$gUser
     * @param string $gClientUser 闲家尾数$gUser
     * @return int
     */
    protected function testPay()
    {
        $tmpHost = array();
        $tmpClient = array();
        $tmpHost['niu_code'] = '123';
        $tmpHost['username'] = '测试庄家';
        $tmpHost['bet_num'] = '15';   //下注额
        $tmpHost['bet_times'] = '2';   //下注倍数
        $tmpHost['open_ending'] = '1';   //尾数 加 最佳人尾号
        //$tmpHost['niu_level'] = '2';   //牛等级
        $tmpHost['is_banker'] = '1';   //庄家
        $tmpHost['pay_result'] = '0';   //赔付结果

        $tmpClient['niu_code'] = '456';
        $tmpClient['username'] = '测试闲家';
        $tmpClient['bet_num'] = '22';   //下注额
        $tmpClient['bet_times'] = '1';   //下注倍数
        $tmpClient['open_ending'] = '5';   //尾数
        //$tmpClient['niu_level'] = '2';   //牛等级
        $tmpClient['is_banker'] = '0';   //庄家
        $tmpClient['pay_result'] = '0';   //赔付结果

        $winSite = $this->_winCheck($tmpHost['open_ending'],$tmpClient['open_ending'] );

        $tmpResult = $this->_payCheck($winSite, $tmpHost['open_ending'], $tmpHost, $tmpClient);

        return $tmpResult;
    }

    /**
     * 保存赔付结果，更新玩家积分
     * @param int $isWin 庄家输赢
     * @param string $gHostUser 庄家尾数$gUser
     * @param string $gClientUser 闲家尾数$gUser
     * @return int
     */
    protected function makeGameDone()
    {
        //获取当前局的庄家和闲家信息

        $gHostInfo = '';  //庄家赔付结果信息

        $gUserList = Db::name($this->table_game)
            ->where('is_deleted', '0')
            ->where('is_done', '0')
            ->select();

        foreach($gUserList as $key => $gUser){
            $this->writeLog(8, '当前游戏结果信息', $gUser);

            //保存庄家赔付信息
            if($gUser['is_banker'] == 1){
                $gHostInfo = $gUser;
            }
            //获取玩家记录信息
            $userInfo = '';
            $retUserInfo = Db::name($this->table_user)
                ->where('username', $gUser['username'])
                ->select();
            $userInfo = $retUserInfo[0];
            if($userInfo == ''){
                $this->writeLog(3, '更新玩家积分，没有玩家信息', $gUser['username']);
                continue;
            }

            //更新玩家信息
            $wUserInfo = array();
            $wUserInfo['id'] = $userInfo['id'];
            $wUserInfo['points_now'] = $userInfo['points_now'] + $gUser['pay_result'];  //剩余积分
            if( $gUser['pay_result'] > 0){
                $wUserInfo['points_income'] = $userInfo['points_now'] + $gUser['pay_result'];  //收入
                $wUserInfo['game_win'] = $userInfo['game_win'] + 1;  //赢的次数
            }elseif( $gUser['pay_result'] < 0 ){
                $wUserInfo['points_payout'] = $userInfo['points_payout'] + $gUser['pay_result']; //支出
                $wUserInfo['game_lost'] = $userInfo['game_lost'] + 1;  //输的次数
            }else{
                $wUserInfo['game_min'] = $userInfo['game_min'] + 1;  //平局的次数
            }
            $wUserInfo['game_count'] = $userInfo['game_count'] + 1;  //游戏总次数


            DataService::save($this->table_user, $wUserInfo);
            $isDone = array(
                'id' => $gUser['id'],
                'is_done' => 1,
            );
            DataService::save($this->table_game, $isDone);

        }

        //更新游戏房间信息
        $wHost = array();
        if($gHostInfo != ''){

            $wHost = array(
                'game_code' => $gHostInfo['game_code'],
                'username' => $gHostInfo['username'],
                'niu_code' => $gHostInfo['niu_code'],
                'bet_total_num' => $gHostInfo['bet_total_num'],
                'bet_num' => $gHostInfo['bet_num'],
                'bet_times' => $gHostInfo['bet_times'],
                'open_ending' => $gHostInfo['open_ending'],
                'pay_result' => $gHostInfo['pay_result'],
                'niu_level' => $gHostInfo['open_ending'] + $gHostInfo['best_ending'],    //TODO
                'best_ending' => $gHostInfo['best_ending'],
                'is_done' => 1
            );
            $this->writeLog(8, '更新游戏庄家房间信息', $wHost);
            DataService::save($this->table_host, $wHost, 'game_code', []);
        }

        return ;
    }


    /**
     * 生成赔付函数
     * @param int $isWin 庄家输赢
     * @param string $gHostUser 庄家尾数$gUser
     * @param string $gClientUser 闲家尾数$gUser
     * @return int
     */
    protected function makePay($isCheck )
    {

        $gHostUser = '';
        $gClientUsers = '';
        $retResultList = array();

        //获取当前局的庄家和闲家信息
        $hostData = Db::name($this->table_game)
            ->where('is_banker', '1')
            ->where('is_deleted', '0')
            ->where('is_done', '0')
            ->select();


        $clientList = Db::name($this->table_game)
            ->where('is_banker', '0')
            ->where('is_deleted', '0')
            ->where('is_done', '0')
            ->select();


        if($this->request->isPost()){
            $postData = $this->request->Post();
            $reqIds = $postData['id'];

            if($isCheck == 1){
                //需要检查提交的计算数据，是否有庄家记录

            }else{
                //直接读取当前局所有玩家记录
                $gHostUser = $hostData[0];
                $gClientUsers = $clientList;
            }

            if($gHostUser == ''){
                $this->writeLog(3, '没有庄家信息', $gHostUser);
                $this->error('没有庄家信息！');
                return;
            }
            //循环计算每个玩家的赔付结果
            $payHost = 0;
            $this->_initGUser();
            $hostUser = $this->_setGUser($gHostUser);
            $this->writeLog(8, '庄家信息', $hostUser);

            foreach($gClientUsers as $key => $clientUser){
                $this->_initGUser();
                $tmpUser = $this->_setGUser($clientUser);

                $hostLevel = $hostUser['open_ending'] + $hostUser['best_ending'];   //庄家牛等级
                $tmpLevel = $tmpUser['open_ending'] + $hostUser['best_ending'];    //闲家牛等级

                $winSite = $this->_winCheck($hostUser['open_ending'], $clientUser['open_ending'] );
                $retResult = $this->_payCheck($winSite, $hostLevel, $hostUser, $tmpUser);
                $retResultList[] = $retResult;

                //获得闲家玩家积分
                $userInfo = '';
                $retUserInfo = Db::name($this->table_user)
                    ->where('username', $tmpUser['username'])
                    ->select();
                $userInfo = $retUserInfo[0];
                $this->writeLog(8, '玩家信息', $userInfo);
                $this->writeLog(8, '玩家赔付', $retResult);

                //更新闲家 当前游戏局记录
                $retClientRes = $retResult['client'];
                $retClientRes['points_per'] = $userInfo['points_now'];
                $retClientRes['points_now'] = $userInfo['points_now'] + $retClientRes['pay_result'];
                unset($retClientRes['niu_code']);
                unset($retClientRes['username']);
                DataService::save($this->table_game, $retClientRes);

                $payHost = $payHost + $retResult['host']['pay_result'];
            }  //foreach($gClientUsers as $key => $clientUser)


            //获得庄家积分
            $userInfo = '';
            $retUserInfo = Db::name($this->table_user)
                ->where('username', $hostUser['username'])
                ->select();
            $userInfo = $retUserInfo[0];
            //更新庄家赔付结果
            $hostUser['points_per'] = $userInfo['points_now'];
            $hostUser['points_now'] = $userInfo['points_now'] + $payHost;
            $hostUser['pay_result'] = $payHost;
            unset($hostUser['niu_code']);
            unset($hostUser['username']);
            DataService::save($this->table_game, $hostUser);


        }   //if($this->request->isPost())


        return $retResultList;
    }


        /**
     * 下注数据单元初始化
     * @return int
     */
    protected function _initGUser() {
        $init_gUser = $this->gUser;

        $init_gUser['id'] = 0;
        $init_gUser['niu_code'] = '';
        $init_gUser['username'] = '';
        $init_gUser['bet_num'] = 0;
        $init_gUser['bet_times'] = 1;
        $init_gUser['open_ending'] = 1;
        $init_gUser['best_ending'] = 1;
        //$init_gUser['niu_level'] = 1;
        $init_gUser['is_banker'] = 0;
        $init_gUser['pay_result'] = 0;
        $init_gUser['points_per'] = 0;
        $init_gUser['points_now'] = 0;

        $this->gUser = $init_gUser;
        return $init_gUser;
    }

    /**
     * 下注数据单元赋值
     * @return int
     */
    protected function _setGUser($inData) {
        $init_gUser = $this->gUser;

        foreach($init_gUser as $key => $value){
            if( isset($inData[$key]) ){
                $init_gUser[$key] =  $inData[$key];
            }

        }

        $this->gUser = $init_gUser;
        return $init_gUser;
    }


    /**
     * 输赢赔付
     * @param int $isWin 庄家输赢
     * @param string $gHostUser 庄家尾数$gUser
     * @param string $gClientUser 闲家尾数$gUser
     * @return int
     */
    protected function _payCheck($isWin, $niuLevel, $gHostUser, $gClientUser) {

        $retWin = NIU_WIN_CLIENT;  //默认闲家赢
        $retHost = $this->_initGUser();
        $retClient = $this->_initGUser();

        $retHost = $gHostUser;
        $retClient = $gClientUser;

        //一般情况
        if($isWin == NIU_WIN_HOST){
            $retHost = $gHostUser;
            $retHost['pay_result'] = $gClientUser['bet_num'] * $gClientUser['bet_times'];
            $retClient['pay_result'] = 0 - $retHost['pay_result'];

        }elseif($isWin == NIU_WIN_CLIENT){
            $retClient = $gClientUser;
            if($niuLevel == NIU_LEVEL_1){
                $retClient['pay_result'] = $gHostUser['bet_num'] * $gHostUser['bet_times'] / 2;

            }else{
                $retClient['pay_result'] = $gHostUser['bet_num'] * $gHostUser['bet_times'];
            }
            $retHost['pay_result'] = 0 - $retClient['pay_result'];
        }

        $retAll = array(
            'host' => $retHost,
            'client' => $retClient,
        );
        return $retAll;
    }


    /**
     * 判断输赢
     * @param string $host_open_ending 庄家尾数
     * @param string $client_open_ending 闲家尾数
     * @return int
     */
    protected function _winCheck($host_open_ending, $client_open_ending) {

        $retWin = NIU_WIN_CLIENT;  //默认闲家赢

        //重置 0 为 10 最大
        if(0 == $host_open_ending){
            $host_open_ending = 10;
        }
        if(0 == $client_open_ending){
            $client_open_ending = 10;
        }

        //一般情况
        if($host_open_ending > $client_open_ending){
            $retWin = NIU_WIN_HOST;  //庄家赢
        }elseif($client_open_ending > $host_open_ending){
            $retWin = NIU_WIN_CLIENT;  //闲家赢
        }elseif($client_open_ending == $host_open_ending){
            //同点
            if( ($host_open_ending >= 2)
                && ($host_open_ending <=4) ){
                //庄家赢
                $retWin = NIU_WIN_HOST;  //庄家赢
            }else{
                //平局
                $retWin = NIU_WIN_MIN;
            }
        }

        return $retWin;
    }



    ///////////////////////////////////////////////////////////////////
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
                $result !== false ? $this->success('恭喜, 数据保存成功!', '') : $this->error('数据保存失败, 请稍候再试!');
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

}
