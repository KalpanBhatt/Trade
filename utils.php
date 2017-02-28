<?php

if (strpos($_SERVER['DOCUMENT_ROOT'], 'C:') !== false) {
  require_once $_SERVER['DOCUMENT_ROOT'].'/Trade/simple_html_dom.php';
  require_once $_SERVER['DOCUMENT_ROOT'].'/Trade/starter.php';
  $CONFIG['DB'] = array(
      'dbDriver'	=> 	'mysql',
      'hostname' 	=> 	'localhost',
      'username' 	=> 	'root',
      'password' 	=> 	'',
      'dbName'	=>	'Trade'
  );
} else {
  require_once $_SERVER['DOCUMENT_ROOT'].'/simple_html_dom.php';
  require_once $_SERVER['DOCUMENT_ROOT'].'/starter.php';
  $CONFIG['DB'] = array(
      'dbDriver'	=> 	'mysql',
      'hostname' 	=> 	'localhost',
      'username' 	=> 	'homestead',
      'password' 	=> 	'secret',
      'dbName'	=>	'Trade'
  );
}


$dbObj = new PDO($CONFIG['DB']['dbDriver'].':host='.$CONFIG['DB']['hostname'].';dbname='.$CONFIG['DB']['dbName'],
    $CONFIG['DB']['username'],$CONFIG['DB']['password']);

$sql = "SELECT * FROM zackRank";
$query = $dbObj->query($sql);
$FetchedMajors = $query->fetchAll();

foreach ($FetchedMajors as $symbols) {
	
}

updateEarningsDate($FetchedMajors, $dbObj);




function getNextEarningsDate($ticker){
    $stock_url = 'https://www.zacks.com/stock/quote/'.$ticker;

    $content = get_web_page($stock_url);
    $htmlPage = str_get_html($content['content']);

    $temp = $htmlPage->find("#stock_key_earnings sup.spl_sup_text")[0]->parent()->text();

    return $temp;
}

function updateEarningsDate($FetchedMajors, $dbObj){
	foreach ($FetchedMajors as $Symbols) {
		if ($Symbols['NextEarningsDate'] != NULL) {
			if ((strpos($Symbols['NextEarningsDate'], 'AMC') !== false) || (strpos($Symbols['NextEarningsDate'], 'BMO') !== false)) {
				$query = $dbObj->prepare("UPDATE zackRank set NextEarningsDate=:correctedDate where Symbol=:symbol");
				$param = array(
						':correctedDate' => substr($Symbols['NextEarningsDate'], 4),
						':symbol' => $Symbols['Symbol']
				);

				$row = $query->execute($param) or die(print_r($query->errorInfo(), true));
			}
		}
	}
}
