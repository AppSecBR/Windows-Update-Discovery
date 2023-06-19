<?php

function split_content($text, $start, $end) {
    $tmp = explode($end, $text);
    $tmp = explode($start, $tmp[0]);
    return $tmp[1];
}

$url = 'https://www.catalog.update.microsoft.com/Search.aspx?q=';

//filters
$url .= 'x64 cumulative windows security ';

//block
$url .= '-framework -explorer -dynamic -azure ';

$url .= date('Y-m');

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, str_replace (' ','%20',$url));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HEADER, false);
$data = curl_exec($curl);

$data = split_content($data, 'table class', 'poupInfoDiv');
$data = explode('resultsIconWidth',$data);
array_shift($data);

foreach($data as $update){
	
	$details = explode('resultsbottomBorder resultspadding',$update);
	$description = split_content($details[1], 'href', 'td');
	$description = trim(split_content($description, '>', '<'));
	$kb = trim(split_content($description, '(', ')'));
	$os = trim(split_content($details[2], '>', '<'));
	$date = trim(split_content($details[4], '>', '<'));
	$date = explode('/',$date);
	if(strlen($date[0]) == 1){
		$date[0] = '0'.$date[0];
	}
	$date = $date[2].'-'.$date[0].'-'.$date[1];

	if(stripos($description, 'Cumulative Update for ') === false){
		continue;
	}

	if(stripos($os, ' Server') !== false){
		$os = str_replace('Microsoft', 'Windows', $os);
		if(stripos($description, 'Windows Server, version') !== false || 
			stripos(strtolower($description), 'operating system') !== false){
			$version = split_content($description, 'ersion ', ' for x');
			$os = 'Windows Server 2022 '.$version;
		}
	}else{
		if(stripos($description, '11 for') !== false){
			$version = '21H2';
		}else{
			$version = split_content($description, 'ersion ', ' for x');
		}
		$os = explode(',', $os);
		$os = $os[0].' '.$version;
	}

	$return[$os] = array(
		'kb' => $kb,
		'date' => $date,
		'description' => $description,
	);
}
if(is_array($return) && count($return) >=1){
	echo json_encode($return);
}else{
	echo '[]';
}
