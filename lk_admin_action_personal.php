<?php
	error_reporting(E_ALL);
	session_start();
	include "../../../db/db.php";
	include "../../../functions/functions.php";
	
	$list__employees = [
				'waiter'=>3,
				'hookah'=>4,
				'cook'=>5
			];

	if(isset($_POST['name_pers'])){
		
		$email = FILTER($_POST['email_pers']);
		$name_pers = FILTER($_POST['name_pers']);
		$job_pers = FILTER($_POST['job_pers']);
		$descript_pers = FILTER($_POST['descript_pers']);
		$id_waiter_to_desk = FILTER($_POST['add_waiter_to_desk_arr']);
		$add_waiter_to_desk_arr = explode(",",$id_waiter_to_desk);
		$employees = +$list__employees[FILTER($_POST['employees'])];
		$password = FILTER($_POST['password']);
		

		if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
			echo json_encode(['status'=>'error','data'=>'Не верный формат Email']);
			return;
		}

		if(preg_match("~^\\s*$~",$password)){
			echo json_encode(['status'=>'error','data'=>'Пустое поле Пароль']);
			return;
		}

		if(mb_strlen($name_pers) > 50){
			echo json_encode(['status'=>'error','data'=>'В поле Имя может быть максимум 50 символов']);
			return;
		}

		if(mb_strlen($job_pers) > 50){
			echo json_encode(['status'=>'error','data'=>'В поле Должность может быть максимум 50 символов']);
			return;
		}

		if(mb_strlen($descript_pers) > 255){
			echo json_encode(['status'=>'error','data'=>'В поле Описание может быть максимум 255 символов']);
			return;
		}

		if(preg_match("~^\\s*$~",$name_pers)){
			echo json_encode(['status'=>'error','data'=>'Пустое поле Имя']);
			return;
		}

		if(preg_match("~^\\s*$~",$job_pers)){
			echo json_encode(['status'=>'error','data'=>'Пустое поле Должность']);
			return;
		}

		$password = password_hash($password, PASSWORD_DEFAULT);

		$query_idss = $link->query("SELECT MAX(`id`) as id_m FROM `personal`");
		$row = $query_idss->fetch_assoc();
		$row['id_m'] = (empty($row['id_m'])) ? 1 : $row['id_m']+1;

		$hash_user = HASH__SIMBOL();

		$query_i = $link->query("INSERT INTO `personal` VALUES('$row[id_m]','$email','$name_pers','$job_pers','$descript_pers','$password','$employees','$_SESSION[id_cafe]',NOW(),'','0000-00-00 00:00:00','0','$hash_user','0','music.mp3')");
		
		$insert_id = $link->insert_id;

		$query_idss = $link->query("SELECT MAX(`id`) as id_m FROM `service_desc`");
		$row = $query_idss->fetch_assoc();
		$row['id_m'] = (empty($row['id_m'])) ? 1 : $row['id_m']+1;

		foreach($add_waiter_to_desk_arr as $v){
			$query_i = $link->query("INSERT INTO `service_desc` VALUES('$row[id_m]','$insert_id',$v)");
			$row['id_m']++;
		}

		if(isset($_FILES['photo_add_waiter'])){
			$uploads_dir = "../../../lk/lk_waiter/image/photo_profile/$insert_id";
			
			mkdir($uploads_dir,0755);

			$tmp_name = trim($_FILES['photo_add_waiter']['tmp_name']);
			$name = trim(basename($_FILES["photo_add_waiter"]["name"]));

			move_uploaded_file($tmp_name,$uploads_dir.'/'.$name);

			$query_s = $link->query("UPDATE `personal` SET `photo_profile` = '$name' WHERE `id` = '$insert_id'");

		}else{
			$uploads_dir = "../../../lk/lk_waiter/image/photo_profile/$insert_id";
			
			mkdir($uploads_dir,0777);

			copy("../../../lk/lk_waiter/image/photo_profile/no_photo.png", "../../../lk/lk_waiter/image/photo_profile/$insert_id/no_photo.png");

			$query_s = $link->query("UPDATE `personal` SET `photo_profile` = 'no_photo.png' WHERE `id` = '$insert_id'");
		}

		if(isset($id_waiter_to_desk) && strlen($id_waiter_to_desk) > 0){
			$desc_title = [];
			$query_s = $link->query("SELECT `title` FROM `desc` WHERE `id` IN ($id_waiter_to_desk)");
			while($row_s = $query_s->fetch_assoc()){
				$desc_title[] = $row_s['title'];
			}
		}

		$get_info_user = [
				'insert_id' 	=>	$insert_id,
				'email' 		=> 	$email,
				'login' 		=> 	$name_pers,
				'job_pers'  	=> 	$job_pers,
				'descript_pers'	=>	$descript_pers,
				'desc'			=>	$desc_title,
				'photo_profile' =>  $name
		];

		if(isset($insert_id)){
			echo json_encode(['status'=>'success','data'=>$get_info_user]);
		}
	}

	if(isset($_POST['delete_waiter_id'])){
		$id = +FILTER($_POST['delete_waiter_id']);
		$dir = "../../../lk/lk_waiter/image/photo_profile/$id";
		@unlink($dir.'/'.end(scandir($dir)));
		@rmdir($dir);

		$query_d = $link->query("DELETE FROM `personal` WHERE `id` = '$id'");
		$query_d = $link->query("DELETE FROM `service_desc` WHERE `id_waiter` = '$id'");
		$query_d = $link->query("DELETE FROM `notice_orders` WHERE `id_orders` IN (SELECT `id` FROM `orders` WHERE `id_personal` = '$id')");
		$query_d = $link->query("DELETE FROM `orders` WHERE `id_personal` = '$id'");

	}

	if(isset($_POST['edit_waiter_id'])){
		$id = +FILTER($_POST['edit_waiter_id']);
    $tgId = +FILTER($_POST['telegram_id']);
		$query_s = $link->query("SELECT * FROM `personal` WHERE `id` = '$id' AND `telegram_id` = '$tgId' AND `id_cafe` = '$_SESSION[id_cafe]'");
		$row = $query_s->fetch_assoc();

		$query_s = $link->query("SELECT `id_desc` FROM `service_desc` WHERE `id_waiter` = '$id'");

		$desk_id = [];

		while($row_desc = $query_s->fetch_assoc()){
			$desk_id[] = $row_desc['id_desc'];
		}
		$row['desc_id'] = $desk_id;
		
		echo json_encode(['status'=>'success','data'=>$row]);
	}

	if(isset($_POST['id_edit_personal'])){
		$id  = +FILTER($_POST['id_edit_personal']);
    $tgId = +FILTER($_POST['telegram_id']);
		$edit_email_pers = FILTER($_POST['edit_email_pers']);
		$edit_name_pers = FILTER($_POST['edit_name_pers']);
		$edit_job_pers = FILTER($_POST['edit_job_pers']);
		$edit_descript_pers = FILTER($_POST['edit_descript_pers']);
		$edit_waiter_to_desk_arr = explode(',',$_POST['edit_waiter_to_desk_arr']);
		$data = [];

		$query_d = $link->query("DELETE FROM `service_desc` WHERE `id_waiter` = '$id'");

		$query_idss = $link->query("SELECT MAX(`id`) as id_m FROM `service_desc`");
		$row = $query_idss->fetch_assoc();
		$row['id_m'] = (empty($row['id_m'])) ? 1 : $row['id_m']+1;

		foreach($edit_waiter_to_desk_arr as $v){
			$query_i = $link->query("INSERT INTO `service_desc` VALUES('$row[id_m]','$id',$v)");
			$row['id_m']++;
		}

		$query_s = $link->query("UPDATE `personal` SET `email` = '$edit_email_pers', `login` = '$edit_name_pers', `job_title` = '$edit_job_pers', `descript` = '$edit_descript_pers' WHERE `id` = '$id' AND `telegram_id` = '$tgId'");

		$data['ids'] = $id;

		if(isset($_FILES['photo_edit_waiter'])){
			$uploads_dir = "../../../lk/lk_waiter/image/photo_profile/$id";
			$direct_photo = array_diff(scandir($uploads_dir),['.','..']);
			

			if(count($direct_photo) > 0){
				@unlink($uploads_dir.'/'.end($direct_photo));
			}

			$tmp_name = trim($_FILES['photo_edit_waiter']['tmp_name']);
			$name = trim(basename($_FILES["photo_edit_waiter"]["name"]));

			move_uploaded_file($tmp_name,$uploads_dir.'/'.$name);

			$query_s = $link->query("UPDATE `personal` SET `photo_profile` = '$name' WHERE `id` = '$id' AND `telegram_id` = '$tgId'");

			$data['edit_photo'] = $name;
		}

		echo json_encode(['status'=>'success','data'=>$data]);

	}

	if(isset($_POST['search_pesonal'])){
		list($value, $employees) = json_decode($_POST['search_pesonal'],true);
		$value = FILTER($value);
		$employees = FILTER($employees);
		$employees = $list__employees[$employees];
		if(preg_match("~^\\s*$~uis",$value)){
			echo json_encode(['status'=>'empty','data'=>[]]);
		}else{
			$query_s = $link->query("SELECT `id`,`id_employees` FROM `personal` 
				WHERE `id_employees` = '$employees' AND `id_cafe` = '$_SESSION[id_cafe]' AND `email` LIKE '%$value%' OR `login` LIKE '%$value%' ");
			$id_search = [];
			while($row = $query_s->fetch_assoc()){
				if($row['id_employees']==$employees)
					$id_search[] = $row['id'];
			}
			
			if(count($id_search) > 0){
				echo json_encode(['status'=>'success','data'=>$id_search]);
			}else{
				echo json_encode(['status'=>'empty','data'=>[]]);
			}
		}
	}

	if(isset($_POST['stars__'])){
		$id = +FILTER($_POST['stars__']);
		$count_stars = +FILTER($_POST['count_stars']);
		$query_s = $link->query("UPDATE `personal` SET `star` = '$count_stars' WHERE `id` = '$id'");
	}

	if(isset($_POST['get_comment_user'])){
		$id = +FILTER($_POST['get_comment_user']);
		$publish = (FILTER($_POST['publish'])=='publish') ? 1 : 0;

		$query_s = $link->query("SELECT * FROM `staff_comments` WHERE `id_personal` = '$id' AND `views` = '$publish'");
		$comments = [];
		while($row = $query_s->fetch_assoc()){
			$comments[$row['id']]['id'] = $row['id'];
			$comments[$row['id']]['id_personal'] = $row['id_personal'];
			$comments[$row['id']]['name'] = $row['name'];
			$comments[$row['id']]['comments'] = $row['comments'];
			$comments[$row['id']]['date_created'] = $row['date_created'];
		}

		if(count($comments) > 0){
			echo json_encode(['status'=>'success','data'=>$comments]);
		}else{
			echo json_encode(['status'=>'empty','data'=>'У вас нет комментариев']);
		}
	}

	if(isset($_POST['set_comments'])){
		$arr = json_decode($_POST['set_comments'],true);
		$id = +$arr['id'];
		
		if($arr['action']=='delete_comments'){
			$query_d = $link->query("DELETE FROM `staff_comments` WHERE `id` = '$id'");
		}else if($arr['action']=='confirm_comments'){
			$query_u = $link->query("UPDATE `staff_comments` SET `views` = '1' WHERE `id` = '$id'");
		}
	}
	

	
?>