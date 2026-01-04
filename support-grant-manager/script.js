(function($){
var shown=sessionStorage.getItem('sjgShown');
var cnt=parseInt(sessionStorage.getItem('sjgCnt'))||0;
var scroll=0;

$(document).ready(function(){
    $(document).on('mouseout',function(e){if(e.clientY<0&&!shown&&cnt<2)show()});
    history.pushState(null,'',location.href);
    $(window).on('popstate',function(){if(cnt<2)show();history.pushState(null,'',location.href)});
    $(window).on('scroll',function(){
        var p=($(window).scrollTop()/($(document).height()-$(window).height()))*100;
        if(p>60&&!shown&&!scroll&&cnt<2){show();scroll=1}
    });
    setTimeout(function(){$('.sjg-full').fadeIn();setTimeout(function(){$('.sjg-full').fadeOut()},5000)},3000);
});

function show(){$('#sjgPop').css('display','flex');shown=1}

window.sjgClose=function(){
    $('#sjgPop').fadeOut();
    $('html,body').animate({scrollTop:$('.sjg-hero').offset().top-100},800);
}

window.sjgNo=function(){
    $('#sjgPop').fadeOut();
    shown=1;cnt++;
    sessionStorage.setItem('sjgShown','1');
    sessionStorage.setItem('sjgCnt',cnt);
}
})(jQuery);
