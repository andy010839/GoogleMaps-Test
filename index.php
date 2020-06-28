<?PHP
//引入檔案區
include "function.php";
include "setup.php";
@session_start();

$google = false;//判斷此頁面要不要讀取地圖
//流程控制
switch($_REQUEST['op']) {
	case "top_page":	//首頁
		$google = true;
		$main_content = top_page();
		break;
	case "add_house_form":	//新增表單
		$main_content = add_house_form($_SESSION['id']);
		break;
	case "upload_result":	//新增結果
		$main_content = upload_result($_REQUEST['upload'], $_REQUEST['city'],$_REQUEST['hometown'], $_REQUEST['address'], $_REQUEST['latitude'], $_REQUEST['longitude']);
		break;		
	case "search_form":		//搜尋表單
		$main_content = search_form();
		break;
	case "search_result":	//搜尋結果
		$google = true;
		$main_content = search_result($_REQUEST['search'],$_REQUEST['hometown']);
		break;
	case "management_search_result"://管理員專用搜尋結果
		$google = true;
		$main_content = management_search_result($_REQUEST['search'],$_REQUEST['hometown']);
		break;
	case "house_page":		//顯示單一房屋
		$google = true;
		$main_content = house_page($_REQUEST['the_house_id']);
		break;
	case "delete_a_house":
		$main_content = delete_a_house($_REQUEST['sn']);
		break;
	case "edit_page":		//修改表單
		$main_content = edit_page($_REQUEST['edit_ID']);
		break;
	case "edit_result":		//修改結果
		$main_content = edit_result($_REQUEST['edit'], $_REQUEST['city'],$_REQUEST['hometown'], $_REQUEST['address'], $_REQUEST['latitude'], $_REQUEST['longitude']);
		break;
	case "delete_result":	//刪除結果
		$main_content = delete_result($_REQUEST['delete']);
		break;
	case "predict_page":	//房價預測
		$main_content = predict_page($_SESSION['id']);
		break;
	case "reset_predict":	//更新預測資料庫
		$main_content = reset_predict();
		break;
	case "predict_result":	//預測結果
		$google = true;
		$main_content = predict_result($_REQUEST['predict'],$_REQUEST['city'],$_REQUEST['hometown']);
		break;
	case "register_form":	//註冊表單
		$main_content = register_form($_SESSION['id']);
		break;
	case "login_form":		//登入表單
	 	$main_content = login_form();
		break;
	case "register":		//註冊動作
		register($_POST['reg']);
		$main_content = register_success();
//		header("location: {$_SERVER['PHP_SELF']}");	//加了這一行反而爆  先不加了= =
		break;
	case "login":			//登入
		check_user($_POST["id"],$_POST["passwd"],true);
		$main_content = top_page($_SESSION['id']);
		break;
	case "logout":		//登出
		$google = true;
		logout();
		$main_content = top_page();	
		break;
	default:
		$google = true;
		$main_content = top_page();
		break;
}
//每一動作都在主顯示區統一輸出呈現
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset = UTF-8" />
<link rel="stylesheet" type="text/css" media="screen" href="style.css" />
<title>MAP式房屋查詢系統</title>
<script src="http://maps.google.com/maps?file=api&v=2&key=ABQIAAAAEqnQIkVg5pazaeO6vBrjkBT2yXp_ZAY8_ufC3CFXhHIE1NvwkxSZdQaY4DGjSyPw-fvLHbdatG-5pw" type="text/javascript"></script>
</head>
<body background="images/bg.gif" <?php if($google) echo "onload=\"load()\" onUnload=\"GUnload()\"";//依照目前頁面決定是否開啟地圖 ?>>
<div class="center_block">
	<img src="images/buildingBanner.jpg" class="logo" />
    <?php
		echo toolbar();
		echo $main_content;
	?>
</div>
<?php
if($_SESSION['id'] && $_SESSION['passwd'])
	echo "<div class=\"copyright\">目前的使用者為：{$_SESSION['id']}";
else
	echo "<div class=\"copyright\">Welcome Guest.</div>";
?>
</body>
</html>