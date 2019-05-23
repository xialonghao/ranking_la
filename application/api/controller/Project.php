<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use GatewayWorker\Lib\Db;
use think\Validate;
use think\Controller;
use think\Collection;
use app\api\controller\Order;
use app\admin\controller\Contraband;
use fast\Http;
/**
 * 项目接口noneedlogin
 */
class Project extends Api
{

    protected $noNeedLogin = ['get_channel', 'is_contraband', 'add_project_2','countpay_king','countproject_particular','index'];
    protected $noNeedRight = '*';
    protected $project = null;
//
    public function _initialize()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: toresetpwdken, Origin, X-Requested-With, Content-Type, Accept, Authorization");
        header('Access-Control-Allow-Methods: POST,GET,PUT,DELETE');
        $this->project = new \app\admin\model\Projcet;
        parent::_initialize();
    }

    /**
     * 创建项目步骤1
     */
    public function add_project_1()
    {
        $name = $this->request->request('name');
        $address = $this->request->request('address');
        $logo_image = $this->request->request('logo_image');
        $token = $this->request->request('token');
        if (!$name || !$address) {
            $this->error(__('Invalid parameters'));
        }
        //用户信息
        $user = \app\common\library\Token::get($token);
        $data = [
            'name' => $name,
            'address' => $address,
            'user_id' => $user['user_id'],
            'status' => 0,
            'pay_status' => 0,
            'logo_image'=>$logo_image,
        ];
//        print_r($data);die;
        //判断项目
        $count = $this->project->where($data)->count();
        if ($count) {
            $this->error(__("Project is exists"));
        }
        $data['num'] = 'ZH_' . date('YmdHis', time()) . "_" . Random::alnum() . '_Pj_' . $user['user_id'];
        $ret = $this->project->insertGetId($data);
        if ($ret) {
            $data['id'] = $ret;
            $this->success(__('Successful'), $data);
        } else {
            $this->error($this->project->getError());
        }

    }

    /**
     * 创建项目步骤2
     * 创建关键词
     */
    public function add_project_2()
    {
        $token = $this->request->request('token');
        $keyword = $this->request->request('keyword');
        $days = $this->request->request('days');
        $project_id = $this->request->request('project_id');
        $project_num = $this->request->request('project_num');
        $channel = $this->request->request('channel');
        //用户信息
        $user = \app\common\library\Token::get($token);
        if (!$keyword || !$days || !$project_id || !$project_num) {
            $this->error(__('Invalid parameters'));
        }
        $data['project_id'] = $project_id;
        $data['project_num'] = $project_num;
        $data['project_num'] = $project_num;
        $data['user_id'] = $user['user_id'];
        $keyword = explode(",", $keyword);
        if (empty($keyword)) $this->error(__('Invalid parameters'));
        $ordata = [];
        foreach ($keyword as $val) {
            if ($val) {
                $data['name'] = $val;
                //判断关键词是否被创建
                $count = \think\Db::table("pml_keyword")->where($data)->count();
                if ($count) continue;
//                $contraband = new Contraband();
                if ($this->checkword($val)) $this->error("敏感词：" . $val);
//                if ($contraband->checkword($val)) $this->error("敏感词：" . $val);
                $data['username'] = read_table_filed('pml_user', "username", array('id' => $user['user_id']));
//                print_r($data);
                $keyid = \think\Db::table("pml_keyword")->insertGetId($data);
                if (!$keyid) $this->model->rollback();
                $kdata = $data;
                $kdata['keyword_id'] = $keyid;
                $kdata['channel'] = explode(",", $channel);
                //追加订单记录
//                print_r($kdata);die;
                $order = new Order();
                $ordata[] = $order->add_channel_order($kdata, $days);
            }
        }

        $project['amount'] = \think\Db::table("pml_order")->where('project_id', $project_id)->sum('amount');
        $project['days'] = $days;
        //更新项目信息
        $project['endtime'] = date('Ymd', time() + $days * 24 * 60 * 60);
        $this->project->where(['id' => $project_id])->update($project);
        $this->success(__("Successful"), $ordata);
    }
    public function do_excelImport() {
        $file = request()->file('file');
//        print_r($file);die;
        $pathinfo = pathinfo($file->getInfo()['name']);
        $extension = $pathinfo['extension'];
        $savename = time().'.'.$extension;
        if($upload = $file->move('./upload',$savename)) {
            $savename = './upload/'.$upload->getSaveName();
            vendor('phpoffice.PHPExcel.PHPExcel');
            vendor('phpoffice.PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
            $objPHPExcel = $objReader->load($savename,$encode = 'utf8');
            $sheetCount = $objPHPExcel->getSheetCount();
            for($i=0 ; $i<$sheetCount ; $i++) {    //循环每一个sheet
                $sheet = $objPHPExcel->getSheet($i)->toArray();
//                print_r($sheet);
                unset($sheet[0]);
//                print_r($sheet);
                foreach ($sheet as $v) {
                    $token = $this->request->request('token');
                    $project_id = $this->request->request('project_id');
                    $project_num = $this->request->request('project_num');
                    $keyword = $v[0];
                    $channel  = $v[1];
                    $days = $v[2];
                    //用户信息
                    $user = \app\common\library\Token::get($token);
                    if (!$keyword || !$days || !$project_id || !$project_num) {
                        $this->error(__('Invalid parameters'));
                    }
                    $data['project_id'] = $project_id;
                    $data['project_num'] = $project_num;
                    $data['project_num'] = $project_num;
                    $data['user_id'] = $user['user_id'];
                    $keyword = explode(",", $keyword);
                    if (empty($keyword)) $this->error(__('Invalid parameters'));
                    $ordata = [];
                    foreach ($keyword as $val) {
                        if ($val) {
                            $data['name'] = $val;
                            //判断关键词是否被创建
                            $count = \think\Db::table("pml_keyword")->where($data)->count();
                            if ($count) continue;
//                $contraband = new Contraband();
                            if ($this->checkword($val)) $this->error("敏感词：" . $val);
//                if ($contraband->checkword($val)) $this->error("敏感词：" . $val);
                            $data['username'] = read_table_filed('pml_user', "username", array('id' => $user['user_id']));
//                print_r($data);
                            $keyid = \think\Db::table("pml_keyword")->insertGetId($data);
                            if (!$keyid) $this->model->rollback();
                            $kdata = $data;
                            $kdata['keyword_id'] = $keyid;
                            $kdata['channel'] = explode(",", $channel);
                            //追加订单记录
//                print_r($kdata);die;
                            $order = new Order();
                            $ordata[] = $order->add_channel_order($kdata, $days);
                        }
                    }

                    $project['amount'] = \think\Db::table("pml_order")->where('project_id', $project_id)->sum('amount');
                    $project['days'] = $days;
                    //更新项目信息
                    $project['endtime'] = date('Ymd', time() + $days * 24 * 60 * 60);
                    $this->project->where(['id' => $project_id])->update($project);
                    $this->success(__("Successful"), $ordata);
                }
            }
            $this->success(__('Successful'),1);
        } else {
            return $upload->getError();
        }
    }
    /**
     * 项目创建步骤三
     */
    public function add_project_3()
    {
        $token = $this->request->request('token');
        $project_num = $this->request->request('project_num');
        //用户信息
        $user = \app\common\library\Token::get($token);
        if (!$project_num) {
            $this->error(__('Invalid parameters'));
        }
        //项目信息
        $data = $this->project->where('num', $project_num)->find();
        $data['keyword_num'] = \think\Db::table("pml_keyword")->where('project_num', $project_num)->count();
        $data['channel_num'] = \think\Db::table("pml_order")->where('project_num', $project_num)->group('channel_id')->count();
        $data['usable_money'] = \think\Db::table('pml_user')->where('id="'.$user['user_id'].'"')->field('money')->find();
        //优惠券
        $data['amount'] = $this->project->where('num', $project_num)->find();
        $coupon['coupon'] = $this->get_pay_coupon($user['user_id'], $data['amount']);
        $coupon['data'] = $data;
        $this->success(__("Successful"), $coupon);
    }
    /**
     * 违禁词
     */
    function checkword($wordkey=''){
        $url = "http://47.102.127.12:8000/api/examine/";
        $res = json_decode(Http::post($url,['wordkey'=>$wordkey]),true);
        if($res['code'] == 1 && $res['result'] == 1){
            $result = true;
        }else{
            $result = false;
        }
        return $result;
    }

    /**
     * 可支付优惠券
     */
    private function get_pay_coupon($user_id, $amount)
    {
        //用户信息
        $coupon = \think\Db::table("pml_usercoupon")
            ->alias('a')
            ->field('a.coupon_id, b.type, b.price, a.status,b.endtime, b.amount')
            ->join('pml_coupon b', 'a.coupon_id = b.id')
            ->where('a.user_id', $user_id)
            ->group('a.coupon_id')
            ->select();
        foreach ($coupon as $key => $val) {
            if ($val
                && $val['type'] == 1
                && $val['endtime'] < date('Y-m-d', time())
                && $val['status'] == 0
                || $val['amount'] > $amount
            ) {
                unset($coupon[$key]);
            }
        }
        return $coupon;
    }


    /**
     * 取得渠道数据
     */
    public function get_channel()
    {
        $this->success(__('Successful'), \think\db::name('channel')->field('id,name')->where(['status' => 1])->select());
    }

    /**
     * 判断是否违禁词
     */
    public function is_contraband()
    {
        $keyword = $this->request->request('keyword');
        \think\db::name('contraband')->where('code', 'like', '%' . $keyword . '%')->find();
        $contraband = new Contraband();
        //调用接口检测违禁词
        if (!$contraband->checkword($keyword)) {
            $this->error(__("This keyword is disabled"));
        } else {
            $this->success(__("Successful"));
        }
    }

    /**
     * 项目支付
     */
    public function pay_project()
    {
        $token = $this->request->request('token');
        $project_num = $this->request->request('project_num');
        $coupon_id = $this->request->request('coupon_id');
        $amount = $this->request->request('amount');
        //用户信息
        $user = \app\common\library\Token::get($token);

        if (!$project_num) {
            $this->error(__('Invalid parameters'));
        }

        //项目信息
        $project = $this->project->where('num', $project_num)->find();
//        print_r(json_decode(json_encode($project,true),true));die;
        if ($project['pay_status'] == 1) $this->error(__("Pay exists"));
        //用户余额
        $money = \think\db::name('user')->field("money")->where('id', $user['user_id'])->find();
        if ($money['money'] < $amount) $this->error(__('Money not enghou'));
        //优惠券信息
        if ($coupon_id) {
            $coupon = \think\db::name('coupon')->where('id', $coupon_id)->find();
            if (empty($coupon)) $this->error(__("Coupon endtime"));
            //修改优惠券使用状态
            \think\db::name('usercoupon')->where('coupon_id', $coupon_id)->where('user_id', $user['user_id'])->update(['status' => 1]);
            $upproject['coupon'] = $coupon['price'];
            $amount = $amount - $coupon['price'];

        }
        //更新项目支付状态
        $upproject = [
            'pay_time' => date('Y-m-d H:i:s', time()),
            'pay_status' => 1,
            'status'=>1,
        ];

        if (!$this->project->where('num', $project_num)->update($upproject)) $this->project->rollback();
        //扣除用户余额
        if (!\think\db::name('user')->where('id', $user['user_id'])->setDec('money', $amount)) $this->project->rollback();
//        print_r($this->project->where('num', $project_num)->select());

        $this->success(__("Pay success!"));
    }

    /**
     * 用户项目列表
     */
    public function project_list()
    {
        $token = $this->request->request('token');
        //用户信息
        $user = \app\common\library\Token::get($token);
        $data = $this->project->field('id, num, name, amount, create_time, pay_status')->where('user_id', $user['user_id'])->select();
        $this->success(__("Successful"), $data);
    }

    /**
     * 订单列表
     */
    public function order_manage()
    {
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
//        $res = $this->project->alias('a')
//            ->field('a.id,a.num,a.name,a.status,a.amount,a.create_time')
//            ->join('pml_order b', 'a.id=b.project_id')
//            ->where('a.user_id="' . $user['user_id'] . '" and a.status="0" and pay_status="0"')
//            ->group('a.id')
//            ->select();
        $res = $this->project
            ->field('id,num,name,status,amount,create_time')
            ->where('user_id="' . $user['user_id'] . '" and status="0" and pay_status="0"')
            ->group('id')
            ->select();
//        print_r($res);die;
        if ($res) {
            $this->success(__('Successful'), $res);
        } else {
            $this->error($this->project->getError());
        }
    }

    /**
     * 订单详细列表
     */
    public function order_detailed_list()
    {
        $id = $this->request->request('id');
        $token = $this->request->request('token');
        if (!$id || !$token) {
            $this->error(__('Invalid parameters'));
        }
        $user = \app\common\library\Token::get($token);
        $res =$this->project->alias('a')
            ->join('pml_order b','a.id=b.project_id')
            ->field('b.keyword_name,b.channel_name,a.days,b.price,b.amount,b.heat,b.conformity')
            ->where('a.user_id=' . $user['user_id'] . ' and a.id=' . $id . '')
            ->select();
        if ($res) {
            $this->success(__('Successful'), $res);
        } else {
            $this->error($this->project->getError());
        }

    }

    /**
     * 订单详细标题
     */
    public function order_detailed_title()
    {
        $id = $this->request->request('id');
        $token = $this->request->request('token');
        if (!$id || !$token) {
            $this->error(__('Invalid parameters'));
        }
        $user = \app\common\library\Token::get($token);

        $res = $this->project->alias('a')
            ->join('pml_order b', 'a.id=b.project_id')
            ->join('pml_keyword c', 'b.project_id=c.project_id')
            ->field('a.num,a.name as project_name,a.pay_status,a.days,a.amount,a.create_time,a.id,a.address,a.logo_image')
            ->where('a.user_id="' . $user['user_id'] . '" and a.id="' . $id . '"')
            ->group('a.num')
            ->find();
        //渠道
        $res_qudao = \think\Db::table("pml_order")->where('user_id="' . $user['user_id'] . '" and project_id="' . $id . '"')->group('channel_id')->count();
//        print_r($res_qudao);
        //关键次数
        $res_keycishu = \think\Db::table("pml_keyword")->where('user_id="' . $user['user_id'] . '" and project_id="' . $id . '"')->count();
        $res['res_qudao']=$res_qudao;
        $res['res_keycishu']=$res_keycishu;
        if ($res) {
            $this->success(__('Successful'), $res);
        } else {
            $this->error($this->project->getError());
        }
    }

    /**
     * 取消支付
     */
    public function cancel_pay()
    {
        $id = $this->request->request('id');
        $token = $this->request->request('token');
        if (!$id || !$token) {
            $this->error(__('Invalid parameters'));
        }
        $user = \app\common\library\Token::get($token);
        $res = $this->project->where('id="' . $id . '" and user_id="' . $user['user_id'] . '"')->delete();
        $res1 = \think\Db::table("pml_keyword")->where('project_id="' . $id . '" and user_id="' . $user['user_id'] . '"')->delete();
        $res2 = \think\Db::table("pml_order")->where('project_id="' . $id . '" and user_id="' . $user['user_id'] . '"')->delete();
        if ($res) {
            $this->success(__('Successful'), $res);
        } else {
            $this->error($this->project->getError());
        }
    }

    /**
     * 项目自动过期
     */
    public function project_finish()
    {
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        $res = $this->project->where('user_id="' . $user['user_id'] . '"and pay_status="1"')->select();

//        $dqtes=$res->pay_time;
        foreach ($res as $key => $val) {
            $shijian = $res[$key]['endtime'];
            $edtimes = strtotime($shijian);
//            var_dump(date('Ymd'),$edtimes);
//            var_dump(date('Ymd'),time());
//            var_dump(time());
            $data = array(
                'status' => 2,
            );
            if (time() >= $edtimes) {
                $rese = $this->project->where('user_id="' . $user['user_id'] . '"')
                    ->update($data);
                if ($rese) {
                    $this->success(__('Successful'), $rese);
                } else {
                    $this->error($this->project->getError());
                }
            }
        }

    }
    /**
     * 项目自动过期余额退回账户
     */
        public function project_finishmonyth(){
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        $res = $this->project->where('user_id="'.$user['user_id'].'" and status="2"')->select();
        $user_username = \think\Db::table('pml_user')->where('id="'.$user['user_id'].'"')->find();
        $data=array();
        $user_mon='0';
            $data_money=array();
        foreach($res as $key=>$val){
            $user_mon +=$val['money'];
            $moneys = $user_username['money']+$user_mon;
            $user_money = \think\Db::table('pml_user')->where('id="'.$user['user_id'].'"')->update(['money'=>$moneys]);
            $user_name = \think\Db::table('pml_user')->where('id="'.$user['user_id'].'"')->find();
            $data[] = [
                'recharge_num'=>$val['num'],
                'money'=>$val['money'],
                'user_id'=>$val['user_id'],
                'status'=>2,
                'account_money'=>$user_name['money'],


            ];
            $refund = \think\Db::table('pml_refund')->insertAll($data);
            $data_money[] = [
                'id'=>$val['id'],
                'amount'=>0,


            ];
            $refund = \think\Db::table('pml_refund')->update($data_money);

        }
        if ($refund) {

            $this->success(__('Successful'), $refund);
        } else {
            $this->error($this->project->getError());
        }

    }
    /**
     * 项目历史记录列表
     */
    public function project_histoy_list()
    {
//        $token = $this->request->request('token');
//        $user = \app\common\library\Token::get($token);
//        //这是拿到结束历史项目的状态id
//        $data = $this->project->alias('a')
//            ->where('user_id="' . $user['user_id'] . '" and status="2"')->field('id,user_id')->select();
//        $s1 = json_decode(json_encode($data, true), true);
//        $array4 = array();
//        $arr =array();
//        foreach ($s1 as $key => $val) {
//            $res = $this->project->alias('a')
//                ->join('pml_order b', 'a.id=b.project_id')
//                ->join('pml_keyword c', 'b.keyword_id=c.id')
//                ->where('a.user_id="' . $user['user_id'] . '" and c.project_id="' . $data[$key]['id'] . '"')
//                ->field('a.id,a.name,a.num,a.days,b.reach_count,b.amount,a.create_time,a.endtime,a.days,count(b.complete_days) as wuxiao')
//                ->select();
//            $s2 = json_decode(json_encode($res, true), true);
//            $reo = $this->project->alias('a')->join('pml_keyword b', 'a.id=b.project_id')
//                ->where('a.user_id="' . $user['user_id'] . '" and b.project_id="' . $data[$key]['id'] . '"')
//                ->field('count(b.name) as key_countname,b.project_id')
//                ->select();
//            $s3 = json_decode(json_encode($reo, true), true);
//            $ret = $this->project->alias('a')
//                ->join('pml_order b', 'a.id=b.project_id')
//                ->where('a.user_id="' . $user['user_id'] . '" and b.project_id="' . $data[$key]['id'] . '"')
//                ->field('b.channel_name,b.project_id,b.channel_id')
//                ->group('channel_id')
//                ->select();
//            $s4 = json_decode(json_encode($ret, true), true);
//            $rate = $this->project->alias('a')
//                ->join('pml_order b', 'a.id=b.project_id')
//                ->where('a.user_id="' . $user['user_id'] . '" and b.project_id="' . $data[$key]['id'] . '"')
//                ->field('a.days,b.reach_count,a.id,count(b.complete_days) as wuxiao')
//                ->select();
//            $s5 = json_decode(json_encode($rate, true), true);
////            print_r($s5);
//
//            foreach($s5 as $key=>$val){
//
//                $arr[$val['id']] = $val['days']/$val['reach_count'];
//            }
//            $array4['percent']=$arr;
//            foreach ($s2 as $key => $val) {
//                $array4[$key][] = $val;
//            }
//
//            foreach ($s3 as $key => $val) {
//                $array4[$key][] = $val;
//            }
//            foreach ($s4 as $key => $val) {
//                $array4[$key][] = $val;
//            }
//
//        }
//
//        $this->success(__('Successful'), $array4);
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        //这是拿到结束历史项目的状态id
        $data = $this->project->where('user_id="' . $user['user_id'] . '" and status="2" and pay_status="1"')->field('id,user_id')->select();

        $s1 = json_decode(json_encode($data, true), true);
//        print_r($s1);die;
        $array4 = array();
        $arr = array();
        foreach ($s1 as $key => $val) {
            $res = $this->project->alias('a')
                ->join('pml_order b', 'a.id=b.project_id')
                ->join('pml_keyword c', 'b.keyword_id=c.id')
                ->where('a.user_id="' . $user['user_id'] . '" and c.project_id="' . $data[$key]['id'] . '"')
                ->field('a.id,a.name,a.num,a.days,b.reach_count,a.amount,a.create_time,a.endtime,a.days,count(b.complete_days) as wuxiao,a.logo_image')
                ->select();
//            c.name as uname
//            b.complete_days
//            count(b.complete_days)
            $s2 = json_decode(json_encode($res, true), true);
//            print_r($s2);
            $reo = $this->project->alias('a')
                ->join('pml_keyword b', 'a.id=b.project_id')
                ->where('a.user_id="' . $user['user_id'] . '" and b.project_id="' . $data[$key]['id'] . '"')
                ->field('count(b.name) as key_countname,b.project_id')
                ->select();
            $s3 = json_decode(json_encode($reo, true), true);

//            print_r($s3);
            //渠道
            $ret = $this->project->alias('a')
                ->join('pml_order b', 'a.id=b.project_id')
                ->where('a.user_id="' . $user['user_id'] . '" and b.project_id="' . $data[$key]['id'] . '"')
                ->field('b.channel_name,b.project_id,b.channel_id')
                ->group('channel_id')
                ->select();
            $s4 = json_decode(json_encode($ret, true), true);
//            print_r($s4);
            $rate = $this->project->alias('a')
                ->join('pml_order b', 'a.id=b.project_id')
                ->where('a.user_id="' . $user['user_id'] . '" and b.project_id="' . $data[$key]['id'] . '"')
                ->field('a.days,b.reach_count,a.id,count(b.complete_days) as wuxiao')
                ->select();
            $s5 = json_decode(json_encode($rate, true), true);
//            print_r($s5);

            foreach($s5 as $key=>$val){

                if($val['reach_count']==0){
                    $arr[$val['id']] = 0;
                }else{
                    $arr[$val['id']] = $val['reach_count']/$val['days'];
                }

            }
            $array4['percent']=$arr;

            foreach ($s2 as $key => $val) {
                $array4[$key][] = $val;
            }
            foreach ($s3 as $key => $val) {
                $array4[$key][] = $val;
            }
            $aa='';
            $bb='';
            foreach ($s4 as $key => &$val) {
                $aa.=$val['channel_name'].',';
                $bb.=$val['channel_id'].',';
                $val['channel_name']=$aa;
                $val['channel_id']=$bb;
                $array4[$key][] = $val;
            }
        }
//        print_r($array4);die;

        $this->success(__('Successful'),$array4);
    }
    /**
     * 执行项目列表
     */
    public function execute_list()
    {
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        //这是拿到结束历史项目的状态id
        $data = $this->project->where('user_id="' . $user['user_id'] . '" and status="1" and pay_status="1"')->field('id,user_id')->select();

        $s1 = json_decode(json_encode($data, true), true);
//        print_r($s1);die;
        $array4 = array();
        $arr = array();
        foreach ($s1 as $key => $val) {
            $res = $this->project->alias('a')
                ->join('pml_order b', 'a.id=b.project_id')
                ->join('pml_keyword c', 'b.keyword_id=c.id')
                ->where('a.user_id="' . $user['user_id'] . '" and c.project_id="' . $data[$key]['id'] . '"')
                ->field('a.id,a.name,a.num,a.days,b.reach_count,a.amount,a.create_time,a.endtime,a.days,count(b.complete_days) as wuxiao,a.logo_image')
                ->select();
//            c.name as uname
//            b.complete_days
//            count(b.complete_days)
            $s2 = json_decode(json_encode($res, true), true);
//            print_r($s2);
            $reo = $this->project->alias('a')
                ->join('pml_keyword b', 'a.id=b.project_id')
                ->where('a.user_id="' . $user['user_id'] . '" and b.project_id="' . $data[$key]['id'] . '"')
                ->field('count(b.name) as key_countname,b.project_id')
                ->select();
            $s3 = json_decode(json_encode($reo, true), true);

//            print_r($s3);
            //渠道
            $ret = $this->project->alias('a')
                ->join('pml_order b', 'a.id=b.project_id')
                ->where('a.user_id="' . $user['user_id'] . '" and b.project_id="' . $data[$key]['id'] . '"')
                ->field('b.channel_name,b.project_id,b.channel_id')
                ->group('channel_id')
                ->select();
            $s4 = json_decode(json_encode($ret, true), true);
//            print_r($s4);
            $rate = $this->project->alias('a')
                ->join('pml_order b', 'a.id=b.project_id')
                ->where('a.user_id="' . $user['user_id'] . '" and b.project_id="' . $data[$key]['id'] . '"')
                ->field('a.days,b.reach_count,a.id,count(b.complete_days) as wuxiao')
                ->select();
            $s5 = json_decode(json_encode($rate, true), true);
//            print_r($s5);

            foreach($s5 as $key=>$val){

                if($val['reach_count']==0){
                    $arr[$val['id']] = 0;
                }else{

                    $arr[$val['id']] = $val['reach_count']/$val['days'];
                }

            }
            $array4['percent']=$arr;

            foreach ($s2 as $key => $val) {
                $array4[$key][] = $val;
            }
            foreach ($s3 as $key => $val) {
                $array4[$key][] = $val;

            }
            $aa='';
            $bb='';
            foreach ($s4 as $key => &$val) {
                $aa.=$val['channel_name'].',';
                $bb.=$val['channel_id'].',';
                $val['channel_name']=$aa;
                $val['channel_id']=$bb;
                $array4[$key][] = $val;
            }
        }
//    print_r($arr);die;

        $this->success(__('Successful'),$array4);
    }

//执行项目渠道
    public function zhixing_qudao()
    {
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        //这是拿到结束历史项目的状态id
        $data = $this->project->alias('a')
            ->where('user_id="' . $user['user_id'] . '" and status="1"')->field('id,user_id')->select();
        $s1 = json_decode(json_encode($data, true), true);
        $array4 = array();
        foreach ($s1 as $key => $val) {
            $ret = $this->project->alias('a')
                ->join('pml_order b', 'a.id=b.project_id')
                ->where('a.user_id="' . $user['user_id'] . '" and b.project_id="' . $data[$key]['id'] . '"')
                ->field('b.channel_name,b.project_id,b.channel_id')
                ->group('channel_id')
                ->select();
            $s4 = json_decode(json_encode($ret, true), true);
            $aa='';
            $bb='';
            foreach ($s4 as $key => &$val) {
                $aa .= $val['channel_name'] . ',';
                $bb .= $val['channel_id'] . ',';
                $val['channel_name'] = $aa;
                $val['channel_id'] = $bb;
                $array4[$key][] = $val;
                unset($array4[0]);
            }


        }

        $this->success(__('Successful'),$array4);
    }
//历史项目渠道
    public function lishi_qudao()
    {
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        //这是拿到结束历史项目的状态id
        $data = $this->project->alias('a')
            ->where('user_id="' . $user['user_id'] . '" and status="2"')->field('id,user_id')->select();
        $s1 = json_decode(json_encode($data, true), true);
        $array4 = array();
        foreach ($s1 as $key => $val) {
            $ret = $this->project->alias('a')
                ->join('pml_order b', 'a.id=b.project_id')
                ->where('a.user_id="' . $user['user_id'] . '" and b.project_id="' . $data[$key]['id'] . '"')
                ->field('b.channel_name,b.project_id,b.channel_id')
                ->group('channel_id')
                ->select();
            $s4 = json_decode(json_encode($ret, true), true);
            $aa='';
            $bb='';
            foreach ($s4 as $key => &$val) {
                $aa .= $val['channel_name'] . ',';
                $bb .= $val['channel_id'] . ',';
                $val['channel_name'] = $aa;
                $val['channel_id'] = $bb;
                $array4[$key][] = $val;
                unset($array4[0]);
            }


        }

        $this->success(__('Successful'),$array4);
    }
//优化进度
    public function optimize()
    {
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        //这是拿到结束历史项目的状态id
        $data = $this->project->alias('a')
            ->where('user_id="' . $user['user_id'] . '" and status="1"')->field('id,user_id,days,create_time,endtime')->select();
        $s1 = json_decode(json_encode($data, true), true);
//        print_r($s1);die;
        $array4 = array();
//        $count_days ='0';
        foreach ($s1 as $key => &$val) {

//            $val['youhua']=$this->AC($val['create_time']);
                 $val['jindu'] = $this->AC($val['create_time']);
//            $val['day'] = $this->AC('',$val['days']);
        }
//        print_r($s1);
        $this->success(__('Successful'),$s1);
    }
//时间
    function AC($t1){
        date_default_timezone_set("PRC");//设置中国时区
        //$t1 = $s1[$key]['create_time'];//你自己设置一个开始时间
        $t2 = date('Y-m-d m:i:s', time());//获取当前时间, 格式和$t1一致
        $t = strtotime($t2) - strtotime($t1);//拿当前时间-开始时间 = 相差时间
        $t = $t / (3600 * 24);//此时间单位为 天
        if ($t >= 60)//对比当你设置了60天, 那么当大于或等于60天时提示
        {
//          return("时间已到期, 请续费");
        }else
          return ceil( $t);
    }
//项目详细列表
    public function project_list1(){

         $id = $this->request->request('id');
         $token = $this->request->request('token');
         $user = \app\common\library\Token::get($token);
         $res =   $project=\think\Db::table("pml_order")
             ->where('project_id="'.$id.'" and user_id="'.$user['user_id'].'"')
             ->select();
        if ($res) {
            $this->success(__('Successful'), $res);
        } else {
            $this->error($this->project->getError());
        }
    }
    /**
     * 关键词优化热度价格
     */
    public function keyword_optimizer(){
        $pty_token = json_decode(Sms::pty_token(),true);
        if($pty_token['success']['token']==''){
             $this->success('token为空');
        }
        $token = $this->request->request('token');
        $project_id = $this->request->request('project_id');
//        var_dump($project_id);
//        var_dump($token);die;
        $user = \app\common\library\Token::get($token);
        $res = \think\Db::table("pml_order")->where('user_id="'.$user['user_id'].'" and project_id="'.$project_id.'"')->field('id,keyword_name,channel_name,channel_id')->select();
        $dates =array();
//        bdpc :  百度PC=1
//        bdios:  百度移动=3
//        sgpc:  搜狗PC=2
//        sgios:  搜狗移动=4
//        qh360:   360=5
//        sm: 神马=6
//达标率 当前时间-创建时间=扣分次数/运行天数
        foreach($res as $key=>&$val){

            $val['project_id']=$val['id'];
            $val['key']=$val['keyword_name'];
            if($val['channel_id']==1){
                $val['elastic']='bdpc';
            }else if($val['channel_id']==2) {
                $val['elastic'] = 'sgpc';
            }else if($val['channel_id']==3) {
                $val['elastic'] = 'bdios';
            }else if($val['channel_id']==4) {
                $val['elastic'] = 'sgios';
            }else if($val['channel_id']==5) {
                $val['elastic'] = 'qh360';
            }else if($val['channel_id']==6){
                $val['elastic'] = 'sm';
            }
            unset($val['id']);
            unset($val['keyword_name']);
            unset($val['channel_name']);
            unset($val['channel_id']);
        }
        $post_data = json_encode(array('code'=>520,'data'=>$res));
        $url = 'http://api.ezhihuo.com/apipy/price/';
         $headers = array(
            "token:".$pty_token['success']['token'].""
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $re_success = json_decode($output,true);
        if($re_success['data'][0]=='参数不合法'){
                $this->success(__('参数不合法找python'),0);
        }
        $pro_days = $this->project->where('user_id="'.$user['user_id'].'" and id="'.$project_id.'"')->find();
        $list=array();
        foreach($re_success['data'] as $key=>$val){
//            print_r($val);
           $list = [
               'id'=>$val['project_id'],
               'heat'=>$val['hot'],
               'conformity'=>$val['moderate'],
               'price'=>$val['money'],
               'amount'=>$val['money']*$pro_days['days'],
           ];
            $orgd = \think\Db::table("pml_order")->update($list);
        }
        $ret = $ret = $this->project->alias('a')
            ->join('pml_order b', 'a.id=b.project_id')
            ->where('a.user_id="' . $user['user_id'] . '" and b.project_id="' . $project_id . '"')
            ->field('a.days,b.keyword_name,b.channel_name,b.price,b.amount,b.heat,b.conformity,b.id')
            ->select();
        $jiage = 0;
        foreach ($ret as $key=>$val){
            $jiage +=$val['amount'];
        }
        $this->project->update(['id'=>$project_id,'amount'=>$jiage]);
        if ($ret) {
            $this->success(__('Successful'), $ret);
        } else {
            $this->error($this->project->getError());
        }
    }
    /**
     * 新建第二步关键词移除
     */
    public function keyproject_del(){
        $token = $this->request->request('token');
        $id = $this->request->request('id');
        $user = \app\common\library\Token::get($token);
        $res = \think\Db::table('pml_order')->where('user_id="'.$user['user_id'].'"and id="'.$id.'"')->delete();
        if ($res) {
            $this->success(__('Successful'), $res);
        } else {
            $this->error($this->project->getError());
        }
    }

    /**
     * 支付成功后查排名
     */
    public function pay_king(){
        $pty_token = json_decode(Sms::pty_token(),true);
        if($pty_token['success']['token']==''){
            $this->success('token为空');
        }
        $token = $this->request->request('token');
        $project_id = $this->request->request('project_id');

        $user = \app\common\library\Token::get($token);
        $res = $this->project->alias('a')
            ->join('pml_order b', 'a.id=b.project_id')
            ->where('b.user_id="'.$user['user_id'].'" and project_id="'.$project_id.'"')
            ->field('b.id,b.keyword_name,b.channel_name,channel_id,a.address,b.project_id')
            ->select();
//        print_r($res);die;
        $ret = json_decode(json_encode($res, true), true);
        foreach($ret as $key=>&$val){
            $val['key']  = $val['keyword_name'];
            $val['pro_id'] = $val['project_id'];
            $val['link']   = $val['address'];
            if($val['channel_id']==1){
                $val['elastic']='bdpc';
            }else if($val['channel_id']==2) {
                $val['elastic'] = 'sgpc';
            }else if($val['channel_id']==3) {
                $val['elastic'] = 'bdios';
            }else if($val['channel_id']==4) {
                $val['elastic'] = 'sgios';
            }else if($val['channel_id']==5) {
                $val['elastic'] = 'qh360';
            }else if($val['channel_id']==6){
                $val['elastic'] = 'sm';
            }
            unset($val['project_id']);
            unset($val['id']);
            unset($val['keyword_name']);
            unset($val['channel_name']);
            unset($val['channel_id']);
            unset($val['address']);
        }
//        print_r($ret);die;
        $post_data=json_encode(array('code'=>520,'data'=>$ret));

        $url = 'http://api.ezhihuo.com/apipy/runspider/ ';
        $headers = array(
            "token:".$pty_token['success']['token'].""
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $re_success = json_decode($output,true);
//        print_r($re_success);
        if($re_success){
            $this->success(__("Successful"),$re_success);
        }else{
            $this->error($this->worder->getError());
        }

    }
    /**
     * 每天定时请求已执行项目启动爬虫
     */
    public function countpay_king(){
        $pty_token = json_decode(Sms::pty_token(),true);
        if($pty_token['success']['token']==''){
            $this->success('token为空');
        }
//        $token = $this->request->request('token');
//        $user = \app\common\library\Token::get($token);
        $res = $this->project->alias('a')
            ->join('pml_order b', 'a.id=b.project_id')
            ->where('a.status="1"')
            ->field('b.id,b.keyword_name,b.channel_name,channel_id,a.address,b.project_id')
            ->select();
        $ret = json_decode(json_encode($res, true), true);
        foreach($ret as $key=>&$val){
            $val['key']  = $val['keyword_name'];
            $val['pro_id'] = $val['project_id'];
            $val['link']   = $val['address'];
            if($val['channel_id']==1){
                $val['elastic']='bdpc';
            }else if($val['channel_id']==2) {
                $val['elastic'] = 'sgpc';
            }else if($val['channel_id']==3) {
                $val['elastic'] = 'bdios';
            }else if($val['channel_id']==4) {
                $val['elastic'] = 'sgios';
            }else if($val['channel_id']==5) {
                $val['elastic'] = 'qh360';
            }else if($val['channel_id']==6){
                $val['elastic'] = 'sm';
            }
            unset($val['project_id']);
            unset($val['id']);
            unset($val['keyword_name']);
            unset($val['channel_name']);
            unset($val['channel_id']);
            unset($val['address']);
        }

        $post_data=json_encode(array('code'=>520,'data'=>$ret));
//        print_r($post_data);die;
        $url = 'http://api.ezhihuo.com/apipy/runspider/';
        $headers = array(
            "token:".$pty_token['success']['token'].""
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $re_success = json_decode($output,true);
        $this->success(__('已请求添加成功'),$re_success);
    }
    /**
     * 每天定时请求排名
     */
    public function countproject_particular (){
        $pty_token = json_decode(Sms::pty_token(),true);
        if($pty_token['success']['token']==''){
            $this->success('token为空');
        }
        $carryout = $this->project->where('status="1"')->field('id')->select();
        $carryout_id = json_decode(json_encode($carryout, true), true);
//        print_r($carryout_id);die;
        for($z=0;$z<count($carryout_id);$z++){
            $res = $this->project->alias('a')
                ->join('pml_order b', 'a.id=b.project_id')
                ->where('project_id="'.$carryout_id[$z]['id'].'"')
                ->field('b.id,b.keyword_name,b.channel_name,channel_id,a.address,a.days,b.create_time,project_id,b.reach_count,b.user_id')
                ->select();
            $ret = json_decode(json_encode($res, true), true);

            foreach($ret as $key=>&$val){
                $val['keyword']=$val['keyword_name'];
                $val['link']=$val['address'];
                $val['time']=date('Ymd',time());
                if($val['channel_id']==1){
                    $val['ditch']='bdpc';
                }else if($val['channel_id']==2) {
                    $val['ditch'] = 'sgpc';
                }else if($val['channel_id']==3) {
                    $val['ditch'] = 'bdios';
                }else if($val['channel_id']==4) {
                    $val['ditch'] = 'sgios';
                }else if($val['channel_id']==5) {
                    $val['ditch'] = 'qh360';
                }else if($val['channel_id']==6){
                    $val['ditch'] = 'sm';
                }
                unset($val['id']);
                unset($val['keyword_name']);
                unset($val['channel_name']);
                unset($val['channel_id']);
                unset($val['address']);
                unset($val['create_time']);
                unset($val['days']);
                unset($val['reach_count']);
            }

            $ret_id = json_decode(json_encode($res, true), true);

            $word_id = array();
            foreach($ret_id as $key=>&$val){
                if( $val['reach_count']==null){
                    $val['reach_count']='';
                }
                unset($val['keyword_name']);
                unset($val['channel_name']);
                unset($val['channel_id']);
                unset($val['address']);
                unset($val['days']);
                unset($val['create_time']);
            }

            $post_data=json_encode(array('code'=>520,'parame'=>$ret_id,'data'=>$ret));
//            print_r($post_data);die;
            $url = 'http://api.ezhihuo.com/apipy/timing/';
            $headers = array(
                "token:".$pty_token['success']['token'].""
            );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            $re_success = json_decode($output,true);
//            print_r($re_success);die;
//            print_r($output);die;
            if($re_success['msg']=='参数不合法'){
                $this->success(__('参数不合法'),2);
            }
            if($re_success['code']==40000){
                $this->success(__('请求排名失败'),2);
            }
            if($re_success['data']==''){
                $this->success(__('ptyhone返回为空'),2);
            }
            $datas = array();

            for($i=0;$i<count($re_success['data']);$i++){
                for($j=0;$j<count($re_success['parame']);$j++){
                    if($i==$j){
                        $datas[$i]['user_id'] = $re_success['parame'][$i]['user_id'];
                        $datas[$i]['id'] = $re_success['parame'][$j]['id'];
                        $datas[$i]['reach_count'] = $re_success['parame'][$j]['reach_count'];
//                        $datas[$i]['title'] = $re_success['data'][$i]['title'];
                        $datas[$i]['project_id'] = $re_success['data'][$i]['project_id'];
//                        $datas[$i]['link'] = $re_success['data'][$i]['link'];
                        $datas[$i]['keyword'] = $re_success['data'][$i]['keyword'];
                        $datas[$i]['time'] = $re_success['data'][$i]['time'];
                        $datas[$i]['paiming'] = $re_success['data'][$i]['paiming'];
                        $datas[$i]['ditch'] = $re_success['data'][$i]['ditch'];
                    }

                }
            }

//          print_r($datas);die;
            $list=array();
            $lists=array();
            foreach($datas as $key=>$val){
//                var_dump(intval($val['reach_count']));die;
                if($val['paiming']>10){
                    $list = [
                        'id'=>$val['id'],
                        'taday_ranking'=>$val['paiming'],
                        'reach_count'=>intval($val['reach_count'])+1,
                        'complete_status'=>1,
                    ];
                    $orgd = \think\Db::table("pml_order")->update($list);
                    $ins = $this->project->alias('a')
                        ->join('pml_order b', 'a.id=b.project_id')
                        ->where('b.id="'.$val['id'].'"')
                        ->field('b.id as order_id,b.project_num,b.channel_id,b.channel_name,b.keyword_id,b.keyword_name,b.taday_ranking,b.price,b.project_id,a.name,b.user_id,a.amount')
                        ->select();
                    $user_name = \think\Db::table("pml_user")->where('id="'.$val['user_id'].'"')->field('username')->find();
//                    print_r($user_name);
                   $coms = json_decode(json_encode($ins, true), true);
//                   print_r($coms);die;
                    $inserts=array();
                    $aSpotCat = 0;
                    //扣费
                    foreach($coms as $key=>$val){
                        $aSpotCat = $val['amount']-$val['price'];
                        $kf = $this->project->where('id="'.$val['project_id'].'"')->update(['amount'=>$aSpotCat]);
                        if($kf==0){
                           echo '扣费失败';
                        }
                    }
                    foreach($coms as $key=>$val){
                        $inserts[]=array(
                            'order_sn'=>$val['project_num'],
                            'channel_id'=>$val['channel_id'],
                            'channel_name'=>$val['channel_name'],
                            'keyword_id'=>$val['keyword_id'],
//                          'create_time'=>date('Y-m-d h:i:s', time()),
                            'ranking'=>$val['taday_ranking'],
                            'money'=>$val['price'],
                            'user_id'=>$val['user_id'],
                            'user_name'=>$user_name['username'],
                            'projcet_id'=>$val['project_id'],
                            'projcet_name'=>$val['name'],
                            'status'=>1,
                            'is_qualified'=>1,
                            'order_id'=>$val['order_id'],
                        );

                    }
                    $cai_ls=\think\Db::table('pml_consumption')->insertAll($inserts);
                    if($cai_ls){
                        $this->success(__('已请求添加成功'),$ins);
                    }
                }else{

                    $lists = [
                        'id'=>$val['id'],
                        'taday_ranking'=>$val['paiming'],
                        'complete_status'=>0,
                    ];
                    $ins = \think\Db::table("pml_order")->update($lists);
                    if($ins) {
                        $this->success(__('已请求添加成功'), $ins);
                    }
                }

            }
        }




    }
/**
 * 请求初始排名
 */
        public function project_particular(){
        $pty_token = json_decode(Sms::pty_token(),true);
        if($pty_token['success']['token']==''){
            $this->success('token为空');
        }
            $token      = $this->request->request('token');
            $project_id = $this->request->request('project_id');
            $user = \app\common\library\Token::get($token);
//            print_r($user['user_id']);
            $res = $this->project->alias('a')
                ->join('pml_order b', 'a.id=b.project_id')
                ->where('b.user_id="'.$user['user_id'].'" and project_id="'.$project_id.'"')
                ->field('b.id,b.keyword_name,b.channel_name,channel_id,a.address,a.days,b.create_time,project_id,b.reach_count')
                ->select();

            $ret = json_decode(json_encode($res, true), true);

            foreach($ret as $key=>&$val){
                $val['keyword']=$val['keyword_name'];
                $val['link']=$val['address'];
                $val['time']=date('Ymd',time());
                if($val['channel_id']==1){
                    $val['ditch']='bdpc';
                }else if($val['channel_id']==2) {
                    $val['ditch'] = 'sgpc';
                }else if($val['channel_id']==3) {
                    $val['ditch'] = 'bdios';
                }else if($val['channel_id']==4) {
                    $val['ditch'] = 'sgios';
                }else if($val['channel_id']==5) {
                    $val['ditch'] = 'qh360';
                }else if($val['channel_id']==6){
                    $val['ditch'] = 'sm';
                }
                unset($val['id']);
                unset($val['keyword_name']);
                unset($val['channel_name']);
                unset($val['channel_id']);
                unset($val['address']);
                unset($val['create_time']);
                unset($val['days']);
                unset($val['reach_count']);
            }
        $ret_id = json_decode(json_encode($res, true), true);

            $word_id = array();
            foreach($ret_id as $key=>&$val){
                if($val['reach_count']==''){
                    $val['reach_count']='0';
                };
                unset($val['keyword_name']);
                unset($val['channel_name']);
                unset($val['channel_id']);
                unset($val['address']);
                unset($val['days']);
                unset($val['create_time']);
            }
//      print_r($res[0]->create_time);die;
//                        print_r($ret_id);die;
        $post_data=json_encode(array('code'=>520,'day'=>7,'time'=>date('Ymd',time()),'parame'=>$ret_id,'data'=>$ret));
//print_r($post_data);exit;
        $url = 'http://api.ezhihuo.com/apipy/rank/';
        $headers = array(
            "token:".$pty_token['success']['token'].""
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $re_success = json_decode($output,true);
//        print_r($re_success);
        if($re_success['data'][0]=' '){
            $this->success(__('没有数据请查看是否启动爬虫请找python'), 1);
        }
        if($re_success['code']==40000){
            $this->success(__('token过期或找python'), 1);
        }
        $datas = array();
        for($i=0;$i<count($re_success['data']);$i++){
            for($j=0;$j<count($re_success['parame']);$j++){
                if($i==$j){

                    $datas[$i]['id'] = $re_success['parame'][$j]['id'];
                    $datas[$i]['reach_count'] = $re_success['parame'][$j]['reach_count'];
                    $datas[$i]['title'] = $re_success['data'][$i]['title'];
                    $datas[$i]['project_id'] = $re_success['data'][$i]['project_id'];
                    $datas[$i]['link'] = $re_success['data'][$i]['link'];
                    $datas[$i]['keyword'] = $re_success['data'][$i]['keyword'];
                    $datas[$i]['time'] = $re_success['data'][$i]['time'];
                    $datas[$i]['paiming'] = $re_success['data'][$i]['paiming'];
                    $datas[$i]['ditch'] = $re_success['data'][$i]['ditch'];

                }

            }
        }
//            print_r($datas);die;
        $list=array();
        foreach($datas as $key=>$val){
//            print_r($val);
            if($val['paiming']>10){
                $list = [
                    'id'=>$val['id'],
//                  'taday_ranking'=>$val['paiming'],
                    'star_ranking'=>$val['paiming'],
                    'reach_count'=>$val['reach_count']+1,
                    'complete_status'=>1,
                ];
                $orgd = \think\Db::table("pml_order")->update($list);
                if($orgd) {
                    $this->success(__('已请求添加成功'), 1);
                }
            }else{
                $lists = [
                    'id'=>$val['id'],
                    'taday_ranking'=>$val['paiming'],
                    'complete_status'=>0,
                ];
                $ins = \think\Db::table("pml_order")->update($lists);
                if($ins) {
                    $this->success(__('已请求添加成功'), $ins);
                }
            }

        }

    }
    /**
     * 关键词详细标题
     */
    public function key_detailed(){
            $token = $this->request->request('token');
            $antistop = $this->request->request('antistop');
            $user = \app\common\library\Token::get($token);
             $res = $this->project->alias('a')
            ->join('pml_order b', 'a.id=b.project_id')
            ->where('b.user_id="'.$user['user_id'].'" and b.keyword_name="'.$antistop.'"')
            ->field('a.days,a.amount,b.keyword_name,b.conus_mount,a.create_time,b.taday_ranking,b.reach_count')
            ->select();
        $what = $this->project->alias('a')
            ->join('pml_order b', 'a.id=b.project_id')
            ->where('b.user_id="'.$user['user_id'].'" and b.keyword_name="'.$antistop.'"')
            ->field('count(b.channel_id),count(b.keyword_name)')
//            ->group('b.channel_id')
            ->select();
        $jiage = $this->project->alias('a')
            ->join('pml_order b', 'a.id=b.project_id')
            ->where('b.user_id="'.$user['user_id'].'" and b.keyword_name="'.$antistop.'"')
            ->field('b.channel_name,b.price,b.reach_count')
            ->select();
        $project_id = $this->project->alias('a')
            ->join('pml_order b', 'a.id=b.project_id')
            ->where('b.user_id="'.$user['user_id'].'" and b.keyword_name="'.$antistop.'"')
            ->field('a.id')
            ->select();
        $zong_cishu =\think\Db::table('pml_keyword')->where('project_id="'.$project_id[0]['id'].'"')->count();
        $whats = json_decode(json_encode($what,true),true);
        $ret = json_decode(json_encode($res,true),true);
        $ret_jiage = json_decode(json_encode($jiage,true),true);
//        print_r($ret);
//        print_r($zong_cishu);die;
        $key_count=0;
        foreach ($ret as $key=>$val){
            $key_count += $val['reach_count'];
        }
//print_r($whats[0]['count(b.keyword_name)']);die;
//           $val['jindu'] = $this->AC($val['create_time'])/$val['days'];
        //优化天天数
             $hed['youhua']=$this->AC($ret[0]['create_time']);
        //总关键词数
             $hed['keyword'] = $zong_cishu;
        //达标关键词数
             $hed['target_count'] = $key_count;
        //宗达标率
             $hed['zong_target'] = $hed['target_count']/$hed['keyword'];
             foreach($ret_jiage as $key=>&$val){
                    $val['keyword']=$hed['youhua']*1;
             }
//        print_r($ret_jiage);die;
            if ($res) {
                $this->success(__('Successful'), array('youhua'=>$hed['youhua'],'keyword'=>$hed['keyword'],'reach_count'=>$hed['target_count'],'zong_target'=>$hed['zong_target'],'res'=>$ret_jiage,'data'=>$res));
            } else {
                $this->error($this->project->getError());
            }

    }
    /**
     * 关键词详细曲线
     */
    public function key_rankingcurve(){
        $pty_token = json_decode(Sms::pty_token(),true);
        if($pty_token['success']['token']==''){
            $this->success('token为空');
        }
        $token = $this->request->request('token');
        $antistop = $this->request->request('antistop');
        $user = \app\common\library\Token::get($token);
        $res = $this->project->alias('a')
            ->join('pml_order b', 'a.id=b.project_id')
            ->where('b.user_id="'.$user['user_id'].'" and b.keyword_name="'.$antistop.'"')
            ->field('a.id,b.keyword_name,b.channel_name,b.channel_id,a.create_time,a.address')
            ->select();
        $ret = json_decode(json_encode($res, true), true);
        foreach($ret as $key=>&$val){
            $val['project_id']=$val['id'];
            $val['keyword']=$val['keyword_name'];
            $val['time']=date('Ymd',strtotime($val['create_time']));
            $val['link']=$val['address'];
            if($val['channel_id']==1){
                $val['ditch']='bdpc';
            }else if($val['channel_id']==2) {
                $val['ditch'] = 'sgpc';
            }else if($val['channel_id']==3) {
                $val['ditch'] = 'bdios';
            }else if($val['channel_id']==4) {
                $val['ditch'] = 'sgios';
            }else if($val['channel_id']==5) {
                $val['ditch'] = 'qh360';
            }else if($val['channel_id']==6){
                $val['ditch'] = 'sm';
            }
            unset($val['id']);
            unset($val['channel_name']);
            unset($val['keyword_name']);
            unset($val['create_time']);
            unset($val['address']);
            unset($val['channel_id']);
        }
        $post_data=json_encode(array('code'=>520,'time'=>date('Ymd',time()),"day"=>7,'data'=>$ret));
//        print_r($post_data);die;
        $url ='http://api.ezhihuo.com/apipy/rank/';
        $headers = array(
            "token:".$pty_token['success']['token'].""
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $re_success = json_decode($output,true);
        if ($res) {
            $this->success(__('Successful'),$re_success);
        } else {
            $this->error($this->project->getError());
        }
    }
    /**
     * 关键词详细曲线时间筛选
     */
    public function key_rankingcurve_time(){
        $pty_token = json_decode(Sms::pty_token(),true);
        if($pty_token['success']['token']==''){
            $this->success('token为空');
        }
        $token = $this->request->request('token');
        $start = $this->request->request('start');
        $over = $this->request->request('over');
        $antistop = $this->request->request('antistop');
        $user = \app\common\library\Token::get($token);
        $res = $this->project->alias('a')
            ->join('pml_order b', 'a.id=b.project_id')
            ->where('b.user_id="'.$user['user_id'].'" and b.keyword_name="'.$antistop.'"')
            ->field('a.id,b.keyword_name,b.channel_name,b.channel_id,a.create_time,a.address')
            ->select();
        $ret = json_decode(json_encode($res, true), true);
//        print_r($ret);die;
        $ditch = '';
        foreach($ret as $key=>&$val){
            if($val['channel_id']==1){
                $val['ditch']='bdpc';
            }else if($val['channel_id']==2) {
                $val['ditch'] = 'sgpc';
            }else if($val['channel_id']==3) {
                $val['ditch'] = 'bdios';
            }else if($val['channel_id']==4) {
                $val['ditch'] = 'sgios';
            }else if($val['channel_id']==5) {
                $val['ditch'] = 'qh360';
            }else if($val['channel_id']==6){
                $val['ditch'] = 'sm';
            }
            $ditch .=$val['ditch'].',';

        }
        $post_data = json_encode(array('code'=>520,'time'=>date('Ymd',time()),'data'=>array('start'=>$start,'over'=>$over,'keyword'=>$antistop,'project_id'=>$ret[0]['id'],'ditch'=>[substr($ditch,0,-1) ])));
        print_r($post_data);die;
        $url ='http://api.ezhihuo.com/apipy/starank/';
        $headers = array(
            "token:".$pty_token['success']['token'].""
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $re_success = json_decode($output,true);
        print_r($output);
    }
    /**
     * 关键词详细关键词效果
     */
    public function key_result(){
        $pty_token = json_decode(Sms::pty_token(),true);
        if($pty_token['success']['token']==''){
            $this->success('token为空');
        }
        $token = $this->request->request('token');
        $antistop = $this->request->request('antistop');
        $project_id = $this->request->request('project_id');
        $user = \app\common\library\Token::get($token);
        $res = $this->project->alias('a')
            ->join('pml_order b', 'a.id=b.project_id')
            ->where('b.user_id="'.$user['user_id'].'" and b.keyword_name="'.$antistop.'"and a.id = "'.$project_id.'"')
            ->field('b.id,b.project_num,b.taday_ranking,b.price,b.keyword_name,b.channel_name,b.channel_id,a.create_time,a.address')
            ->select();
       $ret = json_decode(json_encode($res,true));
        if ($res) {
            $this->success(__('Successful'),$ret);
        } else {
            $this->error($this->project->getError());
        }
    }
    /**
     *关键词列表
     */
    public function key_list(){
        $token = $this->request->request('token');
        $project_id = $this->request->request('project_id');
        $user = \app\common\library\Token::get($token);
        $key_list =  \think\Db::table('pml_order')
            ->where('user_id="'.$user['user_id'].'" and project_id="'.$project_id.'"')
            ->field('keyword_name,star_ranking,taday_ranking,reach_count,complete_status,price,create_time,channel_name')
            ->select();
        if ($key_list) {
            $this->success(__('Successful'),$key_list);
        } else {
            $this->error($this->project->getError());
        }
    }
    /**
     *关键词标题
     */
    public function key_titlelist(){
        $token = $this->request->request('token');
        $project_id = $this->request->request('project_id');
        $user = \app\common\library\Token::get($token);
        $arrays = array();
        $key_listtitle = \think\Db::table('pml_order')
            ->where('user_id="'.$user['user_id'].'" and project_id = "'.$project_id.'"')
            ->field('project_num,reach_count')
            ->select();

        $reach_count = '0';
        foreach ($key_listtitle as $key=>$val){
           $reach_count += $val['reach_count'];
        }
        $key_listtitles = $this->project->where('user_id="'.$user['user_id'].'" and id = "'.$project_id.'"')
            ->field('name,status,create_time,endtime,days,address')
            ->find();
        $staDate = date('Y-m-d', strtotime(date("Y-m-d")));
        $endDate= date('Y-m-d', strtotime("$staDate 0 month -1 day"));
        $zuori_money =  \think\Db::table('pml_consumption')
            ->where('user_id="'.$user['user_id'].'" and '."'".date(''.$endDate." 00:00:00", strtotime(date("Y-m-d")))."'".'< create_time and create_time < '."'".date(''.$endDate." 23:59:00", strtotime(date("Y-m-d")))."'")
            ->select();
        $zuori_xiaofei = '0';
        foreach($zuori_money as $key=>$val){
            $zuori_xiaofei += $val['money'];
        }
//
        $key_counts = \think\Db::table("pml_keyword")->where('user_id="'.$user['user_id'].'" and project_id = "'.$project_id.'"')->count();
        $key_listtitles['youhua']=$this->AC($key_listtitles['create_time']);
        $arrays['key_countname'] = $key_counts;//count($key_listtitle);
        $arrays['reach_count'] = $reach_count;
        $arrays['reach_rate'] = $reach_count/$key_listtitles['days'];
        $arrays['name']= $key_listtitles['name'];
        $arrays['status']=$key_listtitles['status'];
        $arrays['create_time']=$key_listtitles['create_time'];
        $arrays['endtime']=$key_listtitles['endtime'];
        $arrays['num']=$key_listtitle[0]['project_num'];
        $arrays['day']= $key_listtitles['days'];
        $arrays['youhua'] = $key_listtitles['youhua'];
        $arrays['zr_xiaofei'] = $zuori_xiaofei;
        $arrays['address']=$key_listtitles['address'];
        if ($arrays) {
            $this->success(__('Successful'),$arrays);
        } else {
            $this->error($this->project->getError());
        }

    }
    public function ceshi(){
        return '1';
    }
    public function refund_apply(){

    }
    /**
     *退款显示
     **/
    public function tklist(){
        $keyword_id = $this->request->request('id');
        $res = \think\Db::table('pml_order')->where('id="'.$keyword_id.'"')->field('project_num,keyword_name,channel_name')->find();
        $this->success('获取数据成功',$res);
    }
    /**
    *退款添加
     **/
    public function tuikuan(){
            $keyword_id = $this->request->request('id');
            $content = $this->request->request('content');
            $mobile = $this->request->request('mobile');
            $token = $this->request->request('token');
            $user =\app\common\library\Token::get($token);
            $users = $this->auth->getUser();
            $res = \think\Db::table('pml_order')->where('id="'.$keyword_id.'"')->find();
            $data = array(
                'user_id'=>$res['user_id'],
                'username'=>$users->username,
                'money'=>$res['money'],
                'status'=>0,
                'recharge_num'=> 'CA' . date('YmdHis', time()) . "_" . Random::alnum() . 'HGiW',
                'create_time'=>date('Y-m-d H:i:s',time()),
                'phone'=>$mobile,
                'key_name'=>$res['channel_name'],
                'content'=>$content,
                'project_id'=>$res['project_id'],
                'order_id'=>$keyword_id,
            );
            $res_one = \think\Db::table('pml_refund')->insert($data);
            if($res_one){
                $this->success(__('Success'),1);
            }

    }


}
