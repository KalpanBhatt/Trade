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
	if($eachStockTicker['Updated_At'] == NULL || Carbon::now("America/New_York")->diffInHours(Carbon::parse($eachStockTicker['Updated_At'], 'America/New_York')) > 24){

        $zackValues = xtractZACKs($eachStockTicker['Symbol']);

        $query = $dbObj->prepare("UPDATE zackRank 
                                SET Latest_ClosingPrice=".$zackValues['zackLastClosingPrice'].
                                ", Updated_At='".Carbon::now("America/New_York").
                                "' WHERE ID=".$eachStockTicker['ID']);
        $row = $query->execute() or die(print_r($query->errorInfo(), true));

        if ($eachStockTicker['CurrentRank'] == NULL || $zackValues['zackRank'] !== $eachStockTicker['CurrentRank']) {
            // Rank changed, so capture todays date and rank, and update previous ranks
            $query = $dbObj->prepare("UPDATE zackRank
                                SET CurrentRank=:rank,
                                    CurrentRank_UpdateDate=:currentRank_UpdateDate,
                                    Rank1_UpdateDate=:rank1_UpdateDate,
                                    PreviousRank_1=:previousRank_1,
                                    Rank1_ClosingPrice=:rank1_ClosingPrice,
                                    Rank2_UpdateDate=:rank2_UpdateDate,
                                    PreviousRank_2=:previousRank_2,
                                    Rank2_ClosingPrice=:rank2_ClosingPrice,
                                    Updated_At=:currentDatetime
                                WHERE Symbol=:symbol");

            $param1 = array();
            $param1[':symbol'] = $eachStockTicker['Symbol'];
            $param1[':rank'] = $zackValues['zackRank'];
            $param1[':currentRank_UpdateDate'] = Carbon::now("America/New_York")->toDateString();
            $param1[':previousRank_1'] = $eachStockTicker['CurrentRank'];
            $param1[':rank1_UpdateDate'] = $eachStockTicker['CurrentRank_UpdateDate'];
            $param1[':rank1_ClosingPrice'] = $eachStockTicker['Latest_ClosingDate'];
            $param1[':previousRank_2'] = $eachStockTicker['PreviousRank_1'];
            $param1[':rank2_UpdateDate'] = $eachStockTicker['Rank1_UpdateDate'];
            $param1[':rank2_ClosingPrice'] = $eachStockTicker['Rank1_ClosingPrice'];
            $param1[':currentDatetime'] = Carbon::now("America/New_York");

            $row = $query->execute($param1) or die(print_r($query->errorInfo(), true));
        }

        if ($zackValues['zackIndustry'] !== $eachStockTicker['Industry'] || $zackValues['zackSubSector'] !== $eachStockTicker['SubSector'] || $zackValues['zackCompanyName'] !== $eachStockTicker['Company_Name']) {
            $query = $dbObj->prepare("UPDATE zackRank
                                SET Industry=:industry,
                                    SubSector=:subSector,
                                    Company_Name=:companyName,
                                    Updated_At=:currentDatetime
                                WHERE Symbol=:symbol");
            $param2 = array();
            $param2[':symbol'] = $eachStockTicker['Symbol'];
            $param2[':industry'] = $zackValues['zackIndustry'];
            $param2[':subSector'] = $zackValues['zackSubSector'];
            $param2[':companyName'] = $zackValues['zackCompanyName'];
            $param2[':currentDatetime'] = Carbon::now("America/New_York");

            $row = $query->execute($param2) or die(print_r($query->errorInfo(), true));
        }
        // What if earningsDate changes after it is initially set
        if ($eachStockTicker['NextEarningsDate'] == NULL || $zackValues['zackEarningDate'] !== $eachStockTicker['NextEarningsDate']) {
            if ((strpos($zackValues['zackEarningDate'], 'AMC') !== false) || (strpos($zackValues['zackEarningDate'], 'BMO') !== false)) {
                $zackValues['zackEarningDate'] = substr($zackValues['zackEarningDate'], 4);
            }
            $query = $dbObj->prepare("UPDATE zackRank
                                SET NextEarningsDate=:EarningsDate,
                                Updated_At=:currentDatetime
                                WHERE Symbol=:symbol");

            $param3 = array();
            $param3[':symbol'] = $eachStockTicker['Symbol'];
            $param3[':EarningsDate'] = Carbon::createFromFormat('m/d/Y', $zackValues['zackEarningDate'])->toDateString();
            $param3[':currentDatetime'] = Carbon::now("America/New_York");

            $row = $query->execute($param3) or die(print_r($query->errorInfo(), true));
        }
        //sleep(3);
	}
}

$sql = "SELECT * FROM zackRank ORDER BY CurrentRank ASC";
$query = $dbObj->query($sql);
$sortedZACKRANK_table = $query->fetchAll();

$renderHTML = "";
$renderHTML = $renderHTML .
            "<table border='1'>
                <tr>
                    <td> SYMBOL </td>
                    <td> RANK </td>
                    <td> COMPANY NAME </td>
                    <td> Earnings Date </td>
                </tr>";
foreach($sortedZACKRANK_table as $eachStockTicker){
		$renderHTML = $renderHTML.
                ($eachStockTicker['CurrentRank'] < 3 ? '<tr style="color:green;">' : ($eachStockTicker['CurrentRank'] > 3 ? '<tr style="color:red;">' : '<tr style="color:black;">')).
                   '<td>'.$eachStockTicker['Symbol'].'</td>
                    <td>'.$eachStockTicker['CurrentRank'].'</td>
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
        'zackSubSector'       => '',
        'zackCompanyName'     => ''
    ];
    $zackValues['zackRank'] = getZACKRank($zacksHtmlPage);
    $zackValues['zackEarningDate'] = getNextEarningsDate($zacksHtmlPage);
    $zackValues['zackLastClosingPrice'] = getLastClosingPrice($zacksHtmlPage);
    $zackValues['zackIndustry'] = getZackIndustry($zacksHtmlPage)['Industry'];
    $zackValues['zackSubSector'] = getZackIndustry($zacksHtmlPage)['SubSector'];
    $zackValues['zackCompanyName'] = getZackFullName($zacksHtmlPage);

    return $zackValues;
}

function getZACKRank($htmlPage){

    $temp = trim($htmlPage->find("div.zr_rankbox")[1]->innertext());

    $arr = explode("<span", $temp, 2);
    $first = substr($arr[0], 0, 1);
    return $first;
}

function getNextEarningsDate($htmlPage){

    $temp = $htmlPage->find("#stock_key_earnings sup.spl_sup_text")[0]->parent()->text();
    // Change date format from 5/13/17 to 5/13/2017
    $temp = substr($temp,0,strrpos($temp, '/')+1).'20'.substr($temp,strrpos($temp, '/')+1);
    return $temp;
}

function getLastClosingPrice($htmlPage){

    $temp = $htmlPage->find("p.last_price")[0]->text();
    $temp = trim(substr($temp, 1));     //Remove $ sign and trim trailing spaces
    $temp = substr($temp, 0, strpos($temp, ' '));       //Remove USD from text
    return $temp;
}

function getZackIndustry($htmlPage){

    $temp = $htmlPage->find("table.abut_top a");

    return [
      'Industry' => $temp[0]->text(),
      'SubSector'=> $temp[1]->text()
    ];
}

function getZackFullName($htmlPage){

    $temp = trim($htmlPage->find("#quote_ribbon_v2 a")[0]->innertext());

    return $temp;
}

function get1yearPrice($ticker){
    $stock_url = 'http://finance.yahoo.com/quote/'.$ticker;

    $content = get_web_page($stock_url);
    $htmlPage = str_get_html($content['content']);

    $temp = $htmlPage->find("#quote-summary div")[1]->outertext();

    return $temp;
}
