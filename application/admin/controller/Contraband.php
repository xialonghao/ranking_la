<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use fast\Http;

/**
 * 违禁词管理
 *
 * @icon fa fa-circle-o
 */
class Contraband extends Backend
{
    
    /**
     * Contraband模型对象
     * @var \app\admin\model\Contraband
     */
    protected $model = null;

    protected  $host = "http://47.102.127.12:8000/api/word/";
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Contraband;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index()
    {
   
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }

 
            $data = json_decode(Http::get($this->host),true);

            $total = count($data);
            foreach ($data as $key=>$val){
                $data[$key]['id'] = $val['pk'];
            }
            $result = array("total" => $total, "rows" => $data);

            return json($result);
        }
        return $this->view->fetch();
    }
    
    /**
     * 添加违禁词
     */
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try {
                    $res = json_decode(Http::post($this->host,['word'=>$params['code']]),true);
                    if (!empty($res['pk'])) {
                        $this->success();
                    } else {
                        $this->error(__($res['word']['0']));
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
    
    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $res = Http::sendRequest($this->host.$ids."/", '', 'DELETE');
            if($res['ret']){
                $this->success(__("Operation completed"));
            }else{
                $this->error(__("Operation failed"));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
    
    /**
     * 检测违禁词
     */
    public function checkword($wordkey=""){
        $url = "http://47.102.127.12:8000/api/examine/";
        $res = json_decode(Http::post($url,['wordkey'=>$wordkey]),true);
        if($res['code'] == 1 && $res['result'] == 1){
            $result = true;
        }else{
            $result = false;
        }
        return $result;
    }
    
}
