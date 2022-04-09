function Tpl(element,menuElement, success) {
    let iTpl = {
        activeHash: location.hash.substring(1),
        getActiveHash: function (){
            return location.hash.substring(1)
        },
        isHashChanged: function () {
            if(iTpl.activeHash === location.hash.substring(1)){
                return false
            }else{
                return true
            }
        },
        renderTemplate: function (body){
            $(element).html(body)
            // 渲染完成回调
            success && success()
            $("a[href]").click(function () {
                location.hash = this.getAttribute('href');
                return false
            })
        },
        hashChangeFire: function () {
            let hash = location.hash.substring(1);
            if(hash.indexOf("javascript")===0){
                location.hash = iTpl.activeHash;
                return false;
            }
            iTpl.activeHash = hash
            $.ajax({
                url: iTpl.activeHash,
                data: {},
                success: iTpl.renderTemplate,
                dataType: 'html',
                error: function (XMLhttpServlet) {
                    console.log("readystate->" + XMLhttpServlet.readyState);
                    console.log("status->" + XMLhttpServlet.status);
                    console.log("网络故障！")
                    // 根据错误code,返回异常模板
                    switch (XMLhttpServlet.status){
                        case 401:
                        case 403: iTpl.renderTemplate("<div>无权访问: "+activeHash+"</div>");break
                        case 404: iTpl.renderTemplate("<div>没有找到路由: "+activeHash+"</div>");break
                        case 500: iTpl.renderTemplate("<div>服务器错误: "+activeHash+"</div>");break
                    }
                }
            });
        }

    }
    // 监听菜单栏点击事件
    $(menuElement).click(function (){
        location.hash = this.getAttribute('lay-url')
    })
    // 监听hash变化
    if (("onhashchange" in window) && ((typeof document.documentMode === "undefined") || document.documentMode === 8)) {
        window.onhashchange = iTpl.hashChangeFire;
    } else {
        // 不支持则用定时器检测的办法
        setInterval(function () {
            if (iTpl.isHashChanged()) {
                iTpl.hashChangeFire();
            }
        }, 150);
    }
    // 初始化页面
    iTpl.hashChangeFire()
    return iTpl
}
let tpl = Tpl(".vit-content", "#docDemoMenu1>li", function (){
    console.log("模板渲染完成之后执行回调")
})

//隐藏显示菜单
function vit_minmenu(){
    var positionLeft = $(".min-menu").position().left;
    if(positionLeft == -200){
        $(".min-menu").css({position: "absolute"});
        $(".min-menu").animate({left: "0"});
        $("#vit-body-menu").animate({"margin-left":"200px"});
        $("#vitionClass").removeClass("vit-icon-zhankai").addClass("vit-icon-shouqi");
        // $("#vit-body-menu .vit_minmenu").animate({left: "453px"});
    }else{
        $(".min-menu").css({position: "absolute"});
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