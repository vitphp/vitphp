//菜单加载
$(function () {
    menuload();
});
function menuload(){
    var _iframe_src_ = sessionStorage.getItem("_iframe_src_");
    $("#docDemoMenu1").find("li").each(function (i,item) {
        var src = $(this).attr("lay-url");
        if(src == _iframe_src_){
            $(this).addClass("layui-menu-item-checked").siblings().removeClass("layui-menu-item-checked");
        }

    })
}
// 监听菜单栏点击事件
$("#docDemoMenu1").find('li').click(function (){
    location.href = this.getAttribute('lay-url');
    saveStorage(this.getAttribute('lay-url'));
});
function saveStorage(re) {
    sessionStorage.setItem("_iframe_src_", re);
}

//隐藏显示菜单
function vit_minmenu(){
    var positionLeft = $(".min-menu").position().left;
    if(positionLeft == -200){
        $(".min-menu").animate({left: "0"});
        $("#vit-body-menu").animate({"margin-left":"200px"});
        $("#vitionClass").removeClass("vit-icon-zhankai").addClass("vit-icon-shouqi");
        // $("#vit-body-menu .vit_minmenu").animate({left: "453px"});
    }else{
        $(".min-menu").animate({left: "-200px"});
        $("#vit-body-menu").animate({"margin-left":"0px"});
        $("#vitionClass").removeClass("vit-icon-shouqi").addClass("vit-icon-zhankai");
        // $("#vit-body-menu .vit_minmenu").animate({left: "253px"});
    }
}
//全屏
function fullScreen(id) {
    var el = document.documentElement;
    var rfs = el.requestFullScreen || el.webkitRequestFullScreen || el.mozRequestFullScreen || el.msRequestFullscreen;
    if(typeof rfs != "undefined" && rfs) {
        rfs.call(el);
    }else{
        getMsg("浏览器不支持全屏调用，请使用其他浏览器或按F11键切换全屏！");
        return;
    }

    $("#"+id).attr("onclick","exitScreen('"+id+"')");
    $("#"+id).find("i").attr("class","viticon vit-icon-tuichuquanping");
}
//退出全屏
function exitScreen(id) {
    if(document.exitFullscreen) {
        document.exitFullscreen();
    } else if(document.mozCancelFullScreen) {
        document.mozCancelFullScreen();
    } else if(document.webkitCancelFullScreen) {
        document.webkitCancelFullScreen();
    } else if(document.msExitFullscreen) {
        document.msExitFullscreen();
    }
    if(typeof cfs != "undefined" && cfs) {
        cfs.call(el);
    }

    $("#"+id).find("i").attr("class","viticon vit-icon-quanping");
    $("#"+id).attr("onclick","fullScreen('"+id+"')");
}

function shuaxin(){
    $("#vit-layout-fluid").load(location.href+" #vit-layout-fluid");
}

/**
 * ajax 全局方法
 * @param url
 * @param type
 * @param param
 * @param callback
 */
function commonAjax(url,type,param,callback) {
    $.ajax({
        url:url,
        type: type,
        dataType: 'json',
        data:param,
        success: function(data){
            callback(data);
        },
        error: function(xhr, type){
            console.log("错误日志"+xhr,type);
        }
    });
}
/**
 * ajax 请求
 * @param type
 * @param param
 * @param returnUrl 请求返回地址
 */
function actionAjax(url,type,param,returnUrl) {

    // 加载菜单一的数据
    $.ajax({
        url:url,
        type: type,
        dataType: 'json',
        data:{
            "param":param
        },
        success: function(data){
            console.log(data);
            if(data.code == 200){
                layer.msg(data.msg);
                setTimeout(function () {
                    if(returnUrl){
                        location.href = returnUrl;
                    }else{
                        location.reload();
                    }
                },1500)

            }else{
                layer.msg(data.msg);
                setTimeout(function () {
                    if(returnUrl){
                        location.href = returnUrl;
                    }else{
                        location.reload();
                    }
                },1500)

            }
        },
        error: function(xhr, type){
            console.log("错误日志"+xhr,type);
        }
    });

}

/**
 * 富文本上传图片
 * @param editor
 */
function editorUpImg(editor, min = 'true') {

    console.log('-------------------------');
    layui.use('layer', function () {
        var $ = layui.jquery, layer = layui.layer; //独立版的layer无需执行这一句
        layer.open({
            type: 2 //此处以iframe举例
            , title: '上传图片'
            , area: ['90%', '600px']
            , shade: 0
            , maxmin: true
            , content: uppage + '?input_id=' + input_id + '&pre_id=' + pre_id + '&api=' + api + "&type=multi&min=" + min
            , btn: ['确认', '取消']
            , yes: function (index, layero) {
                // var res = window["layui-layer-iframe" + index].callbackdata();
                // if (res.imgsrc == undefined) {
                //     layer.msg('请选择图片');
                //     return false
                // }
                var domImg = "";
                layer.getChildFrame('body', index).find('.grid-demo').find('.active').each(function (index, elm) {
                    var inputsrc = $(this).attr('data-inputsrc');
                    var imgsrc = $(this).find('img').attr('src');
                    var dataid = $(this).attr('data-id');
                    // var dataname = $(this).attr('data-name');
                    // if(!dataname){
                    //     dataname = '';
                    // }
                    // dataname = dataname.substring(0,dataname.lastIndexOf('.'));
                    // inputSrc += inputsrc+',';
                    domImg += "<img style=\"max-width:100%;border:0\" alt=\"\" src='" + imgsrc + "'>";

                });

                var imag = CKEDITOR.dom.element.createFromHtml("<span>" + domImg + "</span>");
                editor.insertElement(imag);
                console.log(domImg);
                layer.close(index);

            }
            , btn2: function (index, layero) {
                layer.closeAll(index ? index : 0);
            }
            , zIndex: layer.zIndex //重点1
            , success: function (layero, index) {
            }
            , end: function () {
                //更新索引
            }
        });
    })


}

/**
 * 弹出框架封装的图片上传及选择
 * @param layer
 * @param fn 选中的图片回调
 * @param  multi true为多选，false为单选
 */
function customImage(layer, fn, multi = false)
{
    var activeImages;
    layer.open({
        type: 2, //此处以iframe举例
        title: '上传图片',
        area:['90%', '600px'],
        shade: 0,
        maxmin: true,
        btn: ['确认', '取消'],
        content:  uppage+"?input_id="+input_id+"&pre_id="+pre_id+"&api="+api+"&min=true&type="+(multi ? 'multi' : ''),
        yes: function(index, layero){
            if(multi){
                activeImages = [];
                layer.getChildFrame('body', index).find('.grid-demo').find('.active').each(function(index,elm) {
                    var inputsrc = $(this).attr('data-inputsrc');
                    var imgsrc = $(this).find('img').attr('src');
                    var dataid = $(this).attr('data-id');
                    var dataname = $(this).attr('data-name');
                    activeImages.push({
                        inputsrc: inputsrc,
                        imgsrc: imgsrc
                    });
                });
                fn && fn(activeImages);
                layer.close(index);
            }else{
                var res = window["layui-layer-iframe" + index].callbackdata();
                fn && fn(res);
                layer.close(index);
            }
        },
        btn2: function(){},
        zIndex: layer.zIndex, //重点1
        success: function(layero, index){},
        end: function(){}
    });
}

/**
 * 自定义上传图片
 * @param layer
 * @param that
 */
function customImg(layer,that,min='true') {
    layer.open({
        type: 2 //此处以iframe举例
        ,title: '上传图片'
        ,area:['90%', '600px']
        ,shade: 0
        ,maxmin: true
        ,btn: ['确认', '取消']
        ,content:  uppage+"?input_id="+input_id+"&pre_id="+pre_id+"&api="+api+"&min="+min
        ,yes: function(index, layero){
            var res = window["layui-layer-iframe" + index].callbackdata();
            console.log(res);
            if (res.imgsrc == undefined) {
                layer.msg('请选择图片');
                return false
            }
            $(that).parents(".customClass").find(".customImgClass").attr('src', res.imgsrc);

            $(that).parent(".customImg").find(".up-input").val(res.imgInputSrc);
            // $(that).prev().attr('src', res.imgsrc);
            // $(that).prev().prev().val(res.imgInputSrc)
            layer.closeAll();

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
}

/**
 * wangEditor富文本图片上传
 * @param editor
 * @param min
 */
function wangEditorUpImg(editor,min='true') {
    console.log('-------------------------');
    layui.use('layer', function(){
        var $ = layui.jquery, layer = layui.layer; //独立版的layer无需执行这一句
        layer.open({
            type: 2 //此处以iframe举例
            ,title: '上传图片'
            ,area:['90%', '600px']
            ,shade: 0
            ,maxmin: true
            ,content:uppage+'?input_id='+input_id+'&pre_id='+pre_id+'&api='+api+"&type=multi&min="+min
            ,btn: ['确认', '取消']
            ,yes: function(index, layero){
                var domImg = "";
                layer.getChildFrame('body', index).find('.grid-demo').find('.active').each(function(index,elm) {
                    var inputsrc = $(this).attr('data-inputsrc');
                    var imgsrc = $(this).find('img').attr('src');
                    var dataid = $(this).attr('data-id');
                    domImg += "<img style=\"max-width:100%;border:0\" alt=\"\" src='"+ imgsrc +"'>";
                });

                editor.cmd.do( 'insertHTML', domImg )
                layer.close(index);
            },
            btn2: function(index, layero){
                layer.closeAll(index?index:0);
            },
            zIndex: layer.zIndex, //重点1
            success: function(layero, index){},
            end: function(){}
        });
    })
}