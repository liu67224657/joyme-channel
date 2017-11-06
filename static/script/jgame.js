/**
 * Created by kexuedong on 2017/5/3.
 */
$(function () {
    $("#selectgname").click(function () {
        var html = '<div class="clearfix"><input type="text" name="searchtext" id="searchtext"> <i id="view_searchtext" style="margin-left: 60px;">查询</i><div class="clearfix"><ul class="searchresults"></ul></div><hr><div class="clearfix selecteddiv"><ul class="selectedresult"></ul></div></div>';
        layer.alert('', {
            title: "查询游戏",
            content: html,
            area: ["400px", "500px"],
            success: function(layero, index){
                $("#view_searchtext").click(function () {
                    var searchtext = $("#searchtext").val();
                    if(searchtext==''){
                        alert("游戏名称不能为空");
                        return false;
                    }
                    $.post('/index.php', {c:'jgame', a:'searchgame', searchtext:searchtext}, function(res){
                        res = JSON.parse(res);
                        console.log(res);
                        $(".searchresults").html(" ");
                        if(res.rs == 1){
                            var ulhtml = '';
                            for (x in res.msg) {
                                console.log("x",x,res.msg[x]);
                                var ht = '<li><label style="display: inline-block;" class="searchli" data-gameid="'+res.msg[x].gameId+'" data-gamename="'+res.msg[x].gameName+'">'+res.msg[x].gameName+'</label></li>';
                                ulhtml+=ht;
                            }
                            $(".searchresults").append(ulhtml);
                            $(".searchli").click(function () {
                                var gameid = $(this).data("gameid");
                                $("#gid").val(gameid);
                                var gamename = $(this).data("gamename");
                                $("#hiddengamename").val(gamename);
                                var ht = '<li><label style="display: inline-block;" class="selectedli" data-gameid="'+gameid+'" data-gamename="'+gamename+'">'+gamename+'</label></li>';
                                $(".selectedresult").html(ht);
                            });
                        }else {
                            var msght = '<span style="display:block;text-align: center;padding-right:25px;margin-top: 20px;">游戏库中没有查到相关游戏</span>';
                            $(".searchresults").append(msght);
                        }
                    });
                });
            }
        }, function (index) {
            var gamename = $("#hiddengamename").val();
            $("#gamename").val(gamename);
            layer.close(index);
        });
    });
});