<?php
// +----------------------------------------------------------------------
// | VitPHP
// +----------------------------------------------------------------------
// | 版权所有 2018~2021 藁城区创新网络电子商务中心 [ http://www.vitphp.cn ]
// +----------------------------------------------------------------------
// | VitPHP是一款免费开源软件,您可以访问http://www.vitphp.cn/以获得更多细节。
// +----------------------------------------------------------------------

namespace app\index\taglib;

use think\facade\Request;
use think\facade\View;
use think\template\TagLib;

class Form extends TagLib
{
    protected $mime_types = array(
        'gif' => 'image/gif',

        'jpg' => 'image/jpg',

        'jpeg' => 'image/jpeg',

        'jpe' => 'image/jpeg',

        'bmp' => 'image/bmp',

        'png' => 'image/png',

        'tif' => 'image/tiff',

        'tiff' => 'image/tiff',

        'pict' => 'image/x-pict',

        'pic' => 'image/x-pict',

        'pct' => 'image/x-pict',

        'psd' => 'image/x-photoshop',

        'swf' => 'application/x-shockwave-flash',

        'js' => 'application/x-javascript',

        'pdf' => 'application/pdf',

        'ps' => 'application/postscript',

        'eps' => 'application/postscript',

        'ai' => 'application/postscript',

        'wmf' => 'application/x-msmetafile',

        'css' => 'text/css',

        'htm' => 'text/html',

        'html' => 'text/html',

        'txt' => 'text/plain',

        'xml' => 'text/xml',

        'wml' => 'text/wml',

        'wbmp' => 'image/vnd.wap.wbmp',

        'mid' => 'audio/midi',

        'wav' => 'audio/wav',

        'mp3' => 'audio/mpeg',

        'mp2' => 'audio/mpeg',

        'avi' => 'video/x-msvideo',

        'mpeg' => 'video/mpeg',

        'mpg' => 'video/mpeg',

        'qt' => 'video/quicktime',

        'mov' => 'video/quicktime',

        'lha' => 'application/x-lha',

        'lzh' => 'application/x-lha',

        'z' => 'application/x-compress',

        'gtar' => 'application/x-gtar',

        'gz' => 'application/x-gzip',

        'gzip' => 'application/x-gzip',

        'tgz' => 'application/x-gzip',

        'tar' => 'application/x-tar',

        'bz2' => 'application/bzip2',

        'zip' => 'application/zip',

        'arj' => 'application/x-arj',

        'rar' => 'application/x-rar-compressed',

        'hqx' => 'application/mac-binhex40',
        'sit' => 'application/x-stuffit',
        'bin' => 'application/x-macbinary',
        'uu' => 'text/x-uuencode',
        'uue' => 'text/x-uuencode',
        'latex'=> 'application/x-latex',
        'ltx' => 'application/x-latex',
        'tcl' => 'application/x-tcl',
        'pgp' => 'application/pgp',
        'asc' => 'application/pgp',
        'exe' => 'application/x-msdownload',
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'mdb' => 'application/x-msaccess',
        'wri' => 'application/x-mswrite',
        'mp4' => 'audio/mp4,video/mp4,MPEG-4 Audio/Video',
    );

    protected $tags = [
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'image'      => ['attr' => 'name,type,value,api,min,session,storage_id', 'close' => 0],
        'music'      => ['attr' => 'name,value,api,session,storage_id', 'close' => 0],
        'video'      => ['attr' => 'name,value,api,session,storage_id', 'close' => 0],
        'upload'      => ['attr' => 'name,value,api,session,storage_id', 'close' => 0],
        'bimage'      => ['attr' => 'name,type,value,api', 'close' => 0],
        'icon' => ['attr' => 'name,type,value,api', 'close' => 0],
        'editor'=>['attr' => 'name,type,value,api', 'close' => 0],
        'time'=>['attr' => 'name,type,value,api,range', 'close' => 0],
        'color'=>['attr' => 'name,value', 'close' => 0],
        'radio'=> ['attr' => 'name,filter,type,data,value', 'close' => 0],
    ];

    public function tagColor($tag){
        $name = $tag['name'];
        $value = empty($tag['value']) ? '' : $tag['value'];
        if($value){
            $value = "{{$value}}";
        }
        $HTML = <<<EOT
<input type="text" value="$value" class="layui-input" id="color-$name" name="$name">
        <div class="upmenu">
            <div id="$name" style="margin-right: 0;"></div>
        </div>
EOT;
        $SCRIPT = <<<EOT
         <script> 
             layui.use('colorpicker', function(){
                var $ = layui.$
                    ,colorpicker = layui.colorpicker;
                //表单赋值
                colorpicker.render({
                    elem: '#$name'
                    ,color: '$value'
                    ,predefine: true
                    ,alpha: true
                    ,done: function(color){
                        $('#color-$name').val(color);
                        color || this.change(color);
                    }
                });
            });
        </script>
EOT;
        return $HTML.$SCRIPT;
    }

    public function tagRadio($tag){
        $name = empty($tag['name']) ? time().rand(1000,9999) : $tag['name'];
        $type = empty($tag['type']) ? 'radio' : $tag['type'];
        $filter = empty($tag['filter']) ? $name : $tag['filter'];
        $value = empty($tag['value']) ? '' : $tag['value'];
        $data = empty($tag['data']) ? '' : $tag['data'];
        if(is_string($data)){
            $data = str_replace("'","\"", $data);
            $result = json_decode($data,true);
            if(json_last_error()){
                $data = str_replace("=>",":", $data);
                if(substr($data,0,1) == '['){
                    $data = "{".substr($data,1,strlen($data)-2)."}";
                    $result = json_decode($data,true);
                }
            }
        }else{
            $result = $data;
        }
        $keyState = false;

        if(is_null($result)){
            return '<span style="color: red;">[radio]data数据格式错误</span>';
        }
        if(count($result) >= 1){
            $keyState = isset($result[0]);
        }
        // 结构数组
        $sut = [];
        foreach ($result as $i=>$v){
            if($keyState){
                $sut[] = ['key'=>$v,'value'=>$v];
            }else{
                // 对象格式下，key为唯一值
                $sut[] = ['key'=>$v,'value'=>$i];
            }
        }
        if($value){
            $value = "{{$value}}";
        }

        $list = '';
        if($type == 'radio') foreach ($sut as $d){
            $list.='<input type="radio" data-'.$filter.'="" name="'.$name.'" value="'.$d['value'].'" title="'.$d['key'].'" lay-filter="data-'.($filter).'">';
        }
        if($type == 'select') {
            $list = '';
            $list .= '<select name="'.$name.'" lay-filter="data-'.($filter).'">';
            foreach ($sut as $d){
                $list.='<option value="'.$d['value'].'" >'.$d['key'].'</option>';
            }
            $list.= '</select>';
        }
        if($type == 'checkbox') {
            foreach ($sut as $d){
                $list.= '<input lay-filter="data-'.($filter).'" type="checkbox" value="'.$d['value'].'" name="'.$name.'[]" title="'.$d['key'].'">';
            }
        }
        $HTML = <<<EOT
$list
EOT;
        if($type == 'radio'){
            $SCRIPT = <<<EOT
         <script> 
              layui.use(['jquery','form'], function(){
                let $ = layui.jquery;
                let form = layui.form;
                $("div[data-$filter]").css('display','none');
                $("div[data-$filter='$value']").css('display','block');
                
                var radioDefault_$filter = '$value';
                $('input[lay-filter="data-$filter"]').each(function(){
                    if($(this).val()==radioDefault_$filter){
                        $(this).attr('checked', 'checked');
                    }
                });
                form.render('radio');
                
               form.on('radio(data-$filter)', function(data){
                    let actValue =  data.value; //$(this).data('value');
                    $("div[data-$filter]").css('display','none');
                    $("div[data-$filter='" + actValue + "']").css('display','block');
                });
        
            });
        </script>
EOT;
        }
        if($type == 'select'){
            $SCRIPT = <<<EOT
         <script> 
              layui.use(['jquery','form'], function(){
                let $ = layui.jquery;
                let form = layui.form;
                
                // 设置默认项
                var selectDefault_$filter = '$value';
                var selectName_$filter = '$name';
                $('select[name="'+selectName_$filter+'"]').val(selectDefault_$filter);
                form.render('select');    
                
                $("div[data-$filter]").css('display','none');
                $("div[data-$filter='$value']").css('display','block');
                form.on('select(data-$filter)', function(data){
                    let actValue =  data.value; //$(this).data('value');
                    $("div[data-$filter]").css('display','none');
                    $("div[data-$filter=" + actValue + "]").css('display','block');
                });
        
            });
        </script>
EOT;
        }
        if($type == 'checkbox'){
            $SCRIPT = <<<EOT
         <script> 
              layui.use(['jquery','form'], function(){
                let $ = layui.jquery;
                let form = layui.form;
                var activeDefaultRange = '$value'.split(',');
                $("div[data-$filter]").css('display','none');
                $('input[lay-filter="data-$filter"]').each(function(){
                    var hashValue = activeDefaultRange.indexOf($(this).val());
                    if(hashValue>=0) $(this).attr('checked', 'checked');
                });
                $.each(activeDefaultRange, function(index, item){
                    $("div[data-$filter="+item+"]").css('display','block');
                });
                form.render('checkbox');
                
                form.on('checkbox(data-$filter)', function(data){
                    var checkActive = [];
                    $('input[lay-filter="data-$filter"]:checked').each(function() {
                        checkActive.push($(this).val());
                    });
                    $("div[data-$filter]").css('display','none');
                    $.each(checkActive, function(index, item){
                       $("div[data-$filter="+item+"]").css('display','block');
                    });
                });
            });
        </script>
EOT;
        }

        return $HTML.$SCRIPT;
    }
    /**
     * 时间选择器 type 对应日期格式
     * @param $tag
     * @return string
     */
    public function tagTime($tag){
        $value = empty($tag['value']) ? '' : $tag['value'];
        if($value){
            $value = "{{$value}}";
        }
        $name = $tag['name'];
        $type = $tag['type'];
        //日期范围
        $range = empty($tag['range']) ? true : false;

        $inputId = uniqid($name.'input');
        $HTML = <<<EOT
                <input type="text" class="layui-input" id="{$inputId}" placeholder=" - " value="{$value}" name="{$name}">

EOT;
        if($range){
            $SCRIPT = <<<EOT
         <script> 
              layui.use('laydate', function(){
                var laydate = layui.laydate;
        
                //日期时间范围
                laydate.render({
                    elem: '#{$inputId}'
                    ,type: '{$type}'
                    ,range:true
                });
        
        
            });
        </script>
EOT;
        }else{
            $SCRIPT = <<<EOT
         <script> 
              layui.use('laydate', function(){
                var laydate = layui.laydate;
        
                //日期时间范围
                laydate.render({
                    elem: '#{$inputId}'
                    ,type: '{$type}'
                    ,range:false
                });
        
        
            });
        </script>
EOT;
        }

        return $HTML.$SCRIPT;
    }

    /**
     * 富文本
     * @param $tag
     */
    public function tagEditor($tag){
        $value = empty($tag['value']) ? '' : $tag['value'];
        $name = $tag['name'];
        if($value){
            $value = "{{$value}}";
        }
        $storageId = $tag['storage_id'] ?? '';
        $session = $tag['session'] ?? '';

        $upList = $tag['uplist'] ?? 1;
        $upList = $upList === '' ? 1 : $upList;
        $upList = $upList ?: 0;
        $upList = $upList === 'false' ? 0 : $upList;

        $pid = input('pid', null);
        $baseApi = url("index/api.attachment/upload")->build();
        $api = empty($tag['api']) ? '' : $tag['api'];
//        $api = empty($tag['api']) ? url("index/api.attachment/upload")->build() . "?storage_id={$storageId}&session={$session}&pid={$pid}" : $tag['api'];
//        $api = urlencode($api);
        $uppage = url("index/common/upload")->build();
        $input_id = uniqid($name);
        $pre_id = uniqid($name.'pre');
        $HTML = <<<EOT
               <div id="{$name}"></div>
               <textarea name="{$name}" style="display:none">{$value}</textarea>
EOT;
        $SCRIPT = $upList ? $this->uploadEditorScriptForOpenContent($api, $uppage, $input_id, $pre_id, $name, $storageId, $session)
                : $this->uploadEditorScriptForApi($api, $uppage, $input_id, $pre_id, $name, $storageId, $session, $baseApi, $pid);
        return $HTML.$SCRIPT;
    }

    protected function uploadEditorScriptForOpenContent($api, $uppage, $input_id, $pre_id, $name, $storageId, $session)
    {
        return <<<EOT
         <script> 
             var api = '{$api}';
             var uppage = '{$uppage}';
             var input_id = '{$input_id}';
             var pre_id = '{$pre_id}';
             var storage_id = '{$storageId}';
             var session1 = '{$session}';
               //自定义富文本
            //setTimeout(function () {
                E = window.wangEditor;
                editor_{$name} = new E('#{$name}');
                editor_{$name}.config.zIndex = 500;
                editor_{$name}.config.showLinkImg = false;
                editor_{$name}.config.onchange = function (html) {
                    // 监控变化，同步更新到 textarea
                    $('[name="{$name}"]').val(html);
                }
                editor_{$name}.config.uploadImgFromMedia = function () {
                    wangEditorUpImg(editor_{$name});
                }

                editor_{$name}.create();
                editor_{$name}.txt.html( $('[name="{$name}"]').val() );
            //}, 100);      
        </script>
EOT;
    }

    protected function uploadEditorScriptForApi($api, $uppage, $input_id, $pre_id, $name, $storageId, $session, $baseApi,$pid)
    {
        return <<<EOT
         <script> 
         var baseApi = '{$baseApi}' + '&storage_id={$storageId}&session={$session}&pid={$pid}&db=0';
        var api = '{$api}' ? '{$api}&db=0' : baseApi;
//             var api = '{$api}';
             var uppage = '{$uppage}';
             var input_id = '{$input_id}';
             var pre_id = '{$pre_id}';
             var storage_id = '{$storageId}';
             var session1 = '{$session}';
               //自定义富文本
                E = window.wangEditor;
                editor_{$name} = new E('#{$name}');
                editor_{$name}.config.zIndex = 500;
                editor_{$name}.config.showLinkImg = false;
                editor_{$name}.config.uploadFileName = 'file';
                editor_{$name}.config.uploadImgServer = api;
                editor_{$name}.config.uploadImgHooks = {
                    customInsert: function(insertImgFn, result) {
                        if(result.code!=0){
                            layer.msg(result.msg || '上传失败');
                        }else{
                            insertImgFn(result.data[0].src)
                        }
                    }
                }
                editor_{$name}.config.onchange = function (html) {
                    // 监控变化，同步更新到 textarea
                    $('[name="{$name}"]').val(html);
                }
                
                editor_{$name}.create();
                editor_{$name}.txt.html( $('[name="{$name}"]').val() );
        </script>
EOT;
    }

    /**
     * 富文本
     * @param $tag
     */
    public function tagEditor__bak($tag){
        $value = empty($tag['value']) ? '' : $tag['value'];
        $name = $tag['name'];
        if($value){
            $value = "{{$value}}";
        }
        $api = empty($tag['api']) ? url("index/api.attachment/upload")->build() : $tag['api'];
        $api = urlencode($api);
        $uppage = url("index/common/upload")->build();
        $input_id = uniqid($name);
        $pre_id = uniqid($name.'pre');
        $HTML = <<<EOT
               <textarea name="{$name}" >{$value}</textarea>
EOT;
        $SCRIPT = <<<EOT
         <script> 
             var api = '{$api}';
             var uppage = '{$uppage}';
             var input_id = '{$input_id}';
             var pre_id = '{$pre_id}';
               //自定义富文本
            setTimeout(function () {
                editor = window.createEditor('[name="{$name}"]');
                editor.on( 'change', function( event ) {
                    var custom_content = this.getData();//获取内容
                    $('[name="{$name}"]').val(custom_content);
                });
        
            }, 100);        
        </script>
EOT;
        return $HTML.$SCRIPT;
    }

    /**
     * 选择图标
     * @param $tag
     * @return string
     */
    public function tagIcon($tag){

        $value = empty($tag['value']) ? '' : $tag['value'];
        if($value){
            $value = "{{$value}}";

        }
        $name = $tag['name'];
        $iconUrl =  url("index/common/icon")->build();
        $checkId = uniqid($name.'check');
        $inputId =  uniqid($name.'input');
        $HTML = <<<EOT
              <span style="text-align: center;line-height: 38px" class="layui-input up-input checkmenu" id="{$checkId}"> 
                  <i class="{$value}"></i> 
                  <input type="hidden" name="{$name}" value="" class="input-icon" id="{$inputId}"> 
              </span> 
              <a class="upmenu" onclick="checkicon()"><button class="layui-btn layui-btn-primary" type="button">选择图标</button></a>

EOT;
        $SCRIPT = <<<EOT
         <script> 
             $('#{$inputId}').val('{$value}');
             function checkicon(){ 
                layer.open({ 
                   type: 2, 
                   title: '选择图标', 
                   shadeClose: true, 
                   shade: 0.8, 
                   maxmin: true, 
                   area: ['80%', '90%'], 
                   content: '{$iconUrl}?checkId={$checkId}&inputId={$inputId}' 
                }); 
               } 
        </script>
EOT;
        return $HTML.$SCRIPT;

    }

    /**
     * 这是一个闭合标签的简单演示
     */
    public function tagImage($tag)
    {
        $min = isset($tag['min']) ? $tag['min'] : 'true';
        $type = empty($tag['type']) ? 'one' : $tag['type'];
        $upList = $tag['uplist'] ?? true;
        $upList = $upList === '' ? true : $upList;
        $upList = $upList ?: false;
        $upList = $upList === 'false' ? false : $upList;
        if (!in_array($type,['one','multi'])){
            throw new \InvalidArgumentException('Form:image标签的type必须是 one 或 multi');
        }
        $value = empty($tag['value']) ? '' : $tag['value'];

        $storageId = $tag['storage_id'] ?? '';
        if($storageId && mb_substr($storageId, 0, 1) === '$'){
            $storageId = str_replace('"',"\\\"",$storageId);
            $storageId = "{{$storageId}}";
        }

        $session = $tag['session'] ?? '';
        if(mb_substr($session, 0, 1) === '$'){
            $session = str_replace('"',"\\\"",$session);
            $session = "{{$session}}";
        }

        $pid = input('pid', null);
        //$api = empty($tag['api']) ? url("index/api.attachment/upload")->build() . "?storage_id={$storageId}&session={$session}&pid={$pid}" : $tag['api'];
        $baseApi = url("index/api.attachment/upload")->build();
        $api = empty($tag['api']) ? '' : $tag['api'];
        $uppage = url("index/common/upload")->build();

        $value = str_replace('"',"\\\"",$value);
        $inputvalue = "";
        if($value){
            $inputvalue = "{{$value}}";
            $value = "{{$value}}";
        }

        $name = $tag['name'];
        //type 为 multi 为批量选择
        if($type == 'multi'){
            $input_id = redom(32);
            $btn_id = redom(31);
            $pre_id = redom(30);
        }else{
            $input_id = uniqid($name);
            $btn_id = uniqid($name.'upbtn');
            $pre_id = uniqid($name.'pre');
        }

        $HTML = <<<EOT
                        <input type="text" name="{$name}" value="{$inputvalue}" id="{$input_id}" data-imgid="{$pre_id}" placeholder="请上传图片" autocomplete="off" class="layui-input up-input">
                        <a class="upmenu"><button type="button" class="layui-btn layui-btn-primary" id="{$btn_id}">上传图片</button></a>
                        {if 'multi'=='$type'}
                        {else}
                        <div style="position: relative;display: inline-block">
                            <div id="close-{$input_id}" class="" style="position: absolute;top: 0px;right:0px;padding: 5px;"><i class="viticon viticon vit-icon-cuo title-icon text-red" style="font-size: 24px;"></i></div>
                            <img class="padding data-tips-image" id="{$pre_id}" style="max-width: 150px;max-height: 150px;" src="{$value}" alt="img">
                        </div>
                        {/if}
                       
EOT;
        $SCRIPT = $upList ? $this->uploadImageScriptForOpenContent($pid, $type, $value, $input_id, $pre_id, $btn_id, $name, $uppage, $api, $min, $baseApi, $storageId, $session)
                :$this->uploadImageScriptForApi($pid, $type, $value, $input_id, $pre_id, $btn_id, $name, $uppage, $api, $min, $baseApi, $storageId, $session);
        return $HTML.$SCRIPT;
    }

    // 上传图片的时候返回的js为弹窗页面
    protected function uploadImageScriptForOpenContent($pid, $type, $value, $input_id, $pre_id, $btn_id, $name, $uppage, $api, $min, $baseApi, $storageId, $session)
    {
        return <<<EOT
<script>
var type = '{$type}';
layui.use(['layer', 'jquery'], function(){ //独立版的layer无需执行这一句
    var $ = layui.jquery;
    var imgSrc = '{$value}' || '/static/theme/images/img404.png';
    var baseApi = '{$baseApi}' + '?storage_id={$storageId}&session={$session}&pid={$pid}';
    var api = '{$api}' ? '{$api}' : baseApi;
    api = encodeURIComponent(api);

    $('#close-{$input_id}').click(function(){
        $("#{$pre_id}").attr('src', '');
        $("#{$input_id}").val('');
    });
    $('#{$input_id}').change(function(){
        $("#{$pre_id}").attr('src', $(this).val());
    });
    $("#{$pre_id}").attr('src', imgSrc);
	var $ = layui.jquery, layer = layui.layer; //独立版的layer无需执行这一句
	    $('#{$btn_id}').on('click', function(){
	    layer.open({
	      type: 2 //此处以iframe举例
	      ,title: '上传图片'
	      ,area:['90%', '600px']
	      ,shade: 0
	      ,maxmin: true
	      ,btn: ['确认', '取消']
	      ,content: '{$uppage}?input_id={$input_id}&pre_id={$pre_id}&api='+api+'&type={$type}&min={$min}&session={$session}&storage_id={$storageId}'
	      ,yes: function(index, layero){
	           //type 为 multi 为批量选择
	          if(type == 'multi'){
	               $(".upmenu").nextAll().remove();
	               var inputSrc = '';
	               //下方缩略图
	               var domImg = "";
	            
	               layer.getChildFrame('body', index).find('.grid-demo').find('.active').each(function(index,elm) {
	                    var inputsrc = $(this).attr('data-inputsrc');
	                    var imgsrc = $(this).find('img').attr('src');
	                    var dataid = $(this).attr('data-id');
	                    var dataname = $(this).attr('data-name');
	                    dataname = dataname.substring(0,dataname.lastIndexOf('.'));
	                   inputSrc += inputsrc+',';
	                   domImg+="<div style=\"float: left;\"><img class=\"padding\"  style=\"max-height: 108px;max-width: 108px;\" src='"+imgsrc+"' alt=\"img\">" +
	                    "<input class=\"layui-input\" style=\"width: 108px;\" type=\"text\" name=\"name_{$name}\" value='"+dataname+"'></div>"; 
	                });
	                
	               $(".upmenu").after(domImg);
	               //去掉最后一个逗号(如果不需要去掉，就不用写)
                    if (inputSrc.length > 0) {
                        inputSrc = inputSrc.substr(0,inputSrc.length - 1);
                    }
                    $("#{$input_id}").attr('readonly',true);
	               $("#{$input_id}").val(inputSrc);
	                layer.close(index);
	          }else{
	                var res = window["layui-layer-iframe" + index].callbackdata();
					if (res.imgsrc == undefined) {
                        layer.msg('请选择图片');
                        return false
                    }
					console.log(res);
					layer.close(index);
					$('#$pre_id').attr('src', res.imgsrc);
					$("#{$input_id}").val(res.imgInputSrc);
					// $("#{$input_id}").val(res.imgsrc)
	          }
					
	      }
	      ,btn2: function(){
	      }
	      ,zIndex: layer.zIndex //重点1
	      ,success: function(layero, index){
	      }
	      ,end: function(){
	        //更新索引
	      }
	    });
	  });
	});
</script>
EOT;
    }

    // 上传图片的时候返回的js为api调用
    protected function uploadImageScriptForApi($pid, $type, $value, $input_id, $pre_id, $btn_id, $name, $uppage, $api, $min, $baseApi, $storageId, $session)
    {
        $imgtype = getSetting('imgtype','setup');
        $imgtype = explode(',', $imgtype);
        $imgAcceptMime = [];
        foreach ($imgtype as $type){
            if(isset($this->mime_types[$type])){
                $mime = $this->mime_types[$type] ?? '';
                if(!in_array($mime, $imgAcceptMime)){
                    $imgAcceptMime[] = $mime;
                }
            }
        }
        $imgAcceptMime = implode(',', $imgAcceptMime);
        return <<<EOT
<script>
layui.use(['layer','upload'], function(){ //独立版的layer无需执行这一句
	  var $ = layui.jquery, upload=layui.upload, layer = layui.layer; //独立版的layer无需执行这一句
      var index_{$input_id};
	  var imgSrc = '{$value}' || '/static/theme/images/img404.png';
	  var baseApi = '{$baseApi}' + '&storage_id={$storageId}&session={$session}&pid={$pid}&db=0';
    var api = '{$api}' ? '{$api}&db=0' : baseApi;

	  upload.render({
			elem: '#{$btn_id}',
			url: api,
            accept: 'file',
            acceptMime: '{$imgAcceptMime}',
            data:{
			     min: '{$min}'
            },
            field: 'f',
            choose:function(obj){
            },
            progress: function (n, ele) {
                $('#layer_msg_index_{$input_id}').html('上传中' + n + '%');
            },
			before: function(obj) {
			    layer.load(1);
                layer.msg('<span id="layer_msg_index_{$input_id}">加载中</span>', { icon: 16,shade: 0.01,time:0 });
			},
			done: function(res) {
				$('#{$input_id}').val(res.data[0].src);
                layer.closeAll('loading'); 
				layer.msg(res.msg || '上传成功', {icon:6, time: 1200});
				$('#$pre_id').attr('src', res.data[0].src);
			},
            allDone: function(obj){ //当文件全部被提交后，才触发
				index_{$input_id} && layer.close(index_{$input_id});
			    $(".layui-upload-file").val("")
            },
            error: function(index, upload){
                layer.closeAll('loading'); 
            }
		});
	});
</script>
EOT;
    }

    /**
     * 这是一个闭合标签的简单演示
     */
    public function tagUpload($tag)
    {
        $value = empty($tag['value']) ? '' : $tag['value'];
        $type = empty($tag['type']) ? '4' : $tag['type'];
        $pid = input('pid');
        //$api = empty($tag['api']) ? url("index/api.attachment/upload", ['type'=>$type])->build() : $tag['api'];

        $upList = $tag['uplist'] ?? 1;
        $upList = $upList === '' ? 1 : $upList;
        $upList = $upList ?: 0;
        $upList = $upList === 'false' ? 0 : $upList;

        $suffix = $tag['suffix'] ?? 'zip';
        $suffix = explode(',', $suffix);
        $mimeTypes = [];
        foreach($suffix as $s){
            if(isset($this->mime_types[$s])){
                array_push($mimeTypes, $this->mime_types[$s]);
            }
        }
        $suffix = implode('|', $suffix);
        $mimeTypes = $mimeTypes ? implode(',', $mimeTypes) : 'application/zip';

        $baseApi = url("index/api.attachment/upload", ['type'=>$type])->build();
        $api = empty($tag['api']) ? '' : $tag['api'];

        $storageId = $tag['storage_id'] ?? '';
        if($storageId && mb_substr($storageId, 0, 1) === '$'){
            $storageId = str_replace('"',"\\\"",$storageId);
            $storageId = "{{$storageId}}";
        }

        $session = $tag['session'] ?? '';
        if(mb_substr($session, 0, 1) === '$'){
            $session = str_replace('"',"\\\"",$session);
            $session = "{{$session}}";
        }

        $btnText = $tag['btnTitle'] ?? '上传文件';
        $uppage = url("index/common/upload")->build();

        $value = str_replace('"',"\\\"",$value);
        $inputvalue = "";
        if($value){
            $inputvalue = "{{$value}}";
            $value = media($value);
        }

        $name = $tag['name'];

        $input_id = uniqid($name);
        $btn_id = uniqid($name.'upbtn');
        $pre_id = uniqid($name.'pre');

        $HTML = <<<EOT
                        <input type="text" name="{$name}" value="{$inputvalue}" id="{$input_id}" data-imgid="{$pre_id}" placeholder="请{$btnText}" autocomplete="off" class="layui-input up-input">
                        <a class="upmenu"><button type="button" class="layui-btn layui-btn-primary" id="{$btn_id}">{$btnText}</button></a>
                       
EOT;

        $SCRIPT = <<<EOT
<script>
layui.use(['layer','upload'], function(){ //独立版的layer无需执行这一句
    var $ = layui.jquery, upload=layui.upload, layer = layui.layer; //独立版的layer无需执行这一句
    var index_{$input_id};
	$('#{$input_id}').val('$inputvalue');
	  
    if({$upList}){
        var baseApi = '{$baseApi}' + '&storage_id={$storageId}&session={$session}&pid={$pid}';
        var api = '{$api}' ? '{$api}' : baseApi;    
    }else{
        var baseApi = '{$baseApi}' + '&storage_id={$storageId}&session={$session}&pid={$pid}&db=0';
        var api = '{$api}' ? '{$api}&db=0' : baseApi;    
    }  

	  upload.render({
			elem: '#{$btn_id}',
			url: api,
            accept: 'file',
            acceptMime: '{$mimeTypes}',
            exts: '{$suffix}',
            data:{},
            field: 'f',
            choose:function(obj){
            },
            progress: function (n, ele) {
                $('#layer_msg_index_{$input_id}').html('上传中' + n + '%');
            },
			before: function(obj) {
			    layer.load(1);
                layer.msg('<span id="layer_msg_index_{$input_id}">加载中</span>', { icon: 16,shade: 0.01,time:0 });
			},
			done: function(res) {
				$('#{$input_id}').val(res.data[0].src);
                layer.closeAll('loading'); 
				layer.msg(res.msg || '上传成功', {icon:6, time: 1200});
			},
            allDone: function(obj){ //当文件全部被提交后，才触发
				index_{$input_id} && layer.close(index_{$input_id});
			    $(".layui-upload-file").val("")
            },
            error: function(index, upload){
                layer.closeAll('loading'); 
            }
		});
	});
</script>
EOT;

        return $HTML.$SCRIPT;
    }
    /**
     * 上传音乐
     */
    public function tagMusic($tag)
    {
        $type = empty($tag['type']) ? 'one' : $tag['type'];
        if (!in_array($type,['one','multi'])){
            throw new \InvalidArgumentException('Form:image标签的type必须是 one 或 multi');
        }
        $value = empty($tag['value']) ? '' : $tag['value'];

        $upList = $tag['uplist'] ?? true;
        $upList = $upList === '' ? true : $upList;
        $upList = $upList ?: false;
        $upList = $upList === 'false' ? false : $upList;

        $storageId = $tag['storage_id'] ?? '';
        if(mb_substr($storageId, 0, 1) === '$'){
            $storageId = str_replace('"',"\\\"",$storageId);
            $storageId = "{{$storageId}}";
        }

        $session = $tag['session'] ?? '';
        if(mb_substr($session, 0, 1) === '$'){
            $session = str_replace('"',"\\\"",$session);
            $session = "{{$session}}";
        }

        $pid = input('pid', null);
//        $api = empty($tag['api']) ? url("index/api.attachment/upload")->build() . "?storage_id={$storageId}&session={$session}&pid={$pid}" : $tag['api'];
//        $api = urlencode($api);
        $baseApi = url("index/api.attachment/upload")->build();
        $api = empty($tag['api']) ? '' : $tag['api'];
        $uppage = url("index/common/music")->build();

        $value = str_replace('"',"\\\"",$value);
        $inputvalue = "";
        if($value){
            $inputvalue = "{{$value}}";
            $value = "{{$value}}";
        }

        $name = $tag['name'];
        //type 为 multi 为批量选择
        if($type == 'multi'){

            $input_id = redom(32);
            $btn_id = redom(31);
            $pre_id = redom(30);

        }else{
            $input_id = uniqid($name);
            $btn_id = uniqid($name.'upbtn');
            $pre_id = uniqid($name.'pre');
        }

        $HTML = <<<EOT
            <input type="text" name="{$name}" value="{$inputvalue}" id="{$input_id}" data-imgid="{$pre_id}" placeholder="请上传音乐" autocomplete="off" class="layui-input up-input">
            <a class="bofang" id="play_{$input_id}"><i type="button" class="viticon vit-icon-bofang padding-lr" style="line-height: 38px;"></i></a>
            <a class="upmenu"><button type="button" class="layui-btn layui-btn-primary" id="{$btn_id}">上传音乐</button></a>
            <audio src="" id="audio_{$input_id}" style="display: none"></audio>
EOT;

        $SCRIPT = $upList ? $this->uploadMusicScriptForOpenContent($pid, $type, $value, $input_id, $pre_id, $btn_id, $name, $uppage, $api, $baseApi, $storageId, $session)
                    : $this->uploadMusicScriptForOpenApi($pid, $type, $value, $input_id, $pre_id, $btn_id, $name, $uppage, $api, $baseApi, $storageId, $session);
        return $HTML.$SCRIPT;
    }

    protected function uploadMusicScriptForOpenContent($pid, $type, $value, $input_id, $pre_id, $btn_id, $name, $uppage, $api, $baseApi, $storageId, $session)
    {
        return  <<<EOT
<script>
var type = '{$type}';
layui.use(['layer', 'jquery'], function(){ //独立版的layer无需执行这一句
    var $ = layui.jquery, layer = layui.layer;
    var imgSrc = '{$value}' || '/static/theme/images/img404.png';
    var baseApi = '{$baseApi}' + '?storage_id={$storageId}&session={$session}&pid={$pid}';
    var api = '{$api}' ? '{$api}' : baseApi;
    api = encodeURIComponent(api);
    var audioPay = $('#audio_{$input_id}');
    var audioHandle = $('#play_{$input_id}');
    
    $("#{$pre_id}").attr('src', imgSrc);
    audioPay[0].addEventListener('ended', function(){
        audioHandle.children('i').removeClass('vit-icon-zanting').addClass('vit-icon-bofang');
    });
    audioHandle.click(function(){
        var musicUrl = $('#{$input_id}').val();
        if(!musicUrl) return;
//             var musicUrl =  '/upload/1/music/2021/09/05/34/d2cbde71866dc82446abb6810f46745b773310.mp3';
        if(!audioPay.attr('src')){
            audioPay.attr('src', musicUrl);
        }
        var isPay = audioHandle.children('i').hasClass('vit-icon-zanting');
        if(isPay){
            audioHandle.children('i').removeClass('vit-icon-zanting').addClass('vit-icon-bofang');
            audioPay[0].pause();
        }else{
            audioHandle.children('i').removeClass('vit-icon-bofang').addClass('vit-icon-zanting');
            audioPay[0].play();
        }
    });
	
	    $('#{$btn_id}').on('click', function(){
	    layer.open({
	      type: 2 //此处以iframe举例
	      ,title: '上传音乐'
	      ,area:['90%', '600px']
	      ,shade: 0
	      ,maxmin: true
	      ,btn: ['确认', '取消']
	      ,content: '{$uppage}?input_id={$input_id}&pre_id={$pre_id}&api='+api+'&type={$type}&session={$session}&storage_id={$storageId}'
	      ,yes: function(index, layero){
               layer.close(index);
	      }
	      ,btn2: function(){
	      }
	      ,zIndex: layer.zIndex //重点1
	      ,success: function(layero, index){
	      }
	      ,end: function(){
	        //更新索引
	      }
	    });
	  });
	});
</script>
EOT;
    }

    protected function uploadMusicScriptForOpenApi($pid, $type, $value, $input_id, $pre_id, $btn_id, $name, $uppage, $api, $baseApi, $storageId, $session)
    {
        $musicType = getSetting('mp3type','setup');
        $musicType = explode(',', $musicType);
        $musicAcceptMime = [];
        foreach ($musicType as $type){
            if(isset($this->mime_types[$type])){
                $mime = $this->mime_types[$type] ?? '';
                if(!in_array($mime, $musicAcceptMime)){
                    $musicAcceptMime[] = $mime;
                }
            }
        }
        $musicAcceptMime = implode(',', $musicAcceptMime);
        return <<<EOT
<script>
layui.use(['layer','upload'], function(){ //独立版的layer无需执行这一句
	  var $ = layui.jquery, upload=layui.upload, layer = layui.layer; //独立版的layer无需执行这一句
      var index_{$input_id};
	  var imgSrc = '{$value}' || '/static/theme/images/img404.png';
	  var baseApi = '{$baseApi}' + '&storage_id={$storageId}&session={$session}&pid={$pid}&db=0';
      var api = '{$api}' ? '{$api}&db=0' : baseApi;
      var audioPay = $('#audio_{$input_id}');
    var audioHandle = $('#play_{$input_id}');
    
    $("#{$pre_id}").attr('src', imgSrc);
    audioPay[0].addEventListener('ended', function(){
        audioHandle.children('i').removeClass('vit-icon-zanting').addClass('vit-icon-bofang');
    });
    audioHandle.click(function(){
        var musicUrl = $('#{$input_id}').val();
        if(!musicUrl) return;
//             var musicUrl =  '/upload/1/music/2021/09/05/34/d2cbde71866dc82446abb6810f46745b773310.mp3';
        if(!audioPay.attr('src')){
            audioPay.attr('src', musicUrl);
        }
        var isPay = audioHandle.children('i').hasClass('vit-icon-zanting');
        if(isPay){
            audioHandle.children('i').removeClass('vit-icon-zanting').addClass('vit-icon-bofang');
            audioPay[0].pause();
        }else{
            audioHandle.children('i').removeClass('vit-icon-bofang').addClass('vit-icon-zanting');
            audioPay[0].play();
        }
    });

	  upload.render({
			elem: '#{$btn_id}',
			url: api,
            accept: 'file',
            acceptMime: '{$musicAcceptMime}',
            data:{
			     type: 2
            },
            field: 'f',
            choose:function(obj){
            },
            progress: function (n, ele) {
                $('#layer_msg_index_{$input_id}').html('上传中' + n + '%');
            },
			before: function(obj) {
			    layer.load(1);
                layer.msg('<span id="layer_msg_index_{$input_id}">加载中</span>', { icon: 16,shade: 0.01,time:0 });
			},
			done: function(res) {
				$('#{$input_id}').val(res.data[0].src);
                layer.closeAll('loading'); 
				layer.msg(res.msg || '上传成功', {icon:6, time: 1200});
				$('#$pre_id').attr('src', res.data[0].src);
			},
            allDone: function(obj){ //当文件全部被提交后，才触发
				index_{$input_id} && layer.close(index_{$input_id});
			    $(".layui-upload-file").val("")
            },
            error: function(index, upload){
                layer.closeAll('loading'); 
            }
		});
	});
</script>
EOT;
    }

    /**
     * 上传视频
     */
    public function tagVideo($tag)
    {
        $type = empty($tag['type']) ? 'one' : $tag['type'];
        if (!in_array($type,['one','multi'])){
            throw new \InvalidArgumentException('Form:image标签的type必须是 one 或 multi');
        }
        $value = empty($tag['value']) ? '' : $tag['value'];
        $upList = $tag['uplist'] ?? true;
        $upList = $upList === '' ? true : $upList;
        $upList = $upList ?: false;
        $upList = $upList === 'false' ? false : $upList;

        $storageId = $tag['storage_id'] ?? '';
        if(mb_substr($storageId, 0, 1) === '$'){
            $storageId = str_replace('"',"\\\"",$storageId);
            $storageId = "{{$storageId}}";
        }

        $session = $tag['session'] ?? '';
        if(mb_substr($session, 0, 1) === '$'){
            $session = str_replace('"',"\\\"",$session);
            $session = "{{$session}}";
        }

        $pid = input('pid', null);
        // $api = empty($tag['api']) ? url("index/api.attachment/upload")->build() . "?storage_id={$storageId}&session={$session}&pid={$pid}" : $tag['api'];
        $baseApi = url("index/api.attachment/upload")->build();
        $api = empty($tag['api']) ? '' : $tag['api'];
        $uppage = url("index/common/video")->build();

        $value = str_replace('"',"\\\"",$value);
        $inputvalue = "";
        if($value){
            $inputvalue = "{{$value}}";
            $value = "{{$value}}";
        }

        $name = $tag['name'];
        //type 为 multi 为批量选择
        if($type == 'multi'){

            $input_id = redom(32);
            $btn_id = redom(31);
            $pre_id = redom(30);

        }else{
            $input_id = uniqid($name);
            $btn_id = uniqid($name.'upbtn');
            $pre_id = uniqid($name.'pre');
        }

        $HTML = <<<EOT
            <input type="text" name="{$name}" value="{$inputvalue}" id="{$input_id}" data-imgid="{$pre_id}" placeholder="请上传视频" autocomplete="off" class="layui-input up-input">
            <a class="bofang" id="play_{$input_id}"><i type="button" class="viticon vit-icon-bofang padding-lr" style="line-height: 38px;"></i></a>
            <a class="upmenu"><button type="button" class="layui-btn layui-btn-primary" id="{$btn_id}">上传视频</button></a>
EOT;

        $SCRIPT = $upList ? $this->uploadVideoScriptForOpenContent($pid, $type, $value, $input_id, $pre_id, $btn_id, $name, $uppage, $api, $baseApi, $storageId, $session)
                : $this->uploadVideoScriptForOpenApi($pid, $type, $value, $input_id, $pre_id, $btn_id, $name, $uppage, $api, $baseApi, $storageId, $session);

        return $HTML.$SCRIPT;
    }

    protected function uploadVideoScriptForOpenContent($pid, $type, $value, $input_id, $pre_id, $btn_id, $name, $uppage, $api, $baseApi, $storageId, $session)
    {
        return <<<EOT
<script>
var type = '{$type}';
layui.use(['layer', 'jquery'], function(){ //独立版的layer无需执行这一句
    var $ = layui.jquery, layer = layui.layer;
    var imgSrc = '{$value}' || '/static/theme/images/img404.png';
    var baseApi = '{$baseApi}' + '?storage_id={$storageId}&session={$session}&pid={$pid}';
    var api = '{$api}' ? '{$api}' : baseApi;
    api = encodeURIComponent(api);
    
    $('#play_{$input_id}').click(function(){
        var videoUrl = $('#{$input_id}').val();
        if(!videoUrl) return;
        layer.open({
            type: 2,
            title: false,
            area: ['630px', '360px'],
            shade: 0.8,
            closeBtn: 0,
            shadeClose: true,
            content: videoUrl
        });
    });
    
    $("#{$pre_id}").attr('src', imgSrc);	
	$('#{$btn_id}').on('click', function(){
	    layer.open({
	      type: 2 //此处以iframe举例
	      ,title: '上传视频'
	      ,area:['90%', '600px']
	      ,shade: 0
	      ,maxmin: true
	      ,btn: ['确认', '取消']
	      ,content: '{$uppage}?input_id={$input_id}&pre_id={$pre_id}&api='+api+'&type={$type}&session={$session}&storage_id={$storageId}'
	      ,yes: function(index, layero){
	          layer.close(index);
	      }
	      ,btn2: function(){
	      }
	      ,zIndex: layer.zIndex //重点1
	      ,success: function(layero, index){
	      }
	      ,end: function(){
	        //更新索引
	      }
	    });
	  });
	});
</script>
EOT;
    }

    protected function uploadVideoScriptForOpenApi($pid, $type, $value, $input_id, $pre_id, $btn_id, $name, $uppage, $api, $baseApi, $storageId, $session)
    {
        $videoType = getSetting('mp4type','setup');
        $videoType = explode(',', $videoType);
        $videoAcceptMime = [];
        foreach ($videoType as $type){
            if(isset($this->mime_types[$type])){
                $mime = $this->mime_types[$type] ?? '';
                if(!in_array($mime, $videoAcceptMime)){
                    $videoAcceptMime[] = $mime;
                }
            }
        }
        $videoAcceptMime = implode(',', $videoAcceptMime);
        return <<<EOT
<script>
layui.use(['layer','upload'], function(){ //独立版的layer无需执行这一句
	  var $ = layui.jquery, upload=layui.upload, layer = layui.layer; //独立版的layer无需执行这一句
      var index_{$input_id};
	  var imgSrc = '{$value}' || '/static/theme/images/img404.png';
	  var baseApi = '{$baseApi}' + '&storage_id={$storageId}&session={$session}&pid={$pid}&db=0';
      var api = '{$api}' ? '{$api}&db=0' : baseApi;

    $('#play_{$input_id}').click(function(){
        var videoUrl = $('#{$input_id}').val();
        if(!videoUrl) return;
        layer.open({
            type: 2,
            title: false,
            area: ['630px', '360px'],
            shade: 0.8,
            closeBtn: 0,
            shadeClose: true,
            content: videoUrl
        });
    });
    
	  upload.render({
			elem: '#{$btn_id}',
			url: api,
            accept: 'file',
            acceptMime: '{$videoAcceptMime}',
            data:{
			     type: 3
            },
            field: 'f',
            choose:function(obj){
            },
            progress: function (n, ele) {
                $('#layer_msg_index_{$input_id}').html('上传中' + n + '%');
            },
			before: function(obj) {
			    layer.load(1);
                layer.msg('<span id="layer_msg_index_{$input_id}">加载中</span>', { icon: 16,shade: 0.01,time:0 });
			},
			done: function(res) {
				$('#{$input_id}').val(res.data[0].src);
                layer.closeAll('loading'); 
				layer.msg(res.msg || '上传成功', {icon:6, time: 1200});
				$('#$pre_id').attr('src', res.data[0].src);
			},
            allDone: function(obj){ //当文件全部被提交后，才触发
				index_{$input_id} && layer.close(index_{$input_id});
			    $(".layui-upload-file").val("")
            },
            error: function(index, upload){
                layer.closeAll('loading'); 
            }
		});
	});
</script>
EOT;
    }
}