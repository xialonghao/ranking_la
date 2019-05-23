<?php if (!defined('THINK_PATH')) exit(); /*a:4:{s:82:"F:\phpStudys\PHPTutorial\WWW\ranking\public/../application/admin\view\xlh\add.html";i:1552017845;s:79:"F:\phpStudys\PHPTutorial\WWW\ranking\application\admin\view\layout\default.html";i:1551866736;s:76:"F:\phpStudys\PHPTutorial\WWW\ranking\application\admin\view\common\meta.html";i:1551866736;s:78:"F:\phpStudys\PHPTutorial\WWW\ranking\application\admin\view\common\script.html";i:1551866736;}*/ ?>
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
                                <form id="add-form" class="form-horizontal" role="form" data-toggle="validator" method="POST" action="">

    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Imge'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-imge" class="form-control" name="row[imge]" type="text">
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Content'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <textarea id="c-content" class="form-control editor" rows="5" name="row[content]" cols="50"></textarea>
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Status'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-status" class="form-control" name="row[status]" type="number">
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Data'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            
            <div class="radio">
            <?php if(is_array($dataList) || $dataList instanceof \think\Collection || $dataList instanceof \think\Paginator): if( count($dataList)==0 ) : echo "" ;else: foreach($dataList as $key=>$vo): ?>
            <label for="row[data]-<?php echo $key; ?>"><input id="row[data]-<?php echo $key; ?>" name="row[data]" type="radio" value="<?php echo $key; ?>" <?php if(in_array(($key), explode(',',"hobbydata"))): ?>checked<?php endif; ?> /> <?php echo $vo; ?></label> 
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </div>

        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('As_ids'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-as_ids" data-rule="required" data-source="as/index" data-multiple="true" class="form-control selectpage" name="row[as_ids]" type="text" value="">
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Attachfile'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <div class="input-group">
                <input id="c-attachfile" class="form-control" size="50" name="row[attachfile]" type="text">
                <div class="input-group-addon no-border no-padding">
                    <span><button type="button" id="plupload-attachfile" class="btn btn-danger plupload" data-input-id="c-attachfile" data-multiple="false" data-preview-id="p-attachfile"><i class="fa fa-upload"></i> <?php echo __('Upload'); ?></button></span>
                    <span><button type="button" id="fachoose-attachfile" class="btn btn-primary fachoose" data-input-id="c-attachfile" data-multiple="false"><i class="fa fa-list"></i> <?php echo __('Choose'); ?></button></span>
                </div>
                <span class="msg-box n-right" for="c-attachfile"></span>
            </div>
            <ul class="row list-inline plupload-preview" id="p-attachfile"></ul>
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