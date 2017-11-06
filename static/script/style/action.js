
$(function(){
    getConfig();

    $("#main-nav li ul li a").live("click",function(){

        $(this).addClass("current").parents().siblings().find("a").removeClass("current");
        $(this).parents().parents().parents().siblings().find("a").removeClass("current");

        var url = $(this).attr("data-href");
        var handler = 0;

        $(".main-content-inner").each(function(){
            var src = $(this).find("iframe").attr("src");
            if(url == src){
                $(this).show().siblings().hide();
                handler = 1;
            }
        })

        if(handler == 0){
            var factory = $('<div class="main-content-inner" ><iframe src="" width="100%" height="" frameborder="0"></iframe></div>');
            factory.find("iframe").attr("src",url);
            $("#main-content").append(factory);
            factory.show().siblings().hide();
        }

        myResize();


    })

    takeCookie();
})
/**
 * 循环配置文件进行赋值//打包生成左侧列表
 * @method  getConfig()
 * @param   {empty}     没有参数
 * @return  {empty}     没有返回值
 */
function getConfig(){
    var Oall = $('<li>' +
        '<a href="#" class="nav-top-item" style="padding-right: 15px;"> ' +
        '文章' +
        '</a>' +
        '<ul style="display: none;">' +
        '</ul></li>');
    var Oli =  $('<li><a class="" href="#1/105" data-href="http://www.baidu.com">管理文章</a></li>');
    for(var i in Oconfigjson){
        var Oall = $('<li>' +
            '<a href="#" class="nav-top-item" style="padding-right: 15px;"> ' +
            '文章' +
            '</a>' +
            '<ul style="display: none;">' +
            '</ul></li>');
        var Omain = Oconfigjson[i];
        var Oid = Omain.id;
        var OhomePage = Omain.homePage;
        var OmainText = Omain.menu[0].text;
        var Omenu = Omain.menu;
        var handle1 = i;
        Oall.find(".nav-top-item").text(OmainText);

        for(var j in Omain.menu[0].items){
            var Oreal = Omain.menu[0].items[j];
            var handle2 = Oreal.id;
            var Otext = Oreal.text;
            var Ohref = Oreal.href;
            var handler = "#"+handle1+"/"+handle2;
            var Oli =  $('<li><a class="" href="#1/105" data-href="http://www.baidu.com">管理文章</a></li>');

            Oli.find("a").attr("data-href",Ohref);
            Oli.find("a").attr("href",handler);
            Oli.find("a").text(Otext);

            Oall.find("ul").append(Oli);
        }

        $("#main-nav").append(Oall);
    }
}
/**
 * 刷新留住当前页面  window.location.hash
 * @method  takeCookie()
 * @param   {empty}     没有参数
 * @return  {empty}     没有返回值
 */
function takeCookie(){
    var Ocookie = window.location.hash;
    var mm = Ocookie.toString();
    var amm = mm.substr(1,Ocookie.length);
//    $(Ocookie).substr(1,$(Ocookie).length);
    var abmm = amm.split("/");
    $("#main-nav li").find(".nav-top-item").each(function(index){
        if(index == abmm[0]){
            $(this).trigger("click");
            $(this).parents().find("ul li").each(function(index){

                var newUrl = $(this).find("a").attr("href");
                if(Ocookie == newUrl){
                    $(this).find("a").trigger("click");
                }
            })
        }
    })


}


/**
 * iframe 随页面大小改变大小
 * @method  myResize()
 * @param   {empty}     没有参数
 * @return  {empty}     没有返回值
 */
function myResize(){
    var pageWidth = window.innerWidth;
    var pageHeight = window.innerHeight;
    var resizeWidth;
    var resizeHeight;
    var chatcontentResizeHeight;
    var textareaResizeWidth;
    if(typeof pageWidth != "number"){
        pageWidth = document.documentElement.clientWidth || document.body.clientWidth;
        pageHeight = document.documentElement.clientHeight || document.body.clientHeight;
    }

    $(".main-content-inner").each(function(){
        $(this).find("iframe").css("height",pageHeight);
    })
}


