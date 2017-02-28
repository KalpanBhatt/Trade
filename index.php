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
        var_dump($zackValues);
        
	    $param = array(
	        ':symbol' => $eachStockTicker['Symbol'],
	        ':currentDatetime' => Carbon::now("America/New_York"));

        if ($eachStockTicker['CurrentRank'] == NULL || $zackValues['zackRank'] !== $eachStockTicker['CurrentRank']) {
            // Rank changed, so capture todays date and rank, and update previous ranks
            $query = $dbObj->prepare("UPDATE zackRank 
                                SET CurrentRank=:rank,
                                    CurrentRank_UpdateDate=:currentRank_UpdateDate,
                                    Rank1_UpdateDate=:rank1_UpdateDate,
                                    PreviousRank_1=:previousRank_1,
                                    Rank2_UpdateDate=:rank2_UpdateDate,
                                    PreviousRank_2=:previousRank_2,
                                    Updated_At=:currentDatetime 
                                WHERE Symbol=:symbol");

            $param[':rank'] = $zackValues['zackRank'];
            $param[':currentRank_UpdateDate'] = Carbon::now("America/New_York")->toDateString();
            $param[':previousRank_1'] = $eachStockTicker['CurrentRank'];
            $param[':rank1_UpdateDate'] = $eachStockTicker['CurrentRank_UpdateDate'];
            $param[':previousRank_2'] = $eachStockTicker['PreviousRank_1'];
            $param[':rank2_UpdateDate'] = $eachStockTicker['Rank1_UpdateDate'];
            
            $row = $query->execute($param) or die(print_r($query->errorInfo(), true));   
        }
        
        if ($zackValues['zackIndustry'] !== $eachStockTicker['Industry'] || $zackValues['zackSubSector'] !== $eachStockTicker['SubSector'] || $zackValues['zackCompanyName'] !== $eachStockTicker['Company_Name']) {
            $query = $dbObj->prepare("UPDATE zackRank 
                                SET Industry=:industry,
                                    SubSector=:subSector,
                                    Company_Name=:companyName,
                                    Updated_At=:currentDatetime 
                                WHERE Symbol=:symbol");

            $param[':industry'] = $zackValues['zackIndustry'];
            $param[':subSector'] = $zackValues['zackSubSector'];
            $param[':companyName'] = $zackValues['zackCompanyName'];

            $row = $query->execute($param) or die(print_r($query->errorInfo(), true));
        }
        var_dump(date("Y-m-d"));
        var_dump($eachStockTicker['NextEarningsDate']);
        if (($eachStockTicker['NextEarningsDate'] == NULL && $eachStockTicker['CurrentRank'] != 0) || $eachStockTicker['NextEarningsDate'] < date("Y-m-d")) {
            if ((strpos($zackValues['zackEarningDate'], 'AMC') !== false) || (strpos($zackValues['zackEarningDate'], 'BMO') !== false)) {
                $zackValues['zackEarningDate'] = substr($zackValues['zackEarningDate'], 4);
            }
            $query = $dbObj->prepare("UPDATE zackRank 
                                SET NextEarningsDate=:EarningsDate
                                WHERE Symbol=:symbol");
            var_dump($zackValues['zackEarningDate']);
            var_dump(Carbon::createFromFormat('m/d/Y', $zackValues['zackEarningDate']));
            $param[':EarningsDate'] = Carbon::createFromFormat('m/d/Y', $zackValues['zackEarningDate'])->toDateString();

            $row = $query->execute($param) or die(print_r($query->errorInfo(), true));
        }
        sleep(3);
	}
}

$sql = "SELECT * FROM zackRank ORDER BY NextEarningsDate ASC";
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
/*

(`ID`,
`Symbol`,
`NextEarningsDate`,             DATE
`Company_Name`,
`CurrentRank`,                  INT(11)
`CurrentRank_UpdateDate`,       DATE
`PreviousRank_1`,               INT(11)
`Rank1_UpdateDate`,             DATE
`PreviousRank_2`,               INT(11)
`Rank2_UpdateDate`,             DATE
`Industry`,
`SubSector`,
`Updated_At`)

*/