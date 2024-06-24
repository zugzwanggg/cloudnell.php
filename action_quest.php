<?php
	error_reporting(E_ALL);
	session_start();
	include "../db/db.php";
	include "../functions/functions.php";

	if(isset($_POST['json_request_comments'])){
		list($id,$name,$comments) = json_decode($_POST['json_request_comments'],true);
		$id = +str_replace('3h6v3k98', '', $id);
		
		$name = FILTER($name);
		$comments = FILTER($comments);

		$query_idss = $link->query("SELECT MAX(`id`) as id_m FROM `guest_comments`");
		$row = $query_idss->fetch_assoc();
		$row['id_m'] = (empty($row['id_m'])) ? 1 : $row['id_m']+1;

		$query_i = $link->query("INSERT INTO `guest_comments` VALUES('$row[id_m]','$id','$name','$comments','0',NOW())");

		if($link->insert_id){
			echo json_encode(['status'=>'success','data'=>'Спасибо. Ваш комментарий будет рассмотрен в ближайшее время']);
		}
	}

	if(isset($_POST['is_design_page'])){
		$query_s = $link->query("SELECT * FROM `page_view` WHERE `id_cafe`= (SELECT `id_cafe` FROM `desc` WHERE `link_code` = '$_POST[is_design_page]')");
		$data_design = $query_s->fetch_assoc();

		echo json_encode(['status'=>'success','data'=>$data_design,'id_c'=>$_SESSION['id_cafe']]);
	}

	if(isset($_POST['json_staff_call'])){
		$json = json_decode($_POST['json_staff_call'],true);
		$id_hash = $json['id_desc'];
		$id_employees = $json['id-employees'];
		
		$query_s = $link->query("SELECT `id`,`id_cafe` FROM `desc` WHERE `link_code` = '$id_hash'");
		$row = $query_s->fetch_assoc();

		$id_desc = $row['id'];
		$id_cafe = $row['id_cafe'];

		$query_s = $link->query("SELECT `id` FROM `personal` WHERE `id` IN (SELECT `id_waiter` FROM `service_desc` WHERE `id_desc` = '$id_desc') AND `id_employees` = '$id_employees'");

		$data = [];

		while($row = $query_s->fetch_assoc()){
			$data[0]['id_cafe'] = $id_cafe;
			$data[0]['id_desc'] = $id_desc;
			$data[0]['user_prersonal_id'][] = $row['id'];
		}	

		$data[0]['id_employees'] = $id_employees;
		
		echo json_encode(['status'=>'success','data'=>$data]);
	}

	if(isset($_POST['get_user_info'])){
		$id_user = +$_POST['get_user_info'];
		$get_user = INFO_USER_PERSONAL($link,$id_user);

		$query_s = $link->query("SELECT * FROM `staff_comments` WHERE `id_personal` = '$id_user' AND `views` = '1'");
		
		$comments = [];
		$i = 0;
		while($row = $query_s->fetch_assoc()){
			$comments[$i]['id'] = $row['id'];
			$comments[$i]['id_personal'] = $row['id_personal'];
			$comments[$i]['name'] = $row['name'];
			$comments[$i]['comments'] = $row['comments'];
			$comments[$i]['date_created'] = $row['date_created'];
			$i++;

		}
		

		if(count($get_user) > 0){
			echo json_encode(['status'=>'success','data'=>$get_user,'comments'=>$comments]);
		}else{
			echo json_encode(['status'=>'empty','data'=>[],'comments'=>$comments]);
		}
	}

	if(isset($_POST['json_send_comments'])){
		$json = json_decode($_POST['json_send_comments'],true);

		$id_personal = +$json[0];
		$name = FILTER($json[1]);
		$comments = FILTER($json[2]);
		$ip = $_SERVER['REMOTE_ADDR'];

		$query_idss = $link->query("SELECT MAX(`id`) as id_m FROM `staff_comments`");
		$row = $query_idss->fetch_assoc();
		$row['id_m'] = (empty($row['id_m'])) ? 1 : $row['id_m']+1;

		$date_created = date('Y-m-d H:i:s');

		$query_i = $link->query("INSERT INTO `staff_comments` VALUES('$row[id_m]','$id_personal','$name','$comments','0','$ip','$date_created')");

		if($link->insert_id){
			echo json_encode(['status'=>'success','insert_id'=>$link->insert_id]);
		}else{
			echo json_encode(['status'=>'error',"data"=>'Упс, что-то пошло не так']);
		}

	}

	// add 1 - set TG id and return waiter info
	if (isset($_POST['edit_waiter_id']) && isset($_POST['telegram_id'])) {
	    $waiterId = $_POST['edit_waiter_id'];
	    $tgId = $_POST['telegram_id'];
	    $query_s = $link->query("SELECT * FROM `personal` WHERE `id` = '$waiterId'");
	    $dataWaiter = $query_s->fetch_assoc();
	
	    $status = 'error';
	    $data = [];
	    if (isset($dataWaiter[0])) {
	        // TODO telegram_id ?
	        $data = $dataWaiter[0];
	        $query_upd = $link->query("UPDATE `personal` SET `telegram_id` = '$tgId' WHERE `id` = '$waiterId'");
	        if ($query_upd) {
	            $data['telegram'] = $tgId;
	            $status = 'success';
	        }
	    }
	
	    echo json_encode(['status' => $status, 'data' => $data]);
	}	
?>
