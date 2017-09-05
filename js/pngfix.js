/// <reference path="jquery-1.7.1-vsdoc.js" />
$(document).ready(function () {	
	try {
    	resetSize();
    	$(window).resize("", resetSize);
	} 
	catch(e) {
		//alert(e.Message);	
	}
});

function resetSize() {    
    var $width = $(window).width();
    var $height = $(window).height();
    var $content = $("#main");
    var scaleW = 1;
    var scaleH = 1;

    // �������ű���
    scaleW = ($width / 1920);
    scaleH = ($height / 1080);

    if (scaleW > 1) scaleW = 1;
    if (scaleH > 1) scaleH = 1;

    if ($content.height() < $height)
        $content.css("height", $height);

    // ����logo�����ű���
    //$("#main .header img").css("width", scaleW * 392).css("height", scaleH * 54);

    // �������ֵ����ű���   
    //$("#main .text img").css("width", scaleW * 437).css("height", scaleH * 86);
	
	// png fix if lt ie7
	$("#main").pngFix();
}