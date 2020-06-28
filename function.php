<?PHP
//資料庫連結
function DB_var() {
	$DB['hostname'] = "localhost";//預設為localhost
	$DB['database'] = "houseweb";//填入mysql資料庫名稱
	$DB['username'] = "houseweb";//mysql 帳號
	$DB['password'] = "houseweb";//mysql密碼
	
	return $DB;
}

//工具列
function toolbar() {
	if(check_user($_SESSION["id"],$_SESSION["passwd"])){
	$main = "
		<div class=\"toolbar\">
		<a href = \"{$_SERVER['PHP_SELF']}?op=top_page\">首頁</a>
		<a href = \"{$_SERVER['PHP_SELF']}?op=add_house_form\">新增房屋</a>
		<a href = \"{$_SERVER['PHP_SELF']}?op=search_form\">房屋搜尋</a>
		<a href = \"{$_SERVER['PHP_SELF']}?op=predict_page\">新屋房價預測</a>
		<a href = \"{$_SERVER['PHP_SELF']}?op=register_form\">新增管理員</a>
		<a href='{$_SERVER['PHP_SELF']}?op=logout'>登出</a>
		</div>";
	}
	else {
	$main = "
		<div class=\"toolbar\">
		<a href = \"{$_SERVER['PHP_SELF']}?op=top_page\">首頁</a>
		<a href = \"{$_SERVER['PHP_SELF']}?op=search_form\">房屋搜尋</a>
		<a href = \"{$_SERVER['PHP_SELF']}?op=login_form\">管理員登入</a>
		</div>";
	}
	return $main;
}

//把上傳房屋的年月日給連起來變成一個字串
function date_join($year, $month, $day) {	
	if($year < 0) {	return $date;}
	else {
	switch (true) {
		case ($month == 1):
		case ($month == 3):
		case ($month == 5):
		case ($month == 7):
		case ($month == 8):
		case ($month == 10):
		case ($month == 12):
			if ($day<=31 and $day>=1)
				$date = $year . "-" . $month . "-" . $day;
			break;
		case ($month == 4):
		case ($month == 6):
		case ($month == 9):
		case ($month == 11):
			if ($day<=30 and $day>=1)
				$date = $year . "-" . $month . "-" . $day;
			break;
		case ($month == 2  and $day<=29 and $day>=1):
			if( $year%400 == 0 || ($year%100 != 0 && $year%4 == 0) )
				$date = $year . "-" . $month . "-" . $day;
			else 
				$date = $year . "-" . $month . "-" . $day;
			break;
		default:
			$date = "";
	}
	return $date;
	}	
}
/****************************************二階下拉式選單******************************************/
function dynamic_address($form_ID ="",$select2 = "") {
//資料庫連結
	$DB = DB_var();
	$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
	mysql_select_db($DB['database'], $link);	
	//將地區資訊從PHP讀出存入java script	
	$main = "<script language=\"javascript\">\n";
	
	$sql = "SELECT * FROM city";//取出全部鄉鎮市
	$result = mysql_query($sql, $link);
	
	$main .= "the_city = new Array();\n";//建立鄉鎮市陣列
	while($list = mysql_fetch_assoc($result)){
		 $main .= "the_city[{$list['city_ID']}] = [\"{$list['city_name']}\"];\n";
	}
	
	$sql = "SELECT * FROM hometown";//取出全部鄉鎮市
	$result = mysql_query($sql, $link);
	
	$main .= "the_hometown = new Array();\n";//建立鄉鎮市陣列
	while($list = mysql_fetch_assoc($result)){
		 $main .= "the_hometown[{$list['hometown_ID']}] = [\"{$list['hometown_name']}\",\"{$list['city_ID']}\"];\n";
	}
	
	//縣市onchange後的動作
	$main .= "
	function renew(index){

		document.{$form_ID}.{$select2}.options[0]=new Option(\"請選擇\",0);
		document.{$form_ID}.{$select2}.length=1;
		
		if(index != 0) {
			var j=1;
			for(var i=1;i<=the_hometown.length;i++){
				if(the_hometown[i][1] == index){
					document.{$form_ID}.{$select2}.options[j]= new Option(the_hometown[i][0],i);
					j++;
				}
			}
			document.{$form_ID}.{$select2}.length= --j ;
		}
	}
	";

	$main .= "</script>";
	
	return $main;
}

//****************************************呼叫Google Maps******************************************//
function call_maps($type = "", $latitude = array(), $longitude = array(), $information = array()){
	if($type == "one") {//顯示單一地點
	$main = "
		<script type=\"text/javascript\">
   	 	function load() {
    		if (GBrowserIsCompatible()) {
       			var map = new GMap2(document.getElementById(\"map\"));
       			map.setCenter(new GLatLng({$latitude}, {$longitude}), 17); //改成你要的經緯度,後面的13是預設顯示大小
       			map.addControl(new GSmallMapControl()); //小型地圖控制項
      			map.addControl(new GMapTypeControl()); //轉換類型地圖控制項
//     	    	map.enableGoogleBar(); //左下Google地圖搜尋
       			var marker = new GMarker(new GLatLng({$latitude}, {$longitude})); //建立標籤的經緯度
       			map.addOverlay(marker); //顯示標籤
       			marker.openInfoWindowHtml(\"{$information}\"); //顯示對話框及文字
     		}
    	}
		</script>";
	}
	elseif($type == "multi") {
		$center_la;//平均值用
		$center_lo;
		
		$a = 0;
		for( $i = 0; $i < count($latitude); $i++ ) {//取得所有地點的共同中心
			$center_la += $latitude[$i];
			$center_lo += $longitude[$i];
			$a++;
		}
		$center_la = $center_la/$a;
		$center_lo = $center_lo/$a;
		
		
/*		$globalMAX = 0;//記錄全局最遠
		$localMAX = 0;//記錄本點與它點最遠
		$globalI = 0;//最遠的兩點
		$globalJ = 0;//
		for( $i = 0; $i < count($latitude); $i++ ) {
			for( $j = 1; $j < count($latitude); $j++ ) {
				if( sqrt($latitude[$i]-$latitude[$j]) )
			}
		}
	*/	
		$main = "
		<script type=\"text/javascript\">
   	 	function load() {
    		if (GBrowserIsCompatible()) {
       			var map = new GMap2(document.getElementById(\"map\"));
       			map.setCenter(new GLatLng({$center_la}, {$center_lo}), 12); //改成你要的經緯度,後面的13是預設顯示大小
       			map.addControl(new GSmallMapControl()); //小型地圖控制項
      			map.addControl(new GMapTypeControl()); //轉換類型地圖控制項";
		for($i = 0; $i < count($latitude); $i++) {
		$main .= "
       			var marker{$i} = new GMarker(new GLatLng(" .$latitude[$i]. "," .$longitude[$i]. ")); //建立標籤的經緯度
				map.addOverlay(marker{$i}); //顯示標籤
				GEvent.addListener(marker{$i}, \"click\", function() {
				//使用openInfoWindow函式
				marker{$i}.openInfoWindow(\"<div style='text-align:left;'>" .$information[$i]. "</div>\"); });
				";
		}
		$main .= "		
       		}
    	}
		</script>";
	}
	else {
	$main = "
		<script type=\"text/javascript\">
   	 	function load() {
    		if (GBrowserIsCompatible()) {
       			var map = new GMap2(document.getElementById(\"map\"));
       			map.setCenter(new GLatLng(25.0134798, 121.5416389), 15); //改成你要的經緯度,後面的13是預設顯示大小
       			map.addControl(new GSmallMapControl()); //小型地圖控制項
      			map.addControl(new GMapTypeControl()); //轉換類型地圖控制項
//     	    	map.enableGoogleBar(); //左下Google地圖搜尋
       			var marker = new GMarker(new GLatLng(25.0134798, 121.5416389)); //建立標籤的經緯度
       			map.addOverlay(marker); //顯示標籤
       			marker.openInfoWindowHtml(\"國立台灣科技大學</br>Google Maps結合資料庫應用\"); //顯示對話框及文字
     		}
    	}
		</script>";
	}
	return $main;
}
//抓取經緯度
function findLocation($form_ID ="")	{
	$main = "
	<script type=\"text/javascript\">
	function getLocation(city,hometown,address,original_location) {
		var find_address; 
		if(hometown == 0 && original_location !=\"\") {
			find_address = original_location + address;//下拉式選單未變動  地址為舊的
		}
		else {
			find_address = the_city[city] + the_hometown[hometown][0] + address;
		}
		geocoder = new GClientGeocoder();
		geocoder.getLocations(find_address, function(response) {
        	if (!response) {
            	alert('Google Maps 找不到該地址，無法顯示地圖！'); //如果Google Maps無法顯示該地址的警示文字
            } 
			else {
               	place = response.Placemark[0];
          		point = new GLatLng(place.Point.coordinates[1],
                   					place.Point.coordinates[0]);
				if(place.Point.coordinates[1]>=22 && place.Point.coordinates[1]<= 26 &&
				   place.Point.coordinates[0]>=118&& place.Point.coordinates[0]<=126) {
					document.{$form_ID}.latitude.value = place.Point.coordinates[1];
					document.{$form_ID}.longitude.value = place.Point.coordinates[0];
				}
				else {
					alert('Google Maps 找不到該地址，無法顯示地圖！'); //如果Google Maps無法顯示該地址的警示文字
				}
            }
        });
	}
	</script>";

	return $main;
}

//首頁
function top_page($the_id = "") {
	if(empty($the_id)) {	
		$main = call_maps("top_page");
		$main .= "<div id=\"map\" style=\"width: 640px; height: 400px;\"></div> <!--此為地圖顯示大小--></br>";
	}
	else {
		$main = "歡迎來到管理者介面</br></br>
				 <meta http-equiv=\"refresh\" content=\"1;url={$_SERVER['PHP_SELF']}?op=top_page\">";
	}
	return $main;
}

/****************************************管理員專用房屋新增******************************************/
//房屋新增功能
function add_house_form($the_id = ""){
	if(empty($the_id))
		return "請先登入管理員系統";
	
	//資料庫連結		
	$DB = DB_var();
	$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
	mysql_select_db($DB['database'], $link);
	$main = "";
	//讀入地址資訊
	$form_name ="upload_form";
	$hometown_name = "hometown";
	$main .= dynamic_address($form_name,$hometown_name);//動態下拉式選單用	
	$main .= findLocation($form_name);//取得經緯用	
	
	//新增表單區
	$main .= "
	<p  style=\"width:30%\">請輸入房屋資訊：</p>
	<form name=\"{$form_name}\" action=\"{$_SERVER['PHP_SELF']}\" method=\"post\" enctype=\"multipart/form-data\">
  	<table class=\"input_table\">";
	//縣市傳入
	$main .= "
	<tr>
  	<td class=\"col_title\">城市：</td>
  	<td><select name=\"city\" class=\"txt\" onchange=\" renew(this.selectedIndex); \">\n
	<option value=\"0\"> 請選擇</option>\n";
		
	$sql = "SELECT * FROM city";
	$result = mysql_query($sql, $link);
	
	while( $list = mysql_fetch_assoc($result) ){
		$main .= "<option value=\"{$list['city_ID']}\"> {$list['city_name']} </option>" . "\n";
	}

	$main .= "</select></td>";
	
	//鄉鎮市傳入
	$main .= "
  	<td class=\"col_title\">鄉鎮市區：</td>
  	<td><select name=\"{$hometown_name}\" class=\"txt\" >
	<option value=\"0\"> 請選擇</option></select></td></tr>";
	
	//路巷弄地址
	$main .= "
	<tr>
	<td class=\"col_title\">地址：</td>
	<td colspan=\"3\" class=\"col\"><INPUT type=\"text\" name=\"address\" value=\"\" size=\"40\" maxlength=\"40\"></td></tr>";
	
	//Google Maps經緯度
	$main .= "
	<tr>
	<td class=\"col_title\">經緯度：</td>
	<td colspan=\"2\">
	<INPUT type=\"text\" name=\"latitude\" value=\"\" readonly>
	<INPUT type=\"text\" name=\"longitude\" value=\"\" readonly></td>
	<td class=\"col\"><input type=\"button\" value=\"取得經緯度\" onClick=\"getLocation({$form_name}.city.value,{$form_name}.{$hometown_name}.value,{$form_name}.address.value)\">
	</td></tr>";
	
	//坪數
	$main .= "
	<tr>
	<td class=\"col_title\">坪數：</td>
	<td colspan=\"3\" class=\"col\">
	<INPUT type=\"text\" name=\"upload[ping]\" value=\"\" size=\"10\" maxlength=\"10\" align=\"texttop\" style=\"text-align:right\"> 坪 
	</td></tr>";
	
	//價位（單位：元）
	$main .= "
	<tr>
	<td class=\"col_title\">每坪單價：</td>
	<td colspan=\"3\" class=\"col\">
	<INPUT type=\"text\" name=\"upload[price_per_ping]\" value=\"\" size=\"10\" maxlength=\"10\" style=\"text-align:right\"> 元
	</td></tr>";
	
	//建造日期
	$main .= "
	<tr>
	<td class=\"col_title\">建造日期：</td>
	<td colspan=\"3\" class=\"col\"> 西元 
	<INPUT type=\"text\" name=\"upload[year]\" value=\"\" size=\"4\" maxlength=\"4\" style=\"text-align:right\"> 年
	<INPUT type=\"text\" name=\"upload[month]\" value=\"\" size=\"2\" maxlength=\"2\" style=\"text-align:right\"> 月
	<INPUT type=\"text\" name=\"upload[day]\" value=\"\" size=\"2\" maxlength=\"2\" style=\"text-align:right\"> 日
	</td></tr>";
	
	//房屋類型
	$main .= "
	<tr>
	<td class=\"col_title\">房屋類型：</td>
	<td colspan=\"3\" class=\"col\"><SELECT name=\"upload[house_type]\">
	<option value=\"不拘\">不拘</option>";
            
		$sql = "show columns from house like 'house_type'";
		$result = mysql_query($sql, $link);
	
		$enum = mysql_result($result,0,"type");
		$enum_arr = explode( "('", $enum );	
		$enum = $enum_arr[1];	
		$enum_arr = explode( "')" , $enum );
		$enum = $enum_arr[0]; 
		$enum_arr = explode( "','" , $enum );
		for ($i=0; $i<count($enum_arr); $i++) {
	    	$main .= "<option value=\"{$enum_arr[$i]}\">".$enum_arr[$i]."</option>";
		}
	$main .= "</SELECT></td></tr>";
	
	//車位
	$main .= "
	<tr>
	<td class=\"col_title\">有無車位：</td>
	<td colspan=\"3\" class=\"col\">
		<INPUT type=\"radio\" name=\"upload[parking_lot]\" value=\"有\">有
	    <INPUT type=\"radio\" name=\"upload[parking_lot]\" value=\"無\">無
	</td></tr>";
	
	//房屋圖片
	$main .= "
	<tr>
	<td class=\"col_title\">房屋圖片：</td>
	<td colspan=\"3\" class=\"col\">
	<input TYPE=\"file\" name=\"picture\" size=\"35\">
	</td></tr>";

	//備註
	$main .= "
	<tr>
	<td class=\"col_title\">備註：</td>
	<td colspan=\"3\" class=\"col\">
	<textarea name=\"upload[note]\" value\"\"></textarea>
	</td></tr>";
		
	//FORM結尾
  	$main .= "</table>
	<input type=\"hidden\" name=\"op\" value=\"upload_result\">
	<input type=\"submit\" value=\"新增房屋\" class=\"input_btn\">
	<INPUT type=\"reset\" value=\"重新整理\" class=\"input_btn\"></form>";
	
	return $main;
}

//房屋新增動作 UPLOAD_RESULT
function upload_result($upload = array(), $city, $hometown, $address, $latitude, $longitude) {

	$main = "";
	
	//把年月日連起來變成字串
	$house_built_date = date_join($upload['year'],$upload['month'],$upload['day']);

	if ($city == 0 or $hometown == 0 or $address == "" or $upload['ping'] == "" or
		$upload['price_per_ping'] == "" or $house_built_date == "" or $upload['parking_lot'] == "") {
			$main .= "資料不全，請輸入完整的資料</br></br>";
			$main .= "<form><input type=\"button\" value=\"回上一頁\" onClick=\"history.go(-1)\" class=\"input_btn\"></form>";
	}

	else {		
		//取得圖片名稱
		

		//資料庫連接
		$DB = DB_var();
		$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
		mysql_select_db($DB['database'], $link);

		$insert = "insert into house (city_ID, hometown_ID, address, ping, price_per_ping, house_built_date,
				house_type, parking_lot, latitude, longitude, note) 
				values ('{$city}', '{$hometown}', '{$address}',
					'{$upload['ping']}', '{$upload['price_per_ping']}', '$house_built_date',
					'{$upload['house_type']}', '{$upload['parking_lot']}', '{$latitude}', '{$longitude}', '{$upload['note']}')";

		mysql_query($insert, $link);

		//上傳圖片
		if($_FILES['picture']['name']){
			$new_sn = mysql_insert_id();
			$pic_name = $new_sn . "_" . $_FILES['picture']['name'];
			move_uploaded_file($_FILES['picture']['tmp_name'], _PIC_DIR.$pic_name);

			$edit_sql = "update house set picture = '{$pic_name}' where house_ID = {$new_sn}";
			mysql_query($edit_sql, $link);
		}
		$main .= "房屋上傳成功</br></br>
				  <meta http-equiv=\"refresh\" content=\"1;url={$_SERVER['PHP_SELF']}?op=add_house_form\">";
	}

	return $main;

}

/****************************************房屋資料的查詢（以及管理員專用的修改刪除）******************************************/
//房屋查詢表單
function search_form() {
	//清除查詢結果殘留SESSION
	unset($_SESSION['ID_arr']);
	//資料庫連接
	$DB = DB_var();
	$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
	mysql_select_db($DB['database'], $link);
	$main = "";
	//讀入地址資訊
	$form_name = "search_form";
	$hometown_name = "hometown";
	$main .= dynamic_address($form_name,$hometown_name);

	//查詢表單區
	$main .= "
	<p  style=\"width:30%\">請選擇查詢條件：</p>
  	<form name=\"{$form_name}\" action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">
  	<table class=\"input_table\">";
	//縣市傳入
	$main .= "
	<tr>
  	<td class=\"col_title\">城市：</td>
  	<td><select name=\"search[city]\" class=\"txt\" onchange=\" renew(this.selectedIndex); \">\n
	<option value=\"0\"> 請選擇</option>\n";
		
	$sql = "SELECT * FROM city";
	$result = mysql_query($sql, $link);
	
	while( $list = mysql_fetch_assoc($result) ){
		$main .= "<option value=\"{$list['city_ID']}\"> {$list['city_name']} </option>" . "\n";
	}

	$main .= "</select></td>";
	
	//鄉鎮市傳入
	$main .= "
  	<td class=\"col_title\">鄉鎮市區：</td>
  	<td><select name=\"{$hometown_name}\" class=\"txt\" >
	<option value=\"0\"> 請選擇</option></select></td></tr>";
	
	//路巷弄地址
	$main .= "
	<tr>
	<td class=\"col_title\">地址：</td>
	<td colspan=\"3\" class=\"col\">
	<INPUT type=\"text\" name=\"search[address]\" size=\"40\" maxlength=\"40\">
	</td></tr>";
	
	//坪數
	$main .= "
	<tr>
	<td class=\"col_title\">坪數：</td>
	<td colspan=\"3\" class=\"col\">
	<INPUT type=\"text\" name=\"search[ping]\" value=\"0\" size=\"10\" maxlength=\"10\" style=\"text-align:right\"> 坪 
    <SELECT name=\"search[ping_dimension]\">
	 	<option value=\"以上\">以上</option>
	 	<option value=\"以下\">以下</option>
	 	</SELECT>
	</td></tr>";
	
	//價位（單位：萬）
	$main .= "
	<td class=\"col_title\">總價：</td>
	<td colspan=\"3\" class=\"col\">
	<INPUT type=\"text\" name=\"search[total_price]\" value=\"0\" size=\"10\" maxlength=\"10\" style=\"text-align:right\"> 萬
    <SELECT name=\"search[tp_dimension]\">
	 	<option value=\"以上\">以上</option>
	 	<option value=\"以下\">以下</option>
	</SELECT>
	</td></tr>";
	
	//屋齡
	$main .= "
	<td class=\"col_title\">屋齡：</td>
	<td colspan=\"3\" class=\"col\">
	<INPUT type=\"text\" name=\"search[house_age]\" value=\"0\" size=\"10\" maxlength=\"10\" style=\"text-align:right\"> 年
    <SELECT name=\"search[hbd_dimension]\">
		<option value=\"以下\" selected>以上</option>
		<option value=\"以上\">以下</option>
	</SELECT>
	</td></tr>";
	
	//房屋類型
	$main .= "
	<td class=\"col_title\">房屋類型：</td>
	<td colspan=\"3\" class=\"col\">
	<SELECT name=\"search[house_type]\">
	<option value=\"不拘\">不拘</option>";
            
		$sql = "show columns from house like 'house_type'";
		$result = mysql_query($sql, $link);
	
		$enum = mysql_result($result,0,"type");
		$enum_arr = explode( "('", $enum );	
		$enum = $enum_arr[1];	
		$enum_arr = explode( "')" , $enum );
		$enum = $enum_arr[0]; 
		$enum_arr = explode( "','" , $enum );
		for ($i=0; $i<count($enum_arr); $i++) {
	    	$main .= "<option value=\"{$enum_arr[$i]}\">".$enum_arr[$i]."</option>";
		}
	$main .= "</SELECT></td></tr>";
	
	//車位
	$main .= "
	<td class=\"col_title\">有無車位：</td>
	<td colspan=\"3\" class=\"col\">
		<INPUT type=\"radio\" name=\"search[parking_lot]\" value=\"有\">有
	    <INPUT type=\"radio\" name=\"search[parking_lot]\" value=\"無\">無
	</td></tr>";
	
	//是否售出
	if($_SESSION['id']) {
		$main .= "
		<td class=\"col_title\">已成交：</td>
		<td colspan=\"3\" class=\"col\">
			<INPUT type=\"radio\" name=\"search[is_deal]\" value=\"是\">是
	   	 	<INPUT type=\"radio\" name=\"search[is_deal]\" value=\"否\">否
		</td></tr>";	
	}
		
	//FORM結尾
  	$main .= "</table>";
	if($_SESSION['id']) {
		$main .= "<input type=\"hidden\" name=\"op\" value=\"management_search_result\">";
	}
	else {
		$main .= "<input type=\"hidden\" name=\"op\" value=\"search_result\">";
	}
	$main .= "<input type=\"submit\" value=\"開始查詢\" class=\"input_btn\">
			  <INPUT type=\"reset\" value=\"重新整理\" class=\"input_btn\"></form>";

	return $main;
}

//以上以下轉換為數學符號函式
function dimension($d){
  if ($d == "以上")
    return ">=";
  else
    return "<=";
}
	
//查詢結果
function search_result($search = array(),$hometown) {
//資料庫連接
	$DB = DB_var();
	$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
	mysql_select_db($DB['database'], $link);

	//SQL語法  根據輸入的搜尋條件改變 WHERE 長度
	$sql = "SELECT house.*, city.city_name, hometown.hometown_name, year(house_built_date) as house_built_year 
			FROM house 
			INNER JOIN (hometown 
						INNER JOIN city 
						USING(city_ID))
			USING(hometown_ID)
			WHERE ";
	//搜尋條件		
	if($search['city'] != 0) {	//縣市
		$sql .= " house.city_ID = {$search['city']} and ";
	}
	if($hometown != 0) {	//鄉鎮市
		$sql .= " house.hometown_ID = {$hometown} and ";
	}
	if($search['address'] != " ") {	//地址
		$sql .= " house.address like '%{$search['address']}%' and ";
	}	
	if($search['house_type'] != "不拘") {	//房屋類型 
		$sql .= " house.house_type = '". $search['house_type'] ."' and ";
	}
	if($search['parking_lot'] != "" ) {	//車位
		$sql .= " house.parking_lot = '{$search['parking_lot']}' and ";
	}
	if($search['house_age'] != "") {	//屋齡
		$house_built_year = date("Y") - $search['house_age'];
	  	$hbd_dimension = dimension($search['hbd_dimension']);
		$sql .= " YEAR(house_built_date) {$hbd_dimension} {$house_built_year} and ";
	}
	if($search['ping'] != "") {	//坪數
		$ping_dimension = dimension($search['ping_dimension']);
		$sql .= " ping {$ping_dimension} {$search['ping']} and ";
	}
	if($search['total_price'] != "") {	//總價
		$total_price = $search['total_price']*10000;
		$tp_dimension = dimension($search['tp_dimension']);
		$sql .= " ping*price_per_ping {$tp_dimension} {$total_price} and ";
	}
	
	
	$sql .= " is_deal = '否' order by house.hometown_ID";
	$result = mysql_query($sql, $link);

	$main .= "
	<div id=\"map\" style=\"width: 640px; height: 400px\"></div> <!--此為地圖顯示大小--></br>
	<form name=\"search_result\" action=\"{$_SERVER['PHP_SELF']}\" method=\"get\">
	<table border=\"1\" class=\"list\">
	<tr align=\"center\">
	<th>預覽圖</th>
	<th>地址</th>
	<th>類型</th>
	<th>車位</th>
	<th>屋齡</th>
	<th>坪數</th>
	<th>每坪價格</th>
	<th>總價</th>
	</tr>";
	
	//google maps用途的經緯度陣列
	$latitude = array();
	$longitude = array();
	$ingormation = array();
	
	while( $list = mysql_fetch_assoc($result) ){		
		$house_built_year = date(Y) - $list['house_built_year'];//計算屋齡：今年年份減建造年份
		$price_per_ping = round($list['price_per_ping']/10000, 2);//每坪價格取到萬位，小數只留兩位
		$total_price = round($list['ping']*$price_per_ping, 2);//算總價
		$pic = (empty($list['picture']))?_PIC_DIR."none.JPG":_PIC_DIR.$list['picture'];//抓圖
		
		//將每一筆讀到的經緯資料存入陣列
		if($list['latitude'] != 23.69781 and $list['longitude'] != 120.960515) {
		$latitude[] = $list['latitude'];
		$longitude[] = $list['longitude'];
		$information[] = "<img src='{$pic}' align='right' class='map_show_pic' \\>".
						 "{$list['city_name']}{$list['hometown_name']}{$list['address']}</br>".
						 "類型：{$list['house_type']}</br>".
						 "車位：{$list['parking_lot']}</br>".
						 "屋齡：{$house_built_year}年</br>".
						 "坪數：{$list['ping']}坪</br>".
						 "總價：{$total_price}萬</br>".
						 "<a href = '{$_SERVER['PHP_SELF']}?op=house_page&the_house_id={$list['house_ID']}'>查看此筆房屋</a>";		
		}
		//將搜尋結果以表格方式顯示在主畫面區
		$main .= "
		<tr class=\"view\">
			<td align=\"center\"><a href = \"{$_SERVER['PHP_SELF']}?op=house_page&the_house_id={$list['house_ID']}\">
			<img src=\"{$pic}\" class=\"show_pic\" \\></a></td>
			<td><a href = \"{$_SERVER['PHP_SELF']}?op=house_page&the_house_id={$list['house_ID']}\">	
				{$list['city_name']}{$list['hometown_name']}{$list['address']}</a></td>
			<td>{$list['house_type']}</td>
			<td>{$list['parking_lot']}</td>
			<td>{$house_built_year}年</td>
			<td>{$list['ping']}坪</td>
			<td>{$price_per_ping}萬</td>
			<td>{$total_price}萬</td>
		</tr>" . "\n";
	}	
	$main .= "</table></br>";			 
	//GOOGLE MAPS 
	if(empty($latitude) or empty($longitude)) 
		$main .= call_maps()."對不起，沒有找到匹配結果。</br></br>";			
	else
		$main .= call_maps("multi",$latitude,$longitude,$information);				
	
	return $main;
}

//管理員專用房屋查詢結果
function management_search_result($search = array(),$hometown) {
	//資料庫連接
	$DB = DB_var();
	$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
	mysql_select_db($DB['database'], $link);
	
	//SQL語法  根據輸入的搜尋條件改變 WHERE 長度
	$sql = "SELECT house.*, city.city_name, hometown.hometown_name, year(house_built_date) as house_built_year 
			FROM house 
			INNER JOIN (hometown 
						INNER JOIN city 
						USING(city_ID))
			USING(hometown_ID)
			WHERE ";
	//修改返回原查詢結果
	if($_SESSION['ID_arr'][0] != "") {
		$i = 0;
		while($_SESSION['ID_arr'][$i] != "") {
			$sql .= " house.house_ID = '" .$_SESSION['ID_arr'][$i]. "' or ";
			$i++;
		}	
		$sql .= " 0 and ";
	}
	else {
	//搜尋條件		
	if($search['city'] != 0) {	//縣市
		$sql .= " house.city_ID = {$search['city']} and ";
	}
	if($hometown != 0) {	//鄉鎮市
		$sql .= " house.hometown_ID = {$hometown} and ";
	}
	if($search['address'] != " ") {	//地址
		$sql .= " house.address like '%{$search['address']}%' and ";
	}	
	if($search['house_type'] != "不拘") {	//房屋類型 
		$sql .= " house.house_type = '". $search['house_type'] ."' and ";
	}
	if($search['parking_lot'] != "" ) {	//車位
		$sql .= " house.parking_lot = '{$search['parking_lot']}' and ";
	}
	if($search['house_age'] != "") {	//屋齡
		$house_built_year = date("Y") - $search['house_age'];
	  	$hbd_dimension = dimension($search['hbd_dimension']);
		$sql .= " YEAR(house_built_date) {$hbd_dimension} {$house_built_year} and ";
	}
	if($search['ping'] != "") {	//坪數
		$ping_dimension = dimension($search['ping_dimension']);
		$sql .= " ping {$ping_dimension} {$search['ping']} and ";
	}
	if($search['total_price'] != "") {	//總價
		$total_price = $search['total_price']*10000;
		$tp_dimension = dimension($search['tp_dimension']);
		$sql .= " ping*price_per_ping {$tp_dimension} {$total_price} and ";
	}
	if($search['is_deal'] != "" ) {	//成交
		$sql .= " house.is_deal = '{$search['is_deal']}' and ";
	}
	}
	$sql .= " 1 order by house.hometown_ID";
	$result = mysql_query($sql, $link);

	$main .= "
	<div id=\"map\" style=\"width: 640px; height: 400px\"></div> <!--此為地圖顯示大小--></br>
	<form name=\"search_result\" action=\"{$_SERVER['PHP_SELF']}\" method=\"get\">
	<table border=\"1\" class=\"list\">
	<tr align=\"center\">
	<th nowrap>修改</th>
	<th>刪除</th>
	<th>預覽圖</th>
	<th>地址</th>
	<th>類型</th>
	<th>車位</th>
	<th>屋齡</th>
	<th>坪數</th>
	<th>每坪價格</th>
	<th>總價</th>
	<th>成交</th>
	</tr>";
	
	//google maps用途的經緯度陣列
	$latitude = array();
	$longitude = array();
	$information = array();
	$_SESSION['ID_arr'] = array();
	
	while( $list = mysql_fetch_assoc($result) ){		
		$house_built_year = date(Y) - $list['house_built_year'];//計算屋齡：今年年份減建造年份
		$price_per_ping = round($list['price_per_ping']/10000, 2);//每坪價格取到萬位，小數只留兩位
		$total_price = round($list['ping']*$price_per_ping, 2);//算總價
		$pic = (empty($list['picture']))?_PIC_DIR."none.JPG":_PIC_DIR.$list['picture'];//抓圖
		$_SESSION['ID_arr'][] = $list['house_ID'];//記錄ＩＤ以供修改返回查詢結果

		//將每一筆讀到的經緯資料存入陣列  若讀到預設值則不存入
		if($list['latitude'] != 23.69781 and $list['longitude'] != 120.960515) {
		$latitude[] = $list['latitude'];
		$longitude[] = $list['longitude'];
		$information[] = "<img src='{$pic}' align='right' class='map_show_pic' \\>".
						 "{$list['city_name']}{$list['hometown_name']}{$list['address']}</br>".
						 "類型：{$list['house_type']}</br>".
						 "車位：{$list['parking_lot']}</br>".
						 "屋齡：{$house_built_year}年</br>".
						 "坪數：{$list['ping']}坪</br>".
						 "總價：{$total_price}萬</br>".
						 "成交：{$list['is_deal']}</br>".
						 "<a href = '{$_SERVER['PHP_SELF']}?op=house_page&the_house_id={$list['house_ID']}'>查看此筆房屋</a>";
		}		
		
		//將搜尋結果以表格方式顯示在主畫面區（ＧＯＯＧＬＥ　ＭＡＰＳ方式待補）
		$main .= "
		<tr class=\"view\">
			<td class=\"func\"><a href = \"{$_SERVER['PHP_SELF']}?op=edit_page&edit_ID={$list['house_ID']}\">修改</a></td>
			<td><INPUT type=\"checkbox\" name=\"delete[]\" value=\"{$list['house_ID']}\"></td>
			<td align=\"center\">
				<a href = \"{$_SERVER['PHP_SELF']}?op=house_page&the_house_id={$list['house_ID']}\"><img src=\"{$pic}\" class=\"show_pic\" \\></a></td>
			<td><a href = \"{$_SERVER['PHP_SELF']}?op=house_page&the_house_id={$list['house_ID']}\">	
				{$list['city_name']}{$list['hometown_name']}{$list['address']}</a></td>
			<td>{$list['house_type']}</td>
			<td>{$list['parking_lot']}</td>
			<td>{$house_built_year}年</td>
			<td>{$list['ping']}坪</td>
			<td>{$price_per_ping}萬</td>
			<td>{$total_price}萬</td>
			<td>{$list['is_deal']}</td>
		</tr>" . "\n";
	}	
	$main .= "</table>";

	$main .= "<input type=\"hidden\" name=\"op\" value=\"delete_result\">
			<input type=\"submit\" value=\"刪除\" class=\"input_btn\">
			<input type=\"reset\" value=\"取消刪除\" class=\"input_btn\"></form></br>";
	
	//GOOGLE MAPS 
	if(empty($latitude) or empty($longitude)) 
		$main .= call_maps()."對不起，沒有找到匹配結果。</br></br>";			
	else
		$main .= call_maps("multi",$latitude,$longitude,$information);			
			
	return $main;
}

//顯示單一一筆房屋
function house_page($house_ID = "") {
	if(empty($house_ID))
		return "查詢錯誤</br></br>";
	else {
	//資料庫連接
	$DB = DB_var();
	$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
	mysql_select_db($DB['database'], $link);
	//SQL語法
	$sql = "SELECT house.*, city.city_name, hometown.hometown_name, year(house_built_date) as house_built_year 
			FROM house 
			INNER JOIN (hometown 
						INNER JOIN city 
						USING(city_ID))
			USING(hometown_ID)
			WHERE house_ID = {$house_ID}";
	$result = mysql_query($sql, $link);
	$list = mysql_fetch_assoc($result);
	
	$information = $list['city_name'].$list['hometown_name'].$list['address'];
	$house_built_year = date(Y) - $list['house_built_year'];//計算屋齡：今年年份減建造年份
	$price_per_ping = round($list['price_per_ping']/10000, 2);//每坪價格取到萬位，小數只留兩位
	$total_price = $list['ping']*$price_per_ping;//算總價
	$pic = (empty($list['picture']))?_PIC_DIR."none.JPG":_PIC_DIR.$list['picture'];//抓圖
	$note = (empty($list['note']))?"一般用途房屋":$list['note'];
	//google maps
	$main = "<div id=\"map\" style=\"width: 640px; height: 400px\"></div> <!--此為地圖顯示大小--></br>";
	$main .= call_maps("one",$list['latitude'],$list['longitude'],$information);	
	//內容顯示
	$main .= "
	<table border=\"1\" class=\"list\" style=\"font-size:16px; text-align:center; \" >
	<tr><td colspan=\"3\" class=\"col_head_title\">詳細資料</td></tr>
	<tr>
	<td colspan=\"2\">{$location}</td>
		<td rowspan=\"5\" align=\"center\"><img src=\"{$pic}\" class=\"one_show_pic\"\\></td>
	</tr>
	<tr><td>{$list['ping']}坪</td><td>每坪{$price_per_ping}萬元</td></tr>
	<tr><td colspan=\"2\">總價{$total_price}萬元</td></tr>
	<tr><td colspan=\"2\">{$list['house_type']}{$list['parking_lot']}車位</td></tr>
	<tr><td colspan=\"2\">{$list['house_built_date']}建（屋齡{$house_built_year}年）</td></tr>
	<tr><td colspan=\"3\">{$note}</td></tr>";
	
	if($_SESSION['id']) {
		$main .= "
		<tr><td colspan=\"2\">已成交：{$list['is_deal']}</td>
		<td><input type=\"button\" value=\"修改\" onClick=\"location.href='{$_SERVER['PHP_SELF']}?op=edit_page&edit_ID={$list['house_ID']}';\" class=\"input_btn\">
		<input type=\"button\" value=\"刪除\" onClick=\"delete_data({$list['house_ID']})\" class=\"input_btn\">
		</td>
		</table></br>";
		
		$main .= "
		<script language=\"javascript\" type=\"text/javascript\">
		<!--
		function delete_data(sn) {
			var sure = window.confirm('確定刪除此筆資料？');	
			if(!sure) return;
			location.href = \"{$_SERVER['PHP_SELF']}?op=delete_a_house&sn=\" + sn;	
		}
		//-->
		</script>
		";
	}
	else {
		$main .= "</table></br><a href=\"mailto:B9630126@mail.ntust.edu.tw?subject=房屋意見詢問\">寄信詢問此間房屋</a>";
	}
	return $main;
	}
}

//刪除單一房屋
function delete_a_house($house_ID = "") {
	$main = "";
	if(empty($house_ID)) {
		$main .= "刪除失敗</br></br>";
		$main .= "<form><input type=\"button\" value=\"回上一頁\" onClick=\"history.go(-1)\" class=\"input_btn\"></form>";
	}
	//資料庫連接
	$DB = DB_var();
	$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
	mysql_select_db($DB['database'], $link);
	
	$sql = "DELETE FROM house WHERE house_ID = {$house_ID}";
	mysql_query($sql, $link);
	
	$main .= "刪除成功</br>
			  頁面將轉回原查詢結果</br></br>
			  <meta http-equiv=\"refresh\" content=\"1;url={$_SERVER['PHP_SELF']}?op=management_search_result\">";
	return $main;
}

//修改資料
function edit_page($edit_ID) {
	$main = "";

	//資料庫連結		
	$DB = DB_var();
	$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
	mysql_select_db($DB['database'], $link);

	//將房屋ID所指向的房屋資料抓出來
	$get_data = "select * from house where house_ID={$edit_ID}";
	$result = mysql_query($get_data, $link);
	$list = mysql_fetch_assoc($result);
	
	//把 $list 所抓出來的資料都存到變數裡面
	//會這樣做的原因是因為 $list['欄位名稱'] 沒辦法直接放進form裡面的value值
	$house_ID = $list['house_ID'];
	$city_ID = $list['city_ID'];
	$hometown_ID = $list['hometown_ID'];
	$address = $list['address'];
	$latitude = $list['latitude'];
	$longitude = $list['longitude'];
	$ping = $list['ping'];   
	$price_per_ping = $list['price_per_ping'];
	$house_type = $list['house_type'];
	$parking_lot = $list['parking_lot'];
	$note = $list['note'];
	$picture = $list['picture'];
	$is_deal = $list['is_deal'];

	//把房屋建造日期年月日拆字串
	$date = explode("-", $list['house_built_date']);
	$year = $date[0];
	$month = $date[1];
	$day = $date[2];

	//讀入地址資訊
	$form_name ="edit_page";
	$hometown_name = "hometown";
	$main .= dynamic_address($form_name,$hometown_name);//動態下拉式選單用	
	$main .= findLocation($form_name);//取得經緯用	
    
    $main .= "
	<p  style=\"width:30%\">請修改房屋資訊：</p>
	<form name=\"{$form_name}\" action=\"{$_SERVER['PHP_SELF']}\" method=\"post\" enctype=\"multipart/form-data\">
  	<table class=\"input_table\">";
	
	//原縣市資訊
	$sql = "SELECT city_name, hometown_name 
			FROM hometown 
			INNER JOIN city 
			USING(city_ID) WHERE city_ID = {$city_ID} and hometown_ID = {$hometown_ID}";
	$result = mysql_query($sql, $link);
	$original_location = mysql_fetch_assoc($result);//原設定
	$main .= "
	<tr>
  	<td class=\"col_title\">城市設定：</td>
	<td class=\"col\">{$original_location['city_name']}{$original_location['hometown_name']}</td>
	<td colspan=\"2\" class=\"col\">（欲更改請由下選擇）</td>
	<input type=\"hidden\" name=\"edit[original_city]\" value=\"{$city_ID}\">
	<input type=\"hidden\" name=\"edit[original_hometown]\" value=\"{$hometown_ID}\">";
	
	//縣市傳入
	$main .= "
	<tr>
  	<td class=\"col_title\">城市：</td>
  	<td><select name=\"city\" class=\"txt\" onchange=\" renew(this.selectedIndex); \">\n
	<option value=\"0\"> 請選擇</option>\n";
		
	$sql = "SELECT * FROM city";
	$result = mysql_query($sql, $link);
	
	while( $list = mysql_fetch_assoc($result) ){
		$main .= "<option value=\"{$list['city_ID']}\"> {$list['city_name']} </option>" . "\n";
	}

	$main .= "</select></td>";
	
	//鄉鎮市傳入
	$main .= "
  	<td class=\"col_title\">鄉鎮市區：</td>
  	<td><select name=\"{$hometown_name}\" class=\"txt\" >
	<option value=\"0\"> 請選擇</option></select></td></tr>";
	
	//路巷弄地址
	$main .= "
	<tr>
	<td class=\"col_title\">地址：</td>
	<td colspan=\"3\" class=\"col\">
	<INPUT type=\"text\" name=\"address\" value=\"{$address}\" size=\"40\" maxlength=\"40\">
	</td></tr>";
	
	//Google Maps經緯度
	$main .= "
	<tr>
	<td class=\"col_title\">經緯度：</td>
	<td colspan=\"2\">
	<INPUT type=\"text\" name=\"latitude\" value=\"{$latitude}\" readonly>
	<INPUT type=\"text\" name=\"longitude\" value=\"{$longitude}\" readonly></td>
	<td class=\"col\"><input type=\"button\" value=\"重新抓取\" onClick=\"getLocation({$form_name}.city.value,{$form_name}.{$hometown_name}.value,{$form_name}.address.value,'{$original_location['city_name']}{$original_location['hometown_name']}')\">
	</td></tr>";
	
	//坪數
	$main .= "
	<tr>
	<td class=\"col_title\">坪數：</td>
	<td colspan=\"3\" class=\"col\">
	<INPUT type=\"text\" name=\"edit[ping]\" value=\"{$ping}\" size=\"10\" maxlength=\"10\" align=\"texttop\" style=\"text-align:right\"> 坪 
	</td></tr>";
	
	//價位（單位：元）
	$main .= "
	<tr>
	<td class=\"col_title\">每坪單價：</td>
	<td colspan=\"3\" class=\"col\">
	<INPUT type=\"text\" name=\"edit[price_per_ping]\" value=\"{$price_per_ping}\" size=\"10\" maxlength=\"10\" style=\"text-align:right\"> 元
	</td></tr>";
	
	//建造日期
	$main .= "
	<tr>
	<td class=\"col_title\">建造日期：</td>
	<td colspan=\"3\" class=\"col\"> 西元 
	<INPUT type=\"text\" name=\"edit[year]\" value=\"{$year}\" size=\"4\" maxlength=\"4\" style=\"text-align:right\"> 年
	<INPUT type=\"text\" name=\"edit[month]\" value=\"{$month}\" size=\"2\" maxlength=\"2\" style=\"text-align:right\"> 月
	<INPUT type=\"text\" name=\"edit[day]\" value=\"{$day}\" size=\"2\" maxlength=\"2\" style=\"text-align:right\"> 日
	</td></tr>";
	
	//房屋類型
	$main .= "
	<tr>
	<td class=\"col_title\">房屋類型：</td>
	<td colspan=\"3\" class=\"col\"><SELECT name=\"edit[house_type]\">
	<option value=\"不拘\">不拘</option>";
            
		$sql = "show columns from house like 'house_type'";
		$result = mysql_query($sql, $link);
	
		$enum = mysql_result($result,0,"type");
		$enum_arr = explode( "('", $enum );	
		$enum = $enum_arr[1];	
		$enum_arr = explode( "')" , $enum );
		$enum = $enum_arr[0]; 
		$enum_arr = explode( "','" , $enum );
		for ($i=0; $i<count($enum_arr); $i++) {			
			$main .= "<option value=\"{$enum_arr[$i]}\" ";
				if ($enum_arr[$i] == $house_type)
					$main .= "selected";
			$main .= ">{$enum_arr[$i]}</option>";
		}
	$main .= "</SELECT></td></tr>";
	
	//車位＆成交
	$main .= "
	<tr>
	<td class=\"col_title\">有無車位：</td>
	<td class=\"col\">
		<INPUT type=\"radio\" name=\"edit[parking_lot]\" value=\"有\" ";
			if ($parking_lot == "有")
				$main .= "checked";
		$main .= ">有
		<INPUT type=\"radio\" name=\"edit[parking_lot]\" value=\"無\" ";
			if ($parking_lot == "無")
				$main .= "checked";
		$main .= ">無
	</td>
	<td class=\"col_title\">是否成交：</td>
	<td class=\"col\">
		<INPUT type=\"radio\" name=\"edit[is_deal]\" value=\"是\" ";
			if ($is_deal == "是")
				$main .= "checked";
		$main .= ">有
		<INPUT type=\"radio\" name=\"edit[is_deal]\" value=\"否\" ";
			if ($is_deal == "否")
				$main .= "checked";
		$main .= ">無
	</td></tr>";
	
	//房屋圖片
	$main .= "
	<tr>
	<td class=\"col_title\">房屋圖片：</td>
	<td colspan=\"3\" class=\"col\">
	<input TYPE=\"file\" name=\"picture\" size=\"35\">
	</td></tr>";

	//備註
	$main .= "
	<tr>
	<td class=\"col_title\">備註：</td>
	<td colspan=\"3\" class=\"col\">
	<textarea name=\"edit[note]\">$note</textarea>
	</td></tr>";
		
	//FORM結尾
  	$main .= "</table>
	<input type=\"hidden\" name=\"edit[house_ID]\" value=\"{$house_ID}\">
	<input type=\"hidden\" name=\"edit[picture]\" value=\"{$picture}\">
	<input type=\"hidden\" name=\"op\" value=\"edit_result\">
	<input type=\"submit\" value=\"修改資訊\" class=\"input_btn\">
	<INPUT type=\"reset\" value=\"重新整理\" class=\"input_btn\"></form>";

	return $main;
}

//修改資料結果
function edit_result($edit = array(), $city, $hometown, $address, $latitude, $longitude) {
	$main = "";
	
	//把年月日連起來變成字串
	$house_built_date = date_join($edit['year'],$edit['month'],$edit['day']);
	if($city == 0 and $hometown == 0 ) {//判斷是否有更改縣市地區
		$city = $edit['original_city'];
		$hometown = $edit['original_hometown'];
	}

	if ($city == 0 or $hometown == 0 or $address == "" or $edit['ping'] == "" or
		$edit['price_per_ping'] == "" or $house_built_date == "" or	$edit['parking_lot'] == "") {
			$main .= "資料不全，請輸入完整的資料</br></br>";
			$main .= "<form><input type=\"button\" value=\"回上一頁\" onClick=\"history.go(-1)\" class=\"input_btn\"></form>";
	}

	else {
		//資料庫連接
		$DB = DB_var();
		$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
		mysql_select_db($DB['database'], $link);

		//直接取之前的檔名並把新上傳覆蓋上來
		//此作法的好處是如果之前是有圖片的，而修改資料的時候沒上傳檔案，之前的檔案路徑不會不見
		if (!empty($edit['picture'])) {
			$pic_name = $edit['picture'];
		}
		if($_FILES['picture']['name'] != "") {
			$sn = $edit['house_ID'];
			$pic_name = $sn . "_" . $_FILES['picture']['name'];
			move_uploaded_file($_FILES['picture']['tmp_name'], _PIC_DIR.$pic_name);
		}
		
		$edit = "update house set city_ID='{$city}', hometown_ID='{$hometown}', address='{$address}',
				ping='{$edit['ping']}', price_per_ping='{$edit['price_per_ping']}',
				house_built_date='{$house_built_date}', house_type='{$edit['house_type']}',
				parking_lot='{$edit['parking_lot']}', latitude={$latitude}, longitude={$longitude}, note='{$edit['note']}', picture='{$pic_name}'
				where house_ID='{$edit['house_ID']}'";
		
		if (mysql_query($edit, $link))
			$main .= "資料修改成功</br>
					  頁面即將轉移回查詢結果</br></br>
					  <meta http-equiv=\"refresh\" content=\"1;url={$_SERVER['PHP_SELF']}?op=management_search_result\">";
	}
	return $main;
}

//刪除結果
function delete_result($delete = "") {
	if(empty($delete)) {
		$main = "未點選任何房屋進行刪除</br>
			 	頁面將轉回原查詢結果</br></br>
			 	<meta http-equiv=\"refresh\" content=\"1;url={$_SERVER['PHP_SELF']}?op=management_search_result\">";
	}
	else {
	$DB = DB_var();
	$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
	mysql_select_db($DB['database'], $link);

	//勾選要刪除的房屋我適用陣列存的
	//利用陣列的長度來刪除所有被勾選的房屋
	foreach ($delete as $delete_house) {
		$delete_sql ="delete from house where house_ID = {$delete_house}";
		$result = mysql_query($delete_sql, $link);
	}

	$main = "已將資料從資料庫中刪除</br>
			 頁面將轉回原查詢結果</br></br>
			 <meta http-equiv=\"refresh\" content=\"1;url={$_SERVER['PHP_SELF']}?op=management_search_result\">";
	}
	return $main;
}

/****************************************統計分類相關******************************************/
//新屋房價預測
function predict_page($the_id = "") {
	if(empty($the_id))
		return "請先登入管理員系統";
		
	//資料庫連結		
	$DB = DB_var();
	$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
	mysql_select_db($DB['database'], $link);
	//讀入地址資訊
	$form_name ="predict_form";
	$hometown_name = "hometown";
	$main = dynamic_address($form_name,$hometown_name);//讀取動態下拉式選單
				 
	$main .= "
	<p style=\"width:40%;\">請輸入欲預測的房屋資訊：</p>
	<form name=\"{$form_name}\" action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">
  	<table class=\"input_table\">";
	//縣市傳入
	$main .= "
	<tr>
  	<td class=\"col_title\">城市：</td>
  	<td><select name=\"city\" class=\"txt\" onchange=\" renew(this.selectedIndex); \">\n
	<option value=\"0\"> 請選擇</option>\n";
		
	$sql = "SELECT * FROM city";
	$result = mysql_query($sql, $link);
	
	while( $list = mysql_fetch_assoc($result) ){
		$main .= "<option value=\"{$list['city_ID']}\"> {$list['city_name']} </option>" . "\n";
	}
	$main .= "</select></td>";
	//鄉鎮市傳入
	$main .= "
  	<td class=\"col_title\">鄉鎮市區：</td>
  	<td><select name=\"{$hometown_name}\" class=\"txt\" >
	<option value=\"0\"> 請選擇</option></select></td></tr>";
	//建造日期
	$main .= "
	<tr>
	<td class=\"col_title\">建造日期：</td>
	<td colspan=\"3\" class=\"col\"> 西元 
	<INPUT type=\"text\" name=\"predict[year]\" value=\"\" size=\"4\" maxlength=\"4\" style=\"text-align:right\"> 年
	<INPUT type=\"text\" name=\"predict[month]\" value=\"\" size=\"2\" maxlength=\"2\" style=\"text-align:right\"> 月
	<INPUT type=\"text\" name=\"predict[day]\" value=\"\" size=\"2\" maxlength=\"2\" style=\"text-align:right\"> 日
	</td></tr>";
	//房屋類型
	$main .= "
	<tr>
	<td class=\"col_title\">房屋類型：</td>
	<td colspan=\"3\" class=\"col\"><SELECT name=\"predict[house_type]\">
	<option value=\"不拘\">不拘</option>";
            
		$sql = "show columns from house like 'house_type'";
		$result = mysql_query($sql, $link);
	
		$enum = mysql_result($result,0,"type");
		$enum_arr = explode( "('", $enum );	
		$enum = $enum_arr[1];	
		$enum_arr = explode( "')" , $enum );
		$enum = $enum_arr[0]; 
		$enum_arr = explode( "','" , $enum );
		for ($i=0; $i<count($enum_arr); $i++) {
	    	$main .= "<option value=\"{$enum_arr[$i]}\">".$enum_arr[$i]."</option>";
		}
	$main .= "</SELECT></td></tr>";
	
	//FORM結尾
  	$main .= "</table>
	<input type=\"hidden\" name=\"op\" value=\"predict_result\">
	<input type=\"button\" value=\"更新預測資料庫\" onClick=\"location.href='{$_SERVER['PHP_SELF']}?op=reset_predict';\" class=\"input_btn\" style=\"width:120px;\">
	<input type=\"submit\" value=\"開始預測\" class=\"input_btn\">
	<INPUT type=\"reset\" value=\"重新整理\" class=\"input_btn\"></form>";
	
	return $main;
}

//更新預測資料庫
function reset_predict() {
	//資料庫連結		
	$DB = DB_var();
	$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
	mysql_select_db($DB['database'], $link);
	
	//進行坪價的權重表
	for($i=0;$i<6;$i++){
		if($i == 5){
			$table_name = " price_" .($i*20). "up ";
			$range = " price_per_ping > " .($i*200000). "  and is_deal = '是' ";
		}
		else {
			$table_name = " price_" .($i*20). "to".(($i+1)*20);
			$range = " price_per_ping <= " .(($i+1)*200000). " and price_per_ping > " .($i*200000). "  and is_deal = '是' ";
		}
		
	//清空原資訊
	$sql = "DELETE FROM {$table_name}";
	mysql_query($sql, $link);
	//算出坪價0~20萬的總筆數
	$sql = "SELECT count(house_ID) as sum FROM house WHERE {$range} ";
	$result = mysql_query($sql, $link);
	$list = mysql_fetch_assoc($result);
	$sum = $list['sum'];
	//將新統計權重放入資料表～行政區域
	$sql = "SELECT hometown_ID, count(hometown_ID) as num FROM house WHERE {$range} GROUP BY hometown_ID";
	$result = mysql_query($sql, $link);
	$x = 1;
	while( $list = mysql_fetch_assoc($result) ){		
		$hometown_ID_sn = "hometown_ID_".$list['hometown_ID'];
		$weight = $list['num']/$sum;
		$update = "INSERT into {$table_name} ( sn , weight_name , weight) VALUES( '{$x}' , '{$hometown_ID_sn}' , '{$weight}')";
		mysql_query($update, $link);
		$x++;
	}
	//將新統計權重放入資料表～房屋類型
	$sql = "SELECT house_type, count(house_type) as num FROM house WHERE {$range} GROUP BY house_type";
	$result = mysql_query($sql, $link);
	while( $list = mysql_fetch_assoc($result) ){
		if($list['house_type'] === '公寓'){
			$type_name = "apartment";
		}
		if($list['house_type'] === '大樓'){
			$type_name = "building";
		}
		if($list['house_type'] === '社區'){
			$type_name = "community";
		}
		
		$house_type_sn = "house_type_".$type_name;
		$weight = $list['num']/$sum;
		$update = "INSERT into {$table_name} ( sn , weight_name , weight) VALUES( '{$x}' , '{$house_type_sn}' , '{$weight}')";
		mysql_query($update, $link);
		$x++;
	}
	//將新統計權重放入資料表～屋齡區間
	$sql = "SELECT (".date(Y)."-year(house_built_date)) as age FROM house WHERE {$range} ";
	$result = mysql_query($sql, $link);
	$a = 0; $b = 0; $c = 0;
	while( $list = mysql_fetch_assoc($result) ){
		if($list['age'] <= 15){
			$a++;
		}
		if($list['age'] > 15 && $list['age'] <= 30 ){
			$b++;
		}
		if($list['age'] > 30){
			$c++;
		}
	}
	for($k = 0;$k < 3;$k++){
		if($k==0){
		$house_age_sn = "house_age_lessthan15";
		$weight = $a/$sum;
		}
		if($k==1){
		$house_age_sn = "house_age_between15and30";
		$weight = $b/$sum;
		}
		if($k==2){
		$house_age_sn = "house_age_morethan30";
		$weight = $c/$sum;
		}
		$update = "INSERT into {$table_name} ( sn , weight_name , weight) VALUES( '{$x}' , '{$house_age_sn}' , '{$weight}')";
		mysql_query($update, $link);
		$x++;
	}
	}
	
	$main = "資料庫已更新完畢</br>
			 將返回原頁面</br></br>
			 <meta http-equiv=\"refresh\" content=\"1;url={$_SERVER['PHP_SELF']}?op=predict_page\">";

	return $main; 
}

function predict_result($predict = array(), $city, $hometown) {
	//資料庫連接
	$DB = DB_var();
	$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
	mysql_select_db($DB['database'], $link);
	
	//判斷歷史資料是否足以判斷
	$error = 0;
	$sql = "SELECT * FROM house WHERE hometown_ID ={$hometown} and is_deal = '是' ";
	$result = mysql_query($sql, $link);
	$list = mysql_fetch_assoc($result);
	if($list['house_ID'] == ""){
		$error = 1;
	}
	
	$main = "";
	//把年月日連起來變成字串
	$house_built_date = date_join($predict['year'],$predict['month'],$predict['day']);
	if ($city == 0 or $hometown == 0 or $house_built_date == "" or $predict['house_type'] == "") {
		$main .= "資料不全，請輸入完整的資料</br></br>";
		$main .= "<form><input type=\"button\" value=\"回上一頁\" onClick=\"history.go(-1)\" class=\"input_btn\"></form>";
	}
	elseif($error == 1) {
		$main .= "抱歉，本區域歷史資料不足，無法做房價預測</br></br>";
		$main .= "<form><input type=\"button\" value=\"回上一頁\" onClick=\"history.go(-1)\" class=\"input_btn\"></form>";
	}
	else{
//		$hometown;//取得行政區域
		$age_name = "";
		$age = date(Y) - $predict['year'];//取得屋齡
		if($age <= 15){
			$age_name = "house_age_lessthan15";
		}
		if($age > 15 && $age <= 30 ){
			$age_name = "house_age_between15and30";
		}
		if($age > 30){
			$age_name = "house_age_morethan30";
		}
		$type_name = "";//取得類型
		if($predict['house_type'] === '公寓'){
			$type_name = "apartment";
		}
		if($predict['house_type'] === '大樓'){
			$type_name = "building";	
		}
		if($predict['house_type'] === '社區'){
			$type_name = "community";
		}
	
		$global_weight = 0;//全區權重
		$global_range = "1";//全區範圍
		//進行坪價的權重判斷
		for($i=0;$i<6;$i++){
			$this_weight = 0;//本次權重
			if($i == 5){
				$table_name = " price_" .($i*20). "up ";
				$range = " price_per_ping > " .($i*200000). "  and is_deal = '是' ";
			}
			else {
				$table_name = " price_" .($i*20). "to".(($i+1)*20);
				$range = " price_per_ping <= " .(($i+1)*200000). " and price_per_ping > " .($i*200000). "  and is_deal = '是' ";
			}
		
			$sql = "SELECT * FROM {$table_name} WHERE 1";
			$result = mysql_query($sql, $link);
			while( $list = mysql_fetch_assoc($result) ){
				if($list['weight_name'] == 'hometown_ID_'.$hometown){
					$this_weight += $list['weight'];
				}
				if($list['weight_name'] == $age_name){
					$this_weight += $list['weight'];
				}
				if($list['weight_name'] == 'house_type_'.$type_name){
					$this_weight += $list['weight'];
				}
			}
			if($this_weight > $global_weight) {
				$global_weight = $this_weight;
				$global_range = $range;
			}
		}
		//從資料庫抓出符合條件的資料算平均價格
		$sql = "SELECT AVG(price_per_ping) as the_avg FROM house WHERE {$global_range}";
		$result = mysql_query($sql, $link);
		$list = mysql_fetch_assoc($result);
		$the_avg = $list['the_avg'];
		
		$sql = "SELECT city.city_name, hometown.hometown_name 
				FROM hometown 
				INNER JOIN city 
				USING(city_ID) 
				WHERE hometown.city_ID = '{$city}' and hometown.hometown_ID = '{$hometown}'";
		$result = mysql_query($sql, $link);
		$list = mysql_fetch_assoc($result);
		
		$main .= "{$list['city_name']}{$list['hometown_name']}</br>
				  預測每坪售價為：{$the_avg}元</br>
				  由以下歷史銷售資料計算得知</br></br>";
		
		//將符合條件的資料列出
		$sql = "SELECT * ,year(house_built_date) as house_built_year
				FROM house 
				INNER JOIN (hometown 
						INNER JOIN city 
						USING(city_ID))
				USING(hometown_ID) 
				WHERE {$global_range}";
		$result = mysql_query($sql, $link);
		
		$main .= "
		<div id=\"map\" style=\"width: 640px; height: 400px\"></div> <!--此為地圖顯示大小--></br>
		<form name=\"predict_result\" action=\"{$_SERVER['PHP_SELF']}\" method=\"get\">
		<table border=\"1\" class=\"list\">
		<tr align=\"center\">	
		<th>預覽圖</th>
		<th>地址</th>
		<th>類型</th>
		<th>車位</th>
		<th>屋齡</th>
		<th>坪數</th>
		<th>每坪價格</th>
		<th>總價</th>
		<th>成交</th>
		</tr>";
		
		//google maps用途的經緯度陣列
		$latitude = array();
		$longitude = array();
		$information = array();			
			while( $list = mysql_fetch_assoc($result) ){		
			$house_built_year = date(Y) - $list['house_built_year'];//計算屋齡：今年年份減建造年份
			$price_per_ping = round($list['price_per_ping']/10000, 2);//每坪價格取到萬位，小數只留兩位
			$total_price = round($list['ping']*$price_per_ping, 2);//算總價
			$pic = (empty($list['picture']))?_PIC_DIR."none.JPG":_PIC_DIR.$list['picture'];//抓圖

			//將每一筆讀到的經緯資料存入陣列  若讀到預設值則不存入
			if($list['latitude'] != 23.69781 and $list['longitude'] != 120.960515) {
				$latitude[] = $list['latitude'];
				$longitude[] = $list['longitude'];
				$information[] = "<img src='{$pic}' align='right' class='map_show_pic' \\>".
							 "{$list['city_name']}{$list['hometown_name']}{$list['address']}</br>".
							 "類型：{$list['house_type']}</br>".
							 "車位：{$list['parking_lot']}</br>".
							 "屋齡：{$house_built_year}年</br>".
							 "坪數：{$list['ping']}坪</br>".
							 "總價：{$total_price}萬</br>".
							 "成交：{$list['is_deal']}</br>".
							 "<a href = '{$_SERVER['PHP_SELF']}?op=house_page&the_house_id={$list['house_ID']}'>查看此筆房屋</a>";
			}				
			//將搜尋結果以表格方式顯示在主畫面區
			$main .= "
			<tr class=\"view\">
				<td align=\"center\">
				<a href = \"{$_SERVER['PHP_SELF']}?op=house_page&the_house_id={$list['house_ID']}\"><img src=\"{$pic}\" class=\"show_pic\" \\></a></td>
				<td><a href = \"{$_SERVER['PHP_SELF']}?op=house_page&the_house_id={$list['house_ID']}\">	
					{$list['city_name']}{$list['hometown_name']}{$list['address']}</a></td>
				<td>{$list['house_type']}</td>
				<td>{$list['parking_lot']}</td>
				<td>{$house_built_year}年</td>
				<td>{$list['ping']}坪</td>
				<td>{$price_per_ping}萬</td>
				<td>{$total_price}萬</td>
				<td>{$list['is_deal']}</td>
			</tr>" . "\n";
		}	
		$main .= "</table>";
		//GOOGLE MAPS 
		if(empty($latitude) or empty($longitude)) 
			$main .= call_maps()."對不起，沒有找到匹配結果。</br></br>";			
		else
			$main .= call_maps("multi",$latitude,$longitude,$information);	
	}
		
	return $main;
}

/****************************************管理員登入系統相關******************************************/
//註冊表單
function register_form($the_id = "") {
	if(empty($the_id))
		return "請先登入管理員系統";
	
	$main=<<<FORM
  	<form action="{$_SERVER['PHP_SELF']}" method="post">
  	<table class="input_table">	
	<tr>
  	<td class="col_title">人員姓名：</td>
  	<td class="col"><input type="text" name="reg[name]" class="txt"></td>
  	</tr>
  	<tr>
  	<td class="col_title">電子郵件：</td>
  	<td class="col"><input type="text" name="reg[email]" class="txt"></td>
  	</tr>
  	<tr>
  	<td class="col_title">設定帳號：</td>
  	<td class="col"><input type="text" name="reg[id]" class="txt"></td>
  	</tr>
  	<tr>
  	<td class="col_title">設定密碼：</td>
  	<td class="col"><input type="password" name="reg[passwd]" class="txt"></td>
  	</tr>
  	<tr>
  	<td class="col_title">確認密碼：</td>
  	<td class="col"><input type="password" name="reg[passwd2]" class="txt"></td>
  	</tr>
  	<td colspan="2" align="center">
  	<input type="hidden" name="op" value="register">
  	<input type="submit" value="註冊" class="input_btn">
  	</td>
  	</tr>
  	</table>
  	</form>
FORM;
	
	return $main;
}

//註冊
function register($user=array()) {
	if(empty($user['id']) or empty($user['passwd'])) 
		die("請填好欄位");
	if($user['passwd'] != $user['passwd2'])
		die("密碼前後輸入不一致");
	if(!eregi("[_.0-9a-z-]+@([0-9a-z-]+.)+[a-z]{2,3}$",$user['email']))die("Email格式不正確喔！");
	if(eregi("[^a-zA-Z0-9]",$user['id']))	//a-z  A-Z 0-9
		die("帳號只可輸入英文數字!");
	
	//利用輸入的帳號去比對是否是已註冊ID
	$mem = get_mem_data($user['id']);
	if(!empty($mem['user_ID']))
		die("此帳號已被註冊");
			
	if(!get_magic_quotes_gpc()){
    	foreach($user as $col=>$val){
      		$val=addslashes($val);
    		$user[$col]=$val;
    	}
  	}

  	$passwd=md5($user['passwd']);
  
  	$DB = DB_var();
	$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
	mysql_select_db($DB['database'], $link);
	  
  	$sql="insert into member (user_ID,user_password,user_name,email) values('{$user['id']}' , '{$passwd}' , '{$user['name']} ' , '{$user['email']}' )";
 	mysql_query($sql, $link) or die("註冊資料無法寫入喔！<br>".$sql);
}

function register_success() {
	return "管理員新增成功</br></br>";
}

//登入表單
function login_form(){
  $main=<<<FORM
  <form action="{$_SERVER['PHP_SELF']}" method="post">
  <table class="input_table">
  <tr>
  <td class="col_title">帳號：</td>
  <td class="col"><input type="text" name="id" class="txt"></td>
  </tr>
  <tr>
  <td class="col_title">密碼：</td>
  <td class="col"><input type="password" name="passwd" class="txt"></td>
  </tr>
  <td colspan="2" align="center">
  <input type="hidden" name="op" value="login">
  <input type="submit" value="登入" class="input_btn">
  </td>
  </tr>
  </table>
  </form>
FORM;

  return $main;
}

//取得某會員資料
function get_mem_data($the_id="") {
	if(empty($the_id))return;
	//資料庫連結		
	$DB = DB_var();
	$link = mysql_connect($DB['hostname'], $DB['username'], $DB['password']) or die("無法連結資料庫");
	mysql_select_db($DB['database'], $link);  

	$sql="select * from member where user_ID='{$the_id}'";
  	$result = mysql_query($sql, $link) or die("無法取得{$the_id}的資料！<br>".$sql);
  	$data = mysql_fetch_assoc($result);
  	return $data;
}

//身份確認＆登入判斷
function check_user($id="",$passwd="",$md5=false){
  if(empty($id) or empty($passwd))return false ;
  if($md5)$passwd=md5($passwd);
  $user=get_mem_data($id);
  if($user['user_ID']==$id and $user['user_password']==$passwd){
    if(empty($_SESSION["id"])){
      $_SESSION["id"]=$id;
      $_SESSION["passwd"]=$passwd;
      $_SESSION["usn"]=$user['usn'];
    }
    return true;
  }
  return false;
}
//登出
function logout() {
	$_SESSION = array();
	session_destroy();
}

?>