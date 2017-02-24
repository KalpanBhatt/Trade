<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/simple_html_dom.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/starter.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/email.php';

require 'vendor/autoload.php';
Use Carbon\Carbon;

$CONFIG['DB'] = array(
    'dbDriver'	=> 	'mysql',
    'hostname' 	=> 	'localhost',
    'username' 	=> 	'homestead',
    'password' 	=> 	'secret',
    'dbName'	=>	'Trade'
);

$dbObj = new PDO($CONFIG['DB']['dbDriver'].':host='.$CONFIG['DB']['hostname'].';dbname='.$CONFIG['DB']['dbName'],
    $CONFIG['DB']['username'],$CONFIG['DB']['password']);

$sql = "SELECT * FROM zackRank";
$query = $dbObj->query($sql);
$FetchedMajors = $query->fetchAll();

foreach($FetchedMajors as $symbol){
		if(Carbon::now("America/New_York")->diffInHours(Carbon::parse($symbol['Updated_At'], 'America/New_York')) > 24 || $symbol['Updated_At'] == NULL){
		    $query = $dbObj->prepare("UPDATE zackRank set Rank=:Rank, Updated_At=:currentDatetime where symbol=:symbol");
		    $param = array(
		        ':Rank' => getRank($symbol['Symbol']),
		        ':symbol' => $symbol['Symbol'],
		        ':currentDatetime' => Carbon::now("America/New_York")
		    );

		    $row = $query->execute($param) or die(print_r($query->errorInfo(), true));
		}
}
echo "<!DOCTYPE html>";
$sql = "SELECT * FROM zackRank where Rank < 3";
$query = $dbObj->query($sql);
$FetchedMajors = $query->fetchAll();

$renderHTML = "<h4>Finished Processing - DB Update completed.</h4>";
$renderHTML = $renderHTML . 
            "<table border='1'>
                <tr>
                    <td> SYMBOL </td>
                    <td> RANK </td>
                    <td> COMPANY NAME </td>
                    <td> Earnings Date </td>
                </tr>";
foreach($FetchedMajors as $symbol){
		$renderHTML = $renderHTML.
                '<tr>
                    <td>'.$symbol['Symbol'].'</td>
                    <td>'.$symbol['Rank'].'</td>
                    <td>'.$symbol['Company_Name'].'</td>
                    <td>'.$symbol['NextEarningsDate'].'</td>
                </tr>';
}
$renderHTML = $renderHTML . "</table>";
echo ($renderHTML);

sendMail($renderHTML);

//SnPRanks();

function SnPRanks(){
    $stocklist = array();
    $csv = array_map('str_getcsv', file('constituents.csv'));

    for ($i=35; $i < 38; $i++) {
        array_push($stocklist, $csv[$i][0]);
        $rank = getRank($stocklist[$i-35]);
				$nextEarning = getNextEarningsDate($stocklist[$i-35]);
				$currentPrice = getYahooPrice($stocklist[$i-35]);
				$oneYearTarget = get1yearPrice($stocklist[$i-35]);
        if($rank[0] < 6){
            echo "<div class='cell' style='margin: 5px; padding: 5px; min-width:300px;'>".$csv[$i][0]."</div>";
            echo "<div class='cell' style='margin: 5px; padding: 5px; min-width:500px;'>".$csv[$i][1]."</div>";
            echo "<div class='cell' style='margin: 5px; padding: 5px; min-width:400px;'>".$csv[$i][2]."</div>";
            echo "<div class='cell' style='margin: 5px; padding: 5px; min-width:100px;'>".$rank."</div>";
						echo "<div class='cell' style='margin: 5px; padding: 5px; min-width:100px;'>".$nextEarning."</div>";
						echo "<div class='cell' style='margin: 5px; padding: 5px; min-width:100px;'>".$currentPrice."</div>";
						echo "<div class='cell' style='margin: 5px; padding: 5px; min-width:100px;'>".$oneYearTarget."</div>";
            echo "<br />";
        }
        //sleep(3);
    }
}

function getRank($ticker){
    $stock_url = 'https://www.zacks.com/stock/quote/'.$ticker;

    $content = get_web_page($stock_url);
    $htmlPage = str_get_html($content['content']);

    $temp = trim($htmlPage->find("div.zr_rankbox")[1]->innertext());

    $arr = explode("<span", $temp, 2);
    $first = $arr[0];
    return $first;
}

function getNextEarningsDate($ticker){
    $stock_url = 'https://www.zacks.com/stock/quote/'.$ticker;

    $content = get_web_page($stock_url);
    $htmlPage = str_get_html($content['content']);

    $temp = $htmlPage->find("#stock_key_earnings sup.spl_sup_text")[0]->parent()->text();

    return $temp;
}

function getYahooPrice($ticker){
    $stock_url = 'https://www.zacks.com/stock/quote/'.$ticker;

    $content = get_web_page($stock_url);
    $htmlPage = str_get_html($content['content']);

    $temp = $htmlPage->find("p.last_price")[0]->text();

    return $temp;
}

function get1yearPrice($ticker){
    $stock_url = 'http://finance.yahoo.com/quote/'.$ticker;

    $content = get_web_page($stock_url);
    $htmlPage = str_get_html($content['content']);

    $temp = $htmlPage->find("#quote-summary div")[1]->outertext();

    return $temp;
}
