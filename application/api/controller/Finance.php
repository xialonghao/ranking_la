<?php
namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Validate;
use vendor\phpqrcode;
use think\Controller;
use think\Collection;
use app\api\controller\Order;
use app\admin\controller\Contraband;


/**
 * 项目接口
 */
class Finance extends Api
{

    protected $noNeedLogin = ['toks','voucher','ap','aps','chenggong','weixin','codeImg','wexinaps','extcel','spend'];
    protected $noNeedRight = '*';
    protected $project = null;

    public function _initialize()
    {
        session_start();
        $this->project = new \app\admin\model\Projcet;
        $this->worder = new \app\admin\model\Worder;
        parent::_initialize();
    }
    /**
     *获取支付token
     */
    public function Alipay(){
        $url ='http://api.ezhihuo.com/token/';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $message = json_decode($output,true);
        $_SESSION['code'] = $message['code'];
        print_r($message['success']['token']);
        if($message['code']==20000){
            $_SESSION['toks'] = $message['success']['token'];

        }else if($message['code']==40000){
            return $this->Alipay();
        }

    }
    /**
     *支付
     */
    public function voucher(){
        $pty_token = json_decode(Sms::pty_token(),true);
        if($pty_token['success']['token']==''){
            $this->success('token为空');
        }
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        $remarks = $this->request->request('remarks');
        $money = $this->request->request('money');
        $types = $this->request->request('type');

        if(!$remarks || !$money || !$types){
            $this->error(__('Invalid parameters'));
        }
        $Alipay_url ='http://api.ezhihuo.com/apialipy/ap/';

        $headers = array(
            "token:".$pty_token['success']['token'].""
        );
        $post_data = array();
        $post_data['out_trade_no']='RE'.date('YmdHis', time())."_".Random::alnum().'Vbj';
        $post_data['number']=$money;
        $post_data['app_notify_url']='http://pml.zhihuo.com.cn/api/Finance/ap/?us='.$user['user_id'].'';
        $post_data['return_url']='http://pml.zhihuo.com.cn/api/Finance/aps?us='.$user['user_id'].'';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$Alipay_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $message = json_decode($output,true);
        $this->success(__("Successful"),$message);die;

    }
    /**
     *支付成功后，跳转url
     */
    public function ap()
    {
        $post_data['total_amount']  = $this->request->request('total_amount');
        $post_data['out_trade_no']  = $this->request->request('out_trade_no');
        $post_data['timestamp']  = $this->request->request('timestamp');
        $post_data['us'] = $this->request->request('us');
        $ret = \think\Db::table("pml_user")->where('id="'.$post_data['us'].'"')->find();
        $res = $ret['money']+$post_data['total_amount'];
        $rest = \think\Db::table("pml_user")->where('id="'.$post_data['us'].'"')->update(['money'=>$res]);
        $data = array(
            'user_id'=>$post_data['us'],
            'username'=>$ret['username'],
            'money'=>$post_data['total_amount'],
            'status'=>1,
            'account_money'=>$ret['money'],
            'recharge_num'=>$post_data['out_trade_no'],
            'remarks'=>'在线充值',
            'type'=>'1',
//            'create_time'=>$post_data['timestamp'],
        );
        print_r($data);
        $res = \think\Db::table("pml_recharge")->insert($data);
        if($res){
            $this->success(__("Successful"),$res);
        }else{
            $this->error($this->worder->getError());
        }


    }
    /**
     *支付成功后，跳转url
     */
//    public function aps()
//    {
//            echo '成功';die;
//    }
    /**
     *充值记录
     */
    public function voucher_list(){
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        $res = \think\Db::table("pml_recharge")->where('user_id="'.$user['user_id'].'"')->select();
        if($res){
            $this->success(__("Successful"),$res);
        }else{
            $this->error($this->worder->getError());
        }

    }
    public function weixin(){
        $pty_token = json_decode(Sms::pty_token(),true);
        if($pty_token['success']['token']==''){
            $this->success('token为空');
        }
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        $remarks = $this->request->request('remarks');
        $money = $this->request->request('money');
        $types = $this->request->request('type');

        if(!$remarks || !$money || !$types){
            $this->error(__('Invalid parameters'));
        }
        $Alipay_url ='http://api.ezhihuo.com/apialipy/wx/';
        $headers = array(

            "token:".$pty_token['success']['token'].""
        );
        $post_data = array();
        $post_data['money']=$money;
        $post_data['nonce_str']='RE'.date('YmdHis', time())."_".Random::alnum().'Vbj';
        $post_data['product_id']='zhihuo';
        $post_data['out_trade_no']='RE'.date('YmdHis', time())."_".Random::alnum().'Vbj';
        $post_data['notify_url'] ='http://pml.zhihuo.com.cn/api/Finance/wexinaps?us='.$user['user_id'].'';
//        print_r(  $post_data['notify_url']);die;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$Alipay_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $message = json_decode($output,true);
//        print_r($message);die;
//        "Array (
//        [code] => 40001
//         [error] => Array ( [money] => Array ( [0] => 输入整数。 ) [nonce_str] => [product_id] => [out_trade_no] => [notify_url] => ) ) "
        //
        if($message['code']=='40001'){
                $res_two = $message['error']['money'][0];
            $this->success(__("Successful"),$res_two);
        }
        $dats = array(
            'dizhi'=>$message['success']['code_url'],
            'recharge_num'=>$post_data['out_trade_no'],
        );
////      $this->codeImg($message['success']['code_url']);
//        Vendor('phpqrcode.phpqrcode');
//        $errorCorrectionLevel =intval(3) ;//容错级别
//        $matrixPointSize = intval(4);//生成图片大小
//        //生成二维码图片
//        $object = new \QRcode();
//        $ss = $object->png($message['success']['code_url'], false, $errorCorrectionLevel, $matrixPointSize, 2);
        $this->success(__("Successful"),$dats);
//        echo '<img src="'.$ss.'">';
    }
//这是微信二维码
//    public function codeImg(){
//        $code = $this->request->request('code');
//        Vendor('phpqrcode.phpqrcode');
//        $errorCorrectionLevel =intval(3) ;//容错级别
//        $matrixPointSize = intval(4);//生成图片大小
//        //生成二维码图片
//        $object = new \QRcode();
//        $ss = $object->png($code, false, $errorCorrectionLevel, $matrixPointSize, 2);
//        echo '<img src="'.$ss.'">';
//    }
    public function wexinaps()
    {
        $notifiedData = file_get_contents('php://input');
        //XML格式转换
        $xmlObj = simplexml_load_string($notifiedData, 'SimpleXMLElement', LIBXML_NOCDATA);
        $xmlObj = json_decode(json_encode($xmlObj),true);
//        print_r($xmlObj);die;
//        // 当支付通知返回支付成功时
        if ($xmlObj['return_code'] == "SUCCESS" && $xmlObj['result_code'] == "SUCCESS") {
            foreach ($xmlObj as $k => $v) {

                if ($k == 'sign') {
                    $xmlSign = $xmlObj[$k];
                    unset($xmlObj[$k]);
                };
            }
            $sign = http_build_query($xmlObj);

            //md5处理
//            $sign = md5($sign . '&key=' . '你们公司的key');
//            //转大写
//            $sign = strtoupper($sign);
//            if ($sign === $xmlSign) {

//                // 总订单号
//                $trade_no = $xmlObj['out_trade_no'];
//                print_r($trade_no);die;
//
                $ret = \think\Db::table("pml_user")->where('id="' . $xmlObj['us'] . '"')->find();
            $total_fee = $xmlObj['total_fee']/100;
                $res = $ret['money'] +$total_fee;
                $rest = \think\Db::table("pml_user")->where('id="' . $xmlObj['us'] . '"')->update(['money' => $res]);

                $data = array(
                    'user_id' =>  $xmlObj['us'],
                    'username' => $ret['username'],
                    'money' =>  $xmlObj['total_fee'],
                    'status' => 1,
                    'account_money' => $ret['money'],
                    'recharge_num' =>  $xmlObj['out_trade_no'],
                    'remarks' => '在线充值',
                    'type' => '1',
                    'create_time' =>  $xmlObj['time_end'],
                );
                $res = \think\Db::table("pml_recharge")->insert($data);
                if($res){
//                    $this->chenggong($data['recharge_num']);
                    return "<xml>
                        <return_code><![CDATA[SUCCESS]]></return_code>
                        <return_msg><![CDATA[OK]]></return_msg>
                        </xml>";
                }

            }

    }
    /**
     *支付成功判断订单号有没有
     */
    public function chenggong(){
        $recharge_num = $this->request->request('recharge_num');
        $res = \think\Db::table("pml_recharge")->where('recharge_num="' .$recharge_num. '"')->count();
        if($res==1){
            $this->Success('已存在',1);
        }else{
            $this->Success('不存在',0);
        }
    }
    /**
     *账户余额
     */
    public function yue(){
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);

        $res = \think\Db::table("pml_user")->where('id="'.$user['user_id'].'"')->field('money')->find();
//        print_r($res);exit;
        if($res){
            $this->success(__("Successful"),$res);
        }else{
            $this->error($this->worder->getError());
        }
    }
    /**
     *提现
     */
    public function withdraw(){
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        //钱
        $money = $this->request->request('money');
        //状态
        $types = $this->request->request('type');
        //提现账号
        $username = $this->request->request('accout');
        //账户余额
        $account_money = $this->request->request('account_money');
        if(!$money || !$types || !$username){
            $this->error(__('Invalid parameters'));
        }
        if($money>$account_money){
            $this->error('金额超限');
        }
        $data = array(
            'user_id'=>$user['user_id'],
            'username'=>$username,
            'money'=>$money,
            'status'=>0,
            'account_money'=>$account_money,
            'recharge_num'=>'RE'.date('YmdHis', time())."_".Random::alnum().'Vbj',
            'type'=>$types,
        );
        $res = \think\Db::table("pml_cash")->insert($data);
        if($res){
            $this->success(__("Successful"),$res);
        }else{
            $this->error(__("无数据"));
        }
    }
    /**
     *提现列表
     */
    public function withdrwa_list(){
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        $res = \think\Db::table('pml_cash')->where('user_id="'.$user['user_id'].'"')->select();
//        print_r($res);die;
        if($res){
            $this->success(__("Successful"),$res);
        }else{
            $this->error(__("暂无数据"));
        }
    }
    /**
     *提现列表导出
     */
    public function withdrwa_excel(){

        $path = dirname(__FILE__); //找到当前脚本所在路径
        vendor('phpoffice.PHPExcel.PHPExcel'); //手动引入PHPExcel.php
        vendor('phpoffice.PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory'); //引入IOFactory.php 文件里面的PHPExcel_IOFactory这个
        $file_name = date('Y-m-d_His').'.xls';
        $PHPExcel = new \PHPExcel(); //实例化
        $id = $this->request->request('id');
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        //把他的记录全部插入excel
        $list=\think\Db::table('pml_cash')->where('user_id='.$user['user_id'].' and id in ('.$id.')')->select();
//        print_r($list);die;
//        $list=\think\Db::table('pml_recharge')->where('user_id="11" and id in (78,79,80)')->select();
//        print_r($list);die;
        $PHPSheet = $PHPExcel->getActiveSheet();
        $PHPSheet->setTitle("消费列表");
        $PHPSheet->setCellValue("A1","ID");
        $PHPSheet->setCellValue("B1","用户ID");
        $PHPSheet->setCellValue("C1","体现账号");
        $PHPSheet->setCellValue("D1","提现金额");
        $PHPSheet->setCellValue("E1","账户余额");
        $PHPSheet->setCellValue("F1","订单号");
        $PHPSheet->setCellValue("G1","创建时间");
        $PHPSheet->setCellValue("H1","手机号");
        $PHPSheet->setCellValue("I1","充值类型");

        $i = 2;
        foreach($list as $key => $value){
            $PHPSheet->setCellValue('A'.$i,''.$value['id']);
            $PHPSheet->setCellValue('B'.$i,''.$value['user_id']);
            $PHPSheet->setCellValue('C'.$i,''.$value['username']);
            $PHPSheet->setCellValue('D'.$i,''.$value['money']);
            $PHPSheet->setCellValue('E'.$i,''.$value['account_money']);
            $PHPSheet->setCellValue('F'.$i,''.$value['recharge_num']);
            $PHPSheet->setCellValue('G'.$i,''.$value['create_time']);
            $PHPSheet->setCellValue('H'.$i,''.$value['phone']);
            if($value['type']==1){
                $PHPSheet->setCellValue('I'.$i,''.'支付宝');
            }
            if($value['type']==2){
                $PHPSheet->setCellValue('I'.$i,''.'微信');
            }
            $i++;
        }
        $PHPWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, "Excel2007"); //创建生成的格式
        header('Content-Disposition: attachment;filename="消费记录.xlsx"'); //下载下来的表格名
        header('Content-Type: applicationnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件
    }
    /**
     *发票信息提交
     */
    public function invoice(){
        $users         = $this->auth->getUser();
        $token         = $this->request->request('token');
        $user          = \app\common\library\Token::get($token);
        $amount = $this->request->request('amount');
        $type          = $this->request->request('type');
        $company       = $this->request->request('company');
        $invoice_type  = $this->request->request('invoice_type');
        $address_id    = $this->request->request('address_id');
        $head          = $this->request->request('head');
        $author        = $this->request->request('author');
        $bank          = $this->request->request('bank');
        $bank_card     = $this->request->request('bank_card');
        $address       = $this->request->request('address');
        $mobile        = $this->request->request('mobile');
        if($type==0){
                $data = array(
                    'amount'=>$amount,
                    'type'=>$type,
                    'company'=>$company,
                    'head'=>$head,
                    'invoice_type'=>$invoice_type,
                    'address_id'=>$address_id,
                    'user_id'=>$user['user_id'],
                    'status'=>'0'
//                    'username'=>$users->username,
                );

                $res = \think\Db::table('pml_invoice')->insert($data);
                if($res){
                    $this->success(__("Successful"),$res);
                }else{
                    $this->error($this->worder->getError());
                }
        }
        if($type==1){
            $datas = array(
                'amount'=>$amount,
                'type'=>$type,
                'company'=>$company,
                'head'=>$head,
                'author'=>$author,
                'invoice_type'=>$invoice_type,
                'address_id'=>$address_id,
                'user_id'=>$user['user_id'],
//                'username'=>$users->username,
                'bank'=>$bank,
                'bank_card'=>$bank_card,
                'address'=>$address,
                'mobile'=>$mobile,
                'status'=>'0'
            );

            $rest = \think\Db::table('pml_invoice')->insert($datas);
            if($rest){
                $this->success(__("Successful"),$rest);
            }else{
                $this->error($this->worder->getError());
            }

        }
    }
    /**
     *发票信息
     */
    public function invoice_list(){
        $token         = $this->request->request('token');

        $user          = \app\common\library\Token::get($token);

        $res = \think\Db::table('pml_invoice')->where('user_id="'.$user['user_id'].'"')->field('id,create_time,head,amount,invoice_type,status')->select();

        if($res){
            $this->success(__("Successful"),$res);
        }else{
            $this->error($this->worder->getError());
        }
    }
    /**
     *发票详细信息
     */
    public function invoice_takelist(){
        $token         = $this->request->request('token');
        $user          = \app\common\library\Token::get($token);
        $id            = $this->request->request('id');
        if(!$id){
            $this->error(__('Invalid parameters'));
        }
        $res =  \think\Db::table('pml_invoice')->where('id="'.$id.'"')->find();
        if($res){
            $this->success(__("Successful"),$res);
        }else{
            $this->error($this->worder->getError());
        }
    }
    /**
     *第一步消费列表
     */
    public function consume(){
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        $pro = $this->project
            ->where('user_id="'.$user['user_id'].'" and status="1" and pay_status="1"')
            ->field('address,amount,num,name,id')->select();
        $pro_key=array();
        $key_num = '0';
        $zr_keynum = '0';
        foreach($pro as $key=>&$val){
            $staDate = date('Y-m-d', strtotime("-1 day"));
            $pro_key = \think\Db::table('pml_consumption')
                ->where('user_id="'.$user['user_id'].'" and order_sn="'.$val['num'].'"')
                ->field('money,create_time,order_id')
                ->order('create_time desc')
                ->select();
            foreach($pro_key as $key=>$vv){
                   if(strtotime(substr($vv['create_time'],0,11)) == strtotime($staDate)){
                       $zr_keynum+=$vv['money'];
                       $order_id = $vv['order_id'];
                   }
                $key_num += $vv['money'];
            }
              $val ['key_sum']=$key_num;
              $val ['key_zrsum']=$zr_keynum;
//              $val['order_id'] = $order_id;
              $key_num=0;
              $zr_keynum=0;
        }
//        print_r(json_decode(json_encode($pro,true),true));die;
        if($pro){
            $this->success(__("Successful"),$pro);
        }else{
            $this->error($this->worder->getError());
        }
    }
    /**
     *第二步消费列表
     */
    public function dier_consume(){
        $token = $this->request->request('token');
        $project_id = $this->request->request('project_id');
        $user = \app\common\library\Token::get($token);
        $rest = \think\Db::table('pml_order')
            ->where('user_id="'.$user['user_id'].'" and project_id="'.$project_id.'"')
            ->field('project_num,keyword_name,channel_name,price,id,taday_ranking')
            ->select();
        $key_num = 0;
        foreach($rest as $key=>&$val){
            $pro_key = \think\Db::table('pml_consumption')
                ->where('user_id="'.$user['user_id'].'" and order_id="'.$val['id'].'"')
                ->field('money,create_time,order_id')
                ->order('create_time desc')
                ->select();
            foreach($pro_key as $keys=>$vv){
                $key_num += $vv['money'];
            }
            $val ['key_sum']=$key_num;
        }

            $this->success(__("Successful"),$rest);

    }

    /*
         * 关键词详细消费
     */
    public function key_detailconsume(){
        $token = $this->request->request('token');
        $num = $this->request->request('num');
        $user          = \app\common\library\Token::get($token);
        $res = \think\Db::table('pml_consumption')
            ->where('user_id="'.$user['user_id'].'" and order_sn="'.$num.'"')
            ->select();
//        print_r($res);die;
        if($res){
            $this->success(__("Successful"),$res);
        }else{
            $this->error($this->worder->getError());
        }
    }
 /*
 * 退款记录
 */
 public function refund_list(){
     $token = $this->request->request('token');
     $user = \app\common\library\Token::get($token);
     $res = \think\Db::table('pml_refund')
         ->where('user_id="'.$user['user_id'].'"')
         ->select();
    if($res){
    $this->success(__("Successful"),$res);
    }else{
    $this->error($this->worder->getError());
    }
 }
/*
 * 消费记录导出
 */
    public function extcel(){

        $path = dirname(__FILE__); //找到当前脚本所在路径
        vendor('phpoffice.PHPExcel.PHPExcel'); //手动引入PHPExcel.php
        vendor('phpoffice.PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory'); //引入IOFactory.php 文件里面的PHPExcel_IOFactory这个
        $file_name = date('Y-m-d_His').'.xls';
        $PHPExcel = new \PHPExcel(); //实例化
        $id = $this->request->request('id');
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        //把他的记录全部插入excel
        $list=\think\Db::table('pml_recharge')->where('user_id='.$user['user_id'].' and id in ('.$id.')')->select();
//        $list=\think\Db::table('pml_recharge')->where('user_id="11" and id in (78,79,80)')->select();
//        print_r($list);die;
        $PHPSheet = $PHPExcel->getActiveSheet();
        $PHPSheet->setTitle("消费列表");
        $PHPSheet->setCellValue("A1","ID");
        $PHPSheet->setCellValue("B1","用户ID");
        $PHPSheet->setCellValue("C1","充值账号");
        $PHPSheet->setCellValue("D1","充值金额");
        $PHPSheet->setCellValue("E1","付款状态");
        $PHPSheet->setCellValue("F1","账户余额");
        $PHPSheet->setCellValue("G1","订单");
        $PHPSheet->setCellValue("H1","充值凭证");
        $PHPSheet->setCellValue("I1","充值类型");
        $PHPSheet->setCellValue("J1","备注");
        $PHPSheet->setCellValue("K1","操作人");
        $PHPSheet->setCellValue("L1","时间");
        $i = 2;
        foreach($list as $key => $value){
            $PHPSheet->setCellValue('A'.$i,''.$value['id']);
            $PHPSheet->setCellValue('B'.$i,''.$value['user_id']);
            $PHPSheet->setCellValue('C'.$i,''.$value['username']);
            $PHPSheet->setCellValue('D'.$i,''.$value['money']);
            $PHPSheet->setCellValue('E'.$i,''.$value['status']);
            $PHPSheet->setCellValue('F'.$i,''.$value['account_money']);
            $PHPSheet->setCellValue('G'.$i,''.$value['recharge_num']);
            $PHPSheet->setCellValue('H'.$i,''.$value['images']);
            $PHPSheet->setCellValue('I'.$i,''.$value['type']);
            $PHPSheet->setCellValue('J'.$i,''.$value['remarks']);
            $PHPSheet->setCellValue('K'.$i,''.$value['admin_id']);
            $PHPSheet->setCellValue('L'.$i,''.$value['create_time']);
            $i++;
        }
        $PHPWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, "Excel2007"); //创建生成的格式
        header('Content-Disposition: attachment;filename="消费记录.xlsx"'); //下载下来的表格名
        header('Content-Type: applicationnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件
    }

    /**
     *退款列表
     */
    public function tuikuan_extcel(){

        $path = dirname(__FILE__); //找到当前脚本所在路径
        vendor('phpoffice.PHPExcel.PHPExcel'); //手动引入PHPExcel.php
        vendor('phpoffice.PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory'); //引入IOFactory.php 文件里面的PHPExcel_IOFactory这个
        $file_name = date('Y-m-d_His').'.xls';
        $PHPExcel = new \PHPExcel(); //实例化
        $id = $this->request->request('id');
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        //把他的记录全部插入excel
        $list=\think\Db::table('pml_refund')->where('user_id='.$user['user_id'].' and id in ('.$id.')')->select();

        $PHPSheet = $PHPExcel->getActiveSheet();
        $PHPSheet->setTitle("退款列表");
        $PHPSheet->setCellValue("A1","ID");
        $PHPSheet->setCellValue("B1","流水编号");
        $PHPSheet->setCellValue("C1","时间");
        $PHPSheet->setCellValue("D1","退款金额");
        $PHPSheet->setCellValue("E1","账户金额");

        $i = 2;
        foreach($list as $key => $value){
            $PHPSheet->setCellValue('A'.$i,''.$value['id']);
            $PHPSheet->setCellValue('B'.$i,''.$value['recharge_num']);
            $PHPSheet->setCellValue('C'.$i,''.$value['create_time']);
            $PHPSheet->setCellValue('D'.$i,''.$value['money']);
            $PHPSheet->setCellValue('E'.$i,''.$value['account_money']);
            $i++;
        }
        $PHPWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, "Excel2007"); //创建生成的格式
        header('Content-Disposition: attachment;filename="消费记录.xlsx"'); //下载下来的表格名
        header('Content-Type: applicationnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件
    }
    /**
     *第一步消费列表导出
     */
    public function spend(){
        $token = $this->request->request('token');
        $id = $this->request->request('id');
        $user = \app\common\library\Token::get($token);
        $pro = $this->project
            ->where('user_id="12" and status="1" and pay_status="1" and id in (268,269)')
            ->field('address,amount,num,name,id')->select();
        $pro_key=array();
        $key_num = '0';
        $zr_keynum = '0';
        foreach($pro as $key=>&$val){
            $staDate = date('Y-m-d', strtotime("-1 day"));
            $pro_key = \think\Db::table('pml_consumption')
                ->where('user_id="12" and order_sn="'.$val['num'].'"')
                ->field('money,create_time,order_id')
                ->order('create_time desc')
                ->select();
            foreach($pro_key as $key=>$vv){
                if(strtotime(substr($vv['create_time'],0,11)) == strtotime($staDate)){
                    $zr_keynum+=$vv['money'];
                    $order_id = $vv['order_id'];
                }
                $key_num += $vv['money'];
            }
            $val ['key_sum']=$key_num;
            $val ['key_zrsum']=$zr_keynum;
//              $val['order_id'] = $order_id;
            $key_num=0;
            $zr_keynum=0;
        }

//        print_r(json_decode(json_encode($pro,true),true));die;
        $path = dirname(__FILE__); //找到当前脚本所在路径
        vendor('phpoffice.PHPExcel.PHPExcel'); //手动引入PHPExcel.php
        vendor('phpoffice.PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory'); //引入IOFactory.php 文件里面的PHPExcel_IOFactory这个
        $file_name = date('Y-m-d_His').'.xls';
        $PHPExcel = new \PHPExcel(); //实例化
        $id = $this->request->request('id');
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        //把他的记录全部插入excel

        $PHPSheet = $PHPExcel->getActiveSheet();
        $PHPSheet->setTitle("消费列表");
        $PHPSheet->setCellValue("A1","项目编号");
        $PHPSheet->setCellValue("B1","项目名称");
        $PHPSheet->setCellValue("C1","站点网址");
        $PHPSheet->setCellValue("D1","冻结金额");
        $PHPSheet->setCellValue("E1","昨日消费金额");
        $PHPSheet->setCellValue("F1","总消费金额");

        $i = 2;
        foreach($pro as $key => $value){

            $PHPSheet->setCellValue('A'.$i,''.$value['num']);
            $PHPSheet->setCellValue('B'.$i,''.$value['name']);
            $PHPSheet->setCellValue('C'.$i,''.$value['address']);
            $PHPSheet->setCellValue('D'.$i,''.$value['amount']);
            $PHPSheet->setCellValue('E'.$i,''.$value['key_zrsum']);
            $PHPSheet->setCellValue('F'.$i,''.$value['key_sum']);
            $i++;
        }
        $PHPWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, "Excel2007"); //创建生成的格式
        header('Content-Disposition: attachment;filename="消费记录.xlsx"'); //下载下来的表格名
        header('Content-Type: applicationnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件
    }
    /**
     *第二步消费列表导出
     */
    public function spend_two(){
        $token = $this->request->request('token');
        $project_id = $this->request->request('project_id');
        $id=$this->request->request('id');
        $user = \app\common\library\Token::get($token);
        $rest = \think\Db::table('pml_order')
            ->where('id in ('.$id.') and user_id="'.$user['user_id'].'" and project_id="'.$project_id.'"')
            ->field('project_num,keyword_name,channel_name,price,id,taday_ranking')
            ->select();

        $key_num = '0';
        foreach($rest as $key=>&$val){
            $pro_key = \think\Db::table('pml_consumption')
                ->where('user_id="'.$user['user_id'].'" and order_id="'.$val['id'].'"')
                ->field('money,create_time,order_id')
                ->order('create_time desc')
                ->select();
            foreach($pro_key as $key=>$vv){
                $key_num += $vv['money'];
            }
            $val ['key_sum']=$key_num;
        }
//                print_r(json_decode(json_encode($rest,true),true));die;
        $path = dirname(__FILE__); //找到当前脚本所在路径
        vendor('phpoffice.PHPExcel.PHPExcel'); //手动引入PHPExcel.php
        vendor('phpoffice.PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory'); //引入IOFactory.php 文件里面的PHPExcel_IOFactory这个
        $file_name = date('Y-m-d_His').'.xls';
        $PHPExcel = new \PHPExcel(); //实例化
        $id = $this->request->request('id');
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        //把他的记录全部插入excel

        $PHPSheet = $PHPExcel->getActiveSheet();
        $PHPSheet->setTitle("消费列表");
        $PHPSheet->setCellValue("A1","项目编号");
        $PHPSheet->setCellValue("B1","项目名称");
        $PHPSheet->setCellValue("C1","站点网址");
        $PHPSheet->setCellValue("D1","冻结金额");
        $PHPSheet->setCellValue("E1","昨日消费金额");
        $PHPSheet->setCellValue("E1","总消费金额");

        $i = 2;
        foreach($rest as $key => $value){
            $PHPSheet->setCellValue('A'.$i,''.$value['project_num']);
            $PHPSheet->setCellValue('B'.$i,''.$value['keyword_name']);
            $PHPSheet->setCellValue('C'.$i,''.$value['channel_name']);
            $PHPSheet->setCellValue('D'.$i,''.$value['taday_ranking']);
            $PHPSheet->setCellValue('D'.$i,''.$value['price']);
            $PHPSheet->setCellValue('E'.$i,''.$value['key_sum']);
            $i++;
        }
        $PHPWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, "Excel2007"); //创建生成的格式
        header('Content-Disposition: attachment;filename="消费记录.xlsx"'); //下载下来的表格名
        header('Content-Type: applicationnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件
    }
    /**
     *第三步消费列表导出
     */
    public function spend_thre(){
        $pty_token = json_decode(Sms::pty_token(),true);
        if($pty_token['success']['token']==''){
            $this->success('token为空');
        }
        $token = $this->request->request('token');
        $antistop = $this->request->request('antistop');
        $order_id = $this->request->request('orderid');
        $user = \app\common\library\Token::get($token);
        $res = $this->project->alias('a')
            ->join('pml_order b', 'a.id=b.project_id')
            ->where(' b.user_id="'.$user['user_id'].'" and b.keyword_name="'.$antistop.'" and b.id in('.$order_id.')')
            ->field('b.project_num,b.taday_ranking,b.price,b.keyword_name,b.channel_name,b.channel_id,a.create_time,a.address')
            ->select();
//        $ret = json_decode(json_encode($res,true));
//        print_r($ret);die;
        $path = dirname(__FILE__); //找到当前脚本所在路径
        vendor('phpoffice.PHPExcel.PHPExcel'); //手动引入PHPExcel.php
        vendor('phpoffice.PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory'); //引入IOFactory.php 文件里面的PHPExcel_IOFactory这个
        $file_name = date('Y-m-d_His').'.xls';
        $PHPExcel = new \PHPExcel(); //实例化
        $id = $this->request->request('id');
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        //把他的记录全部插入excel

        $PHPSheet = $PHPExcel->getActiveSheet();
        $PHPSheet->setTitle("关键词详情列表");
        $PHPSheet->setCellValue("A1","项目编号");
        $PHPSheet->setCellValue("B1","关键词");
        $PHPSheet->setCellValue("C1","渠道");
        $PHPSheet->setCellValue("D1","排名");
        $PHPSheet->setCellValue("E1","扣费");
        $PHPSheet->setCellValue("E1","检测时间");

        $i = 2;
        foreach($res as $key => $value){

            $PHPSheet->setCellValue('A'.$i,''.$value['project_num']);
            $PHPSheet->setCellValue('B'.$i,''.$value['keyword_name']);
            $PHPSheet->setCellValue('C'.$i,''.$value['channel_name']);
            $PHPSheet->setCellValue('D'.$i,''.$value['taday_ranking']);
            $PHPSheet->setCellValue('D'.$i,''.$value['price']);
            $PHPSheet->setCellValue('E'.$i,''.$value['create_time']);
            $i++;
        }
        $PHPWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, "Excel2007"); //创建生成的格式
        header('Content-Disposition: attachment;filename="消费记录.xlsx"'); //下载下来的表格名
        header('Content-Type: applicationnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件
    }
    /**
     *发表管理列表
     */
    public function invoice_show(){
        $token = $this->request->request('token');
        $user = \app\common\library\Token::get($token);
        $ivaoice_id = $this->request->request('ivaoice_id');
        $res = \think\Db::table('pml_invoice')->where('user_id="'.$user['user_id'].'" and id="'.$ivaoice_id.'"')->find();
        $rest = \think\Db::table('pml_invoice')->where('id="'.$res['id'].'" and user_id="'.$user['user_id'].'" and type="'.$res['type'].'"')->find();
        $rest['dizhi'] = \think\Db::table('pml_user_address')->where('id="'.$rest['address_id'].'"')->find();
        if($rest){
            $this->success(__("Successful"),$rest);
        }else{
            $this->error($this->worder->getError());
        }
    }

}
