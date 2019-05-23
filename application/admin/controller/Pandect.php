<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 总览
 *
 * @icon fa fa-circle-o
 */
class Pandect extends Backend
{

    /**
     * Worder模型对象
     * @var \app\admin\model\Worder
     */
    protected $model = null;

    public function _initialize()
    {
        $this->project = new \app\admin\model\Projcet;
        $this->model = new \app\admin\model\Worder;
        parent::_initialize();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    public function index(){
        $execute_project =$this->project->where('status="1" and pay_status="1"')->count();
        //即将到期时间
        $newtime = date('Y-m-d',strtotime("-7 day")).' 23:59:59';
        $newstime = date('Y-m-d',time()).' 23:59:59';
        $expire_project = $this->project->select();

        $i='0';
        foreach($expire_project as $key=>$val){
            if(date('Y-m-d',strtotime($val['endtime'])-7 * 24 * 3600).' 23:59:00' <= date('Y-m-d',time()).' 00:00:00'){
                $i++;
            }
        }
        //待处理工单
        $pending_worder = $this->project->where('status=1')->count();
        $pending_invoice=\think\Db::table('pml_invoice')->where('status=1')->count();
        return json(array(
            'code'=>1,
            'msg'=>'成功',
            'data'=>array('execute_project'=>$execute_project,'expire_project'=>$i,'pending_worder'=>$pending_worder,'pending_invoice'=>$pending_invoice),
        ));
    }
    public function channel(){
        $project=\think\Db::table('pml_order')->count();
        $project_baidu = \think\Db::table('pml_order')->where('channel_name="百度PC"')->count();
        $project_bdyd = \think\Db::table('pml_order')->where('channel_name="百度移动"')->count();
        $project_sougou = \think\Db::table('pml_order')->where('channel_name="搜狗"')->count();
        $project_sgyd = \think\Db::table('pml_order')->where('channel_name="搜狗移动"')->count();
        $project_snaliuling= \think\Db::table('pml_order')->where('channel_name="360"')->count();
        $project_shenma = \think\Db::table('pml_order')->where('channel_name="神马"')->count();
        $baidu = $project_baidu/$project;
        $baiduyidong = $project_bdyd/$project;
        $sougou = $project_sougou/$project;
        $sougouyidong = $project_sgyd/$project;
        $sanliuling = $project_snaliuling/$project;
        $shenma =    $project_shenma/$project;
        $percentages  = array(
            'baidu'=>$baidu,
            'baiduyidong'=>$baiduyidong,
            'sougou'=>$sougou,
            'sougouyidong'=>$sougouyidong,
            'sanliuling'=>$sanliuling,
            'shenma'=>$shenma,

        );
        return json(array(
            'code'=>1,
            'msg'=>'成功',
            'data'=>$percentages ,
        ));
    }
    //本月消费曲线
    public function month_consumption(){
        //关键词总数
//        $key_sumcount =\think\Db::table('pml_consumption')->count();
        //本月
        $staDate=date('Y-m-01', strtotime(date("Y-m-d")));
        $endDate= date('Y-m-d', strtotime("$staDate +1 month -1 day"));

        for($i=1;$i<=substr($endDate,8);$i++){

            $wher_count[$i] = \think\Db::table('pml_consumption')
                ->where("'".date('Y-m-'.$i." 00:00:00", strtotime(date("Y-m-d")))."'".'< create_time and create_time < '."'".date('Y-m-'.$i." 23:59:00", strtotime(date("Y-m-d")))."'")
                ->select();
            $counts[$i] = \think\Db::table('pml_order')
                ->where("'".date('Y-m-'.$i." 00:00:00", strtotime(date("Y-m-d")))."'".'< create_time and create_time < '."'".date('Y-m-'.$i." 23:59:00", strtotime(date("Y-m-d")))."'")
                ->select();
            echo "<pre>";
            $num=count($wher_count[$i]);
            $countes=count($counts[$i]);
            $wher_count[$i]['xiaofei']=  $num/$countes;

        }
        $this->success(__('Successful'),$wher_count);
    }
    //筛选消费曲线
    public function sx_consumption(){
        $staDates = $this->request->request('staDate');
        $endDates = $this->request->request('endDate');

//        $staDate=date('Y-m-01', strtotime(date("Y-m-d")));
        $endDate= date('Y-m-d', strtotime("$staDates +1 month -1 day"));

        for($i=1;$i<=substr($endDate,8);$i++){

            $wher_count[$i] = \think\Db::table('pml_consumption')
                ->where("'".date('Y-m-'.$i." 00:00:00", strtotime(date("Y-m-d")))."'".'< create_time and create_time < '."'".date('Y-m-'.$i." 23:59:00", strtotime(date("Y-m-d")))."'")
                ->select();
            $counts[$i] = \think\Db::table('pml_order')
                ->where("'".date('Y-m-'.$i." 00:00:00", strtotime(date("Y-m-d")))."'".'< create_time and create_time < '."'".date('Y-m-'.$i." 23:59:00", strtotime(date("Y-m-d")))."'")
                ->select();
            echo "<pre>";
            $num=count($wher_count[$i]);
            $countes=count($counts[$i]);
            $wher_count[$i]['xiaofei']=  $num/$countes;

        }
        $this->success(__('Successful'),$wher_count);
    }
    public function user_count(){
        $staDate=date('Y-m-01', strtotime(date("Y-m-d")));
        $endDate= date('Y-m-d', strtotime("$staDate +1 month -1 day"));
        print_r($endDate);die;
    }


}
