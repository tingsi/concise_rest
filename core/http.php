<?php

//发送http请求
function open_http_url($url, $returnflag = false){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1) ;
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1) ;
	$result = curl_exec($ch) ;
	curl_close($ch);
	return $result;
}

//发送https请求
function _https_curl_post($url, $vars)
{
    foreach($vars as $key=>$value)
    {
        $fields_string .= $key.'='.$value.'&' ;
    }
    $fields_string = substr($fields_string,0,(strlen($fields_string)-1)) ;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // this line makes it work under https
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POST, count($vars) );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    $data = curl_exec($ch);
    curl_close($ch);
    if ($data){
        return $data;
    }
    else{
        return false;
    }
}

//利用curl发送post请求
function Post($curlPost, $url)
{
	$curlPost=http_build_query($curlPost);
	$curl = curl_init ();
	curl_setopt ( $curl, CURLOPT_URL, $url );
	curl_setopt ( $curl, CURLOPT_HEADER, false );
	curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, true );
	curl_setopt ( $curl, CURLOPT_NOBODY, true );
	curl_setopt ( $curl, CURLOPT_POST, true );
	curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, false);//忽略证书错误
	curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, false);//忽略证书错误
	curl_setopt ( $curl, CURLOPT_POSTFIELDS, $curlPost );
	$return_str = curl_exec ( $curl );
	curl_close ( $curl );
	return $return_str;
}
