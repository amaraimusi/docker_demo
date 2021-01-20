<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="google" content="notranslate" />
   	<meta http-equiv="X-UA-Compatible" content="IE=edge">
   	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>DB情報ツール</title><link rel='shortcut icon' href='example/favicon.ico' />
	
	<link href="http://code.jquery.com/ui/1.12.1/themes/cupertino/jquery-ui.min.css" rel="stylesheet">
	<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">

	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
	<script src="http://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>



</head>
<body>
<div id="header" ><h1>DB情報ツール</h1></div>
<div class="container">
Take7
<?php 

$host = 'docker_demo_mysql_1';
$dbname = 'cake_demo';
$port = '3306';
$charset = 'utf8mb4';
$dsn = "mysql:dbname={$dbname};host={$host};port={$port};charset={$charset};";
$user = 'root';
$password = 'root';

global $dao;
try {
	$dao = new PDO($dsn, $user, $password);
	echo "DB接続成功<br>";
} catch (PDOException $e) {
	echo "DB接続失敗<br> " . $e->getMessage() . "\n";
	exit();
}



$param = $_GET;
if(empty($param['tbl_name'])) $param['tbl_name'] = 'users';
if(empty($param['limit'])) $param['limit'] = 4;
if(empty($param['where'])) $param['where'] = null;


echo "<div style='float:left;width:20%;overflow:scroll'>";
$tbls = getTbls($dao, $param);
foreach ($tbls as $tbl_name){
	echo "<a href='?tbl_name={$tbl_name}'>{$tbl_name}</a><br>";
}
echo "</div>";
echo "<div style='float:left;width:80%;padding-left:10px'>";



	

$data = getData($param);
	
$data = filterData($data);
	
createHtmlTable($data);
echo '-------------------下記・詳細---------------------------<br>';
createHtmlTableDtl($data);
	


echo '-----------全フィールド情報を表示 | フィールドの仕様頻度も表示する-----';
showAllFieldInfo();


// 全フィールド情報を表示 | フィールドの仕様頻度も表示する
function showAllFieldInfo(){
	$tbls = getTbls($dao, $param);
	
	$tbl_count = count($tbls);
	echo 'テーブル数:' . $tbl_count . '<br>';
	$allFields = [];
	
	foreach($tbls as $tbl_name){
		$fData = getFieldInfoData(['tbl_name'=>$tbl_name]);
		foreach($fData as $fEnt){
			$field = $fEnt['Field'];
			if(!isset($allFields[$field])){
				$allFields[$field] = 1;
			}else{
				$allFields[$field] ++;
			}
		}
		
	}
	
	arsort($allFields); // 降順ソート
	
	echo "<table class='table'><thead><tr><td>フィールド名</td><td>頻度</td></tr></thead><tbody>";
	foreach($allFields as $field => $val){
		echo "<tr><td>{$field}</td><td>{$val}</td></tr>";
	}
	echo "</tbody></table>";
	
	
}

	
// } catch (PDOException $e) {
// 	exit('データベース接続失敗。'.$e->getMessage());
// }



/**
 * SQLを実行してデータを取得する
 * @return boolean|PDOStatement[][]
 */
function getData0( $sql){
	global $dao;
	$stmt = $dao->query($sql);
	if($stmt === false) {
		var_dump('SQLエラー→' . $sql);
		return false;
	}
	
	$data = [];
	foreach ($stmt as $row) {
		$ent = [];
		foreach($row as $key => $value){
			if(!is_numeric($key)){
				$ent[$key] = $value;
			}
		}
		$data[] = $ent;
	}
	
	return $data;
}

function filterData($data){
	$data2 = [];
	foreach($data as $ent){
		unset($ent[0]);
		unset($ent[1]);
		unset($ent[2]);
		unset($ent[3]);
		unset($ent[4]);
		unset($ent[5]);
		unset($ent[6]);
		unset($ent[7]);
		unset($ent[8]);
		unset($ent['Collation']);
		unset($ent['Default']);
		unset($ent['Extra']);
		unset($ent['Privileges']);

		$data2[] = $ent;
	}
	return $data2;
}





function createHtmlTable($data){
	
	$keys = array_keys($data[0]);
	$head_html = "<th>{$keys[0]}</th><th>{$keys[4]}</th>";
	
	$body_html = '';
	foreach($data as $ent){
		$body_html .= "<tr><td>{$ent['Field']}</td><td>{$ent['Comment']}</td></tr>'";
	}
	
	$html = "
		<table border='1'>
			<thead><tr>{$head_html}</tr></thead>
			<tbody>{$body_html}</tbody>
		</table>
	";
	
	echo $html;
}



function createHtmlTableDtl($data){
	global $dao;
	$keys = array_keys($data[0]);
	$head_html = "<th>" . implode("</th><th>",$keys) . "</th>";
	
	$body_html = '';
	foreach($data as $ent){
		$body_html .= "<tr><td>" . implode('</td><td>',$ent) . "</td></tr>'";
	}
	
	$html = "
		<table border='1'>
			<thead><tr>{$head_html}</tr></thead>
			<tbody>{$body_html}</tbody>
		</table>
	";
	
	echo $html;
}



function getData ($param){
	global $dao;
	$tbl_name = $param['tbl_name'];
	$limit = $param['limit'];
	
	// フィールドデータを取得する
	$data = getFieldInfoData($param);
	
	// データを取得する
	$data2 = getData2($param);
	
	// マージ
	$data = mergeData($data, $data2);

	return $data;
}

// マージ
function mergeData($data, $data2){
	global $dao;
	if(empty($data2)) return $data;
	
	$row_count = count($data2);
	
	// 	データをループ（エンティティ）
	foreach($data as &$ent){
		// 	エンティティからフィールドを取得する
		$field = $ent['Field'];
		
		// 	行数ループ
		for($row_no=0; $row_no<$row_count; $row_no++){
 			// 	行とフィールドを指定してデータ2から値2を取得する
 			$value2 = $data2[$row_no][$field];
 			$row_field = 'row' . $row_no;
 			$ent[$row_field] = $value2;// 	エンティティに行キーを指定して値2を取得する
		}

	}
	unset($ent);
	
	return $data;
}



function getData2($param){
	global $dao;
	$tbl_name = $param['tbl_name'];
	$limit = $param['limit'];
	$id_field = $param['limit'];
	$where = $param['where'];
	
	
	$sql="SELECT * FROM {$tbl_name} LIMIT {$limit}";
	if(!empty($where)){
		$sql = "
		SELECT * FROM {$tbl_name}
			WHERE {$where} 
			LIMIT {$limit}
		";
	}
	
	$stmt = $dao->query($sql);
	$data = [];
	foreach ($stmt as $row) {
		$data[] = $row;
	}
	return $data;
}


function getFieldInfoData($param){
	global $dao;
	$tbl_name = $param['tbl_name'];

	$sql="SHOW FULL COLUMNS FROM {$tbl_name}";
	$stmt = $dao->query($sql);
	$data = [];
	foreach ($stmt as $row) {
		$data[] = $row;
	}
	return $data;
}

function debug($val){
	echo '<pre>';
	var_dump($val);
	echo '</pre>';
}


function getTbls($dao, $param){
	global $dao;
	$sql="SHOW TABLES";
	$stmt = $dao->query($sql);
	$tbls = [];
	foreach ($stmt as $row) {
		$tbls[] = $row[0];
	}
	return $tbls;
}

echo "</div>";













?>
</div><!-- content -->
<div id="footer">(C) <a href="http://amaraimusi.sakura.ne.jp/" target="blank">kenji uehara</a> 2020-12-7</div>
</body>
</html>