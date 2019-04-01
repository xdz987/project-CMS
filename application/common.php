<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
//  error_reporting(E_ERROR | E_WARNING | E_PARSE);
function get_ueditor($name, $value = '', $width = 1000, $height = 400) {
	$str = <<<HTML
	<textarea name='$name' id='$name'>$value</textarea>
	<script type="text/javascript">
        UE.getEditor('$name',{toolbars:[
            [
                'fullscreen', 'source', '|', 'undo', 'redo', '|',
            'bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'pasteplain', '|', 'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', 'selectall', 'cleardoc', '|',
            'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',
            'customstyle', 'paragraph', 'fontfamily', 'fontsize', '|',
            'directionalityltr', 'directionalityrtl',
            // 'indent', '|',
            // 'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|', 'touppercase', 'tolowercase', '|',
            // 'link', 'unlink', 'anchor', '|', 'imagenone', 'imageleft', 'imageright', 'imagecenter', '|',
            // 'simpleupload', 'insertimage', 'emotion', 'scrawl', 'insertvideo', 'music', 'attachment'
            ]
            ],initialFrameWidth:$width,initialFrameHeight:$height,});
	</script>
HTML;
	return $str;
}