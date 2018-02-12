<?php
	header('HTTP/1.1 '.$code.' '.$message);
	$meta=array();
	$meta['code'] = $code;
	$meta['message'] = $message;
	$response = array('meta'=>$meta);
	echo json_encode($response);
?>