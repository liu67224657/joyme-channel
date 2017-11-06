/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// 计算utf-8字符串字节数
String.prototype.utf8length = function(){
	if(this == '' || typeof(this)==undefined) return 0;
	var slen = this.length;
	var len = 0;
	for(var i=0; i<slen; i++){
		if(this.charCodeAt(i) <= 128){
			len += 1;
		}else{
			len += 3;
		}
	}
	return len;
}

function getImgSize(img){
	var nWidth, nHeight;
	if (img.naturalWidth) { // 现代浏览器
		nWidth = img.naturalWidth
		nHeight = img.naturalHeight
	} else { // IE6/7/8
		var image = new Image();
                if(img.src){
                    image.src = img.src;
                }else{
                    image.src = document.getElementById('litpic').src;
                }
		console.log('image.src:', image.src);
		image.onload = function() {
                    nWidth = this.width;
                    nHeight = this.height;
		}
	}
	return [nWidth, nHeight]
}


