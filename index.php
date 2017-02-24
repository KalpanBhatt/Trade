<?php

if (strpos($_SERVER['DOCUMENT_ROOT'], 'C:') !== false) {
  require_once $_SERVER['DOCUMENT_ROOT'].'/Trade/simple_html_dom.php';
  require_once $_SERVER['DOCUMENT_ROOT'].'/Trade/starter.php';
  require_once $_SERVER['DOCUMENT_ROOT'].'/Trade/email.php';

  require 'vendor/autoload.php';

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
  require_once $_SERVER['DOCUMENT_ROOT'].'/email.php';

  require 'vendor/autoload.php';

  $CONFIG['DB'] = array(
      'dbDriver'	=> 	'mysql',
      'hostname' 	=> 	'localhost',
      'username' 	=> 	'homestead',
      'password' 	=> 	'secret',
      'dbName'	=>	'Trade'
  );
}

Use Carbon\Carbon;

$dbObj = new PDO($CONFIG['DB']['dbDriver'].':host='.$CONFIG['DB']['hostname'].';dbname='.$CONFIG['DB']['dbName'],$CONFIG['DB']['username'],$CONFIG['DB']['password']);

$sql = "SELECT * FROM zackRank";
$query = $dbObj->query($sql);
$completeZACKRANK_table = $query->fetchAll();

foreach($completeZACKRANK_table as $eachStockTicker){
		if(Carbon::now("America/New_York")->diffInHours(Carbon::parse($eachStockTicker['Updated_At'], 'America/New_York')) > -1 || $eachStockTicker['Updated_At'] == NULL){

        $zackValues = xtractZACKs($eachStockTicker['Symbol']);

		    $query = $dbObj->prepare("UPDATE zackRank set Rank=:Rank, Updated_At=:currentDatetime, Industry=:industry, SubSector=:subsector where Symbol=:symbol");
		    $param = array(
		        ':Rank' => $zackValues['zackRank'],
		        ':symbol' => $eachStockTicker['Symbol'],
		        ':currentDatetime' => Carbon::now("America/New_York"),
            ':industry' => $zackValues['zackIndustry'],
            ':subsector'=> $zackValues['zackSubSector']
		    );

		    $row = $query->execute($param) or die(print_r($query->errorInfo(), true));
		}
    sleep(3);
}

$sql = "SELECT * FROM zackRank where Rank < 6 ORDER BY NextEarningsDate ASC";
$query = $dbObj->query($sql);
$filteredZACKRANK_table = $query->fetchAll();

$renderHTML = "";
$renderHTML = $renderHTML .
            "<table border='1'>
                <tr>
                    <td> SYMBOL </td>
                    <td> RANK </td>
                    <td> COMPANY NAME </td>
                    <td> Earnings Date </td>
                </tr>";
foreach($filteredZACKRANK_table as $eachStockTicker){
		$renderHTML = $renderHTML.
                ($eachStockTicker['Rank'] < 3 ? '<tr style="color:green;">' : '<tr style="color:red;">').
                   '<td>'.$eachStockTicker['Symbol'].'</td>
                    <td>'.$eachStockTicker['Rank'].'</td>
                    <td>'.$eachStockTicker['Company_Name'].'</td>
                    <td>'.$eachStockTicker['NextEarningsDate'].'</td>
                </tr>';
}
$renderHTML = $renderHTML . "</table>";

echo ($renderHTML);
//sendMail($renderHTML);


function xtractZACKs($ticker){
    $stock_url = 'https://www.zacks.com/stock/quote/'.$ticker;

    $content = get_web_page($stock_url);
    $zacksHtmlPage = str_get_html($content['content']);

    $zackValues = [
        'zackRank'            => '',
        'zackEarningDate'     => '',
        'zackLastClosingPrice'=> '',
        'zackIndustry'        => '',
        'zackSubSector'       => ''
    ];
    $zackValues['zackRank'] = getZACKRank($zacksHtmlPage);
    $zackValues['zackEarningDate'] = getNextEarningsDate($zacksHtmlPage);
    $zackValues['zackLastClosingPrice'] = getLastClosingPrice($zacksHtmlPage);
    $zackValues['zackIndustry'] = getZackIndustry($zacksHtmlPage)['Industry'];
    $zackValues['zackSubSector'] = getZackIndustry($zacksHtmlPage)['SubSector'];

    return $zackValues;
}



function getZACKRank($htmlPage){

    $temp = trim($htmlPage->find("div.zr_rankbox")[1]->innertext());

    $arr = explode("<span", $temp, 2);
    $first = $arr[0];
    return $first;
}

function getNextEarningsDate($htmlPage){

    $temp = $htmlPage->find("#stock_key_earnings sup.spl_sup_text")[0]->parent()->text();

    return $temp;
}

function getLastClosingPrice($htmlPage){

    $temp = $htmlPage->find("p.last_price")[0]->text();

    return $temp;
}

function getZackIndustry($htmlPage){

    $temp = $htmlPage->find("table.abut_top a");

    return [
      'Industry' => $temp[0]->text(),
      'SubSector'=> $temp[1]->text()
    ];

}

function get1yearPrice($ticker){
    $stock_url = 'http://finance.yahoo.com/quote/'.$ticker;

    $content = get_web_page($stock_url);
    $htmlPage = str_get_html($content['content']);

    $temp = $htmlPage->find("#quote-summary div")[1]->outertext();

    return $temp;
}
