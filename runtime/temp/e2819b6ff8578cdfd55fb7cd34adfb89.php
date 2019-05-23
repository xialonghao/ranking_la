<?php if (!defined('THINK_PATH')) exit(); /*a:4:{s:84:"/www/wwwroot/pml_zhihuo_com_cn/pml/public/../application/admin/view/worder/edit.html";i:1552038988;s:77:"/www/wwwroot/pml_zhihuo_com_cn/pml/application/admin/view/layout/default.html";i:1552038988;s:74:"/www/wwwroot/pml_zhihuo_com_cn/pml/application/admin/view/common/meta.html";i:1552038988;s:76:"/www/wwwroot/pml_zhihuo_com_cn/pml/application/admin/view/common/script.html";i:1552038988;}*/ ?>
<!DOCTYPE html>
<html lang="<?php echo $config['language']; ?>">
    <head>
        <meta charset="utf-8">
<title><?php echo (isset($title) && ($title !== '')?$title:''); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="renderer" content="webkit">

<link rel="shortcut icon" href="<?php echo $site['image']; ?>" />
<!-- Loading Bootstrap -->
<link href="/assets/css/backend<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.css?v=<?php echo \think\Config::get('site.version'); ?>" rel="stylesheet">

<!-- HTML5 shim, for IE6-8 support of HTML5 elements. All other JS at the end of file. -->
<!--[if lt IE 9]>
  <script src="/assets/js/html5shiv.js"></script>
  <script src="/assets/js/respond.min.js"></script>
<![endif]-->
<script type="text/javascript">
    var require = {
        config:  <?php echo json_encode($config); ?>
    };
</script>
    </head>

    <body class="inside-header inside-aside <?php echo defined('IS_DIALOG') && IS_DIALOG ? 'is-dialog' : ''; ?>">
        <div id="main" role="main">
            <div class="tab-content tab-addtabs">
                <div id="content">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <section class="content-header hide">
                                <h1>
                                    <?php echo __('Dashboard'); ?>
                                    <small><?php echo __('Control panel'); ?></small>
                                </h1>
                            </section>
                            <?php if(!IS_DIALOG && !$config['fastadmin']['multiplenav']): ?>
                            <!-- RIBBON -->
                            <div id="ribbon">
                                <ol class="breadcrumb pull-left">
                                    <li><a href="dashboard" class="addtabsit"><i class="fa fa-dashboard"></i> <?php echo __('Dashboard'); ?></a></li>
                                </ol>
                                <ol class="breadcrumb pull-right">
                                    <?php foreach($breadcrumb as $vo): ?>
                                    <li><a href="javascript:;" data-url="<?php echo $vo['url']; ?>"><?php echo $vo['title']; ?></a></li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                            <!-- END RIBBON -->
                            <?php endif; ?>
                            <div class="content">
                                <form id="edit-form" class="form-horizontal" role="form" data-toggle="validator" method="POST" action="">

    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Worder_sn'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-worder_sn" class="form-control" name="row[worder_sn]" type="text" value="<?php echo $row['worder_sn']; ?>">
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Title'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-title" class="form-control" name="row[title]" type="text" value="<?php echo $row['title']; ?>">
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Type'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-type" class="form-control" name="row[type]" type="number" value="<?php echo $row['type']; ?>">
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Record_sn'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-record_sn" class="form-control" name="row[record_sn]" type="text" value="<?php echo $row['record_sn']; ?>">
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Contect'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <textarea id="c-contect" class="form-control editor" rows="5" name="row[contect]" cols="50"><?php echo $row['contect']; ?></textarea>
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Mobile'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-mobile" class="form-control" name="row[mobile]" type="text" value="<?php echo $row['mobile']; ?>">
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('E_mail'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-e_mail" class="form-control" name="row[e_mail]" type="text" value="<?php echo $row['e_mail']; ?>">
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Images'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <div class="input-group">
                <input id="c-images" class="form-control" size="50" name="row[images]" type="text" value="<?php echo $row['images']; ?>">
                <div class="input-group-addon no-border no-padding">
                    <span><button type="button" id="plupload-images" class="btn btn-danger plupload" data-input-id="c-images" data-mimetype="image/gif,image/jpeg,image/png,image/jpg,image/bmp" data-multiple="true" data-preview-id="p-images"><i class="fa fa-upload"></i> <?php echo __('Upload'); ?></button></span>
                    <span><button type="button" id="fachoose-images" class="btn btn-primary fachoose" data-input-id="c-images" data-mimetype="image/*" data-multiple="true"><i class="fa fa-list"></i> <?php echo __('Choose'); ?></button></span>
                </div>
                <span class="msg-box n-right" for="c-images"></span>
            </div>
            <ul class="row list-inline plupload-preview" id="p-images"></ul>
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Admin_name'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-admin_name" class="form-control" name="row[admin_name]" type="text" value="<?php echo $row['admin_name']; ?>">
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Status'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            
            <div class="radio">
            <?php if(is_array($statusList) || $statusList instanceof \think\Collection || $statusList instanceof \think\Paginator): if( count($statusList)==0 ) : echo "" ;else: foreach($statusList as $key=>$vo): ?>
            <label for="row[status]-<?php echo $key; ?>"><input id="row[status]-<?php echo $key; ?>" name="row[status]" type="radio" value="<?php echo $key; ?>" <?php if(in_array(($key), is_array($row['status'])?$row['status']:explode(',',$row['status']))): ?>checked<?php endif; ?> /> <?php echo $vo; ?></label> 
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </div>

        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Create_time'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-create_time" class="form-control datetimepicker" data-date-format="YYYY-MM-DD HH:mm:ss" data-use-current="true" name="row[create_time]" type="text" value="<?php echo datetime($row['create_time']); ?>">
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('User_id'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-user_id" data-rule="required" data-source="user/user/index" data-field="nickname" class="form-control selectpage" name="row[user_id]" type="text" value="<?php echo $row['user_id']; ?>">
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Username'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-username" class="form-control" name="row[username]" type="text" value="<?php echo $row['username']; ?>">
        </div>
    </div>
    <div class="form-group layer-footer">
        <label class="control-label col-xs-12 col-sm-2"></label>
        <div class="col-xs-12 col-sm-8">
            <button type="submit" class="btn btn-success btn-embossed disabled"><?php echo __('OK'); ?></button>
            <button type="reset" class="btn btn-default btn-embossed"><?php echo __('Reset'); ?></button>
        </div>
    </div>
</form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="/assets/js/require<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.js" data-main="/assets/js/require-backend<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.js?v=<?php echo $site['version']; ?>"></script>
    </body>
</html>