<?php

use App\Models\AppSettings;
use App\Models\OTPLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

if(!function_exists('getUserProfileImage')){
	function getUserProfileImage($image_name=''){	

        if(File::exists(public_path('/uploads/users/'.$image_name)) && !empty($image_name)){            
            return url('/uploads/users/'.$image_name);
        }
        return url('/uploads/default/default-user.png');
	}
}

function WALLET_TRANSACTION_TYPE($key){
	$data = array('W_ADD'=>'WALLET_ADD', 'W_WITHDRAW'=>'WALLET_WITHDRAW', 'WIN'=>'TOURNAMENT_WIN', 'JOIN'=>'TOURNAMENT_JOIN');

	return $data[$key];
}

if(!function_exists('generate_uniq_code')){
	function generate_uniq_code($length=8, $table = '', $column = '', $where=array()){
	   $token = "";
	    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	    $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
	    $codeAlphabet.= "0123456789";
	    $max = strlen($codeAlphabet);
	    for ($i=0; $i < $length; $i++) {
	        $token .= $codeAlphabet[crypto_rand_secure(0, $max-1)];
	    }
	    if((!empty($table)) && (!empty($column))){
	    	$check_where = $where;
	    	$check_where[$column] = $token;
	    	$check = _getRows($table, $select='*', $check_where, $order = array(), $data_join=array(), $resultdata="row_array");
	    	if(!empty($check)){
	    		return generate_uniq_code($length, $table = '', $column = '', $where);
	    	}else{ return $token; }
	    }else{
	    	return $token;
	    }
	}
}

if(!function_exists('generate_code')){
	function generate_code($length = 10){
        if ($length <= 0){
            return false;
        }
        $code   = "";
        $chars  = "abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
        srand((double)microtime() * 1000000);
        for ($i = 0; $i < $length; $i++){
            $code = $code . substr($chars, rand() % strlen($chars), 1);
        }
        return $code;
    }
}

if(!function_exists('getWeekRange')){
	function getWeekRange($ts){
		// $start = (date('w', $ts) == 0) ? $ts : strtotime('last sunday', $ts);
		// $end = strtotime('next saturday', $start);
		// $r_array['start_of_week'] = $start;
		// $r_array['end_of_week'] = $end;
		// return $r_array;
		$start = strtotime("7 days ago");
		$end = $ts;
		$r_array['start_of_week'] = $start;
		$r_array['end_of_week'] = $end;
		return $r_array;
	}
}
function win_status($key){
	$array = array('W'=>'WON', 'L'=>'LOST', 'P'=>'Pending', 'N'=>'Cancelled');
	return $array[$key];
}

define('WIN_STATUS', array('W'=>'WON', 'L'=>'LOST', 'P'=>'Pending', 'N'=>'Cancelled'));

if(!function_exists('get_type_status_from_date')){
	function get_type_status_from_date($start,$end,$type="html"){
		$curr_date = time();
		$touranment_status = '';
		if($type == "text"):
			if($curr_date >= $start && $curr_date < $end) :
				$touranment_status = 'On Going';
			elseif($curr_date < $start) :
				$touranment_status = 'Upcoming';
			elseif($curr_date > $end):
				$touranment_status = 'Past';
			else:
				$touranment_status = '';
			endif;
		else:
			if($curr_date >= $start && $curr_date < $end) :
				$touranment_status = '<mark class="bg-success text-white rounded p-1"> On Going </mark>';
			elseif($curr_date < $start) :
				$touranment_status = '<mark class="bg-warning text-white rounded p-1"> Upcoming </mark> ';
			elseif($curr_date > $end):
				$touranment_status = '<mark class="bg-danger text-white rounded p-1"> Past </mark>';
			else:
				$touranment_status = '';
			endif;
		endif;

		return $touranment_status;
	}
}

function UserLink($id,$name){
	return "<a href='".route('users.view',$id)."' target='_blank'>".$name."</a>";
}


if (! function_exists('getUserDetails')) {
	function getUserDetails($user){
		$data = array();
		
		$data['user_id']			=	$user->id;
		//$data['first_name']			=	$user->first_name;
		//$data['last_name']			=	$user->last_name;
		$data['username']			=	$user->username;
		$data['email']				=	$user->email;		
		$data['wallet']				=	$user->wallet?$user->wallet:0;
		$data['sound']				=	$user->sound?$user->sound:1;

		$data['tickets']['total_ticket'] = 0;
		$data['tickets']['used_ticket'] = 0;
		$data['tickets']['remain_ticket'] = 0;
		$Tournament = App\Models\Tournament::RunningTournament();
		if(!empty($Tournament)){
			$user_tickets = App\Models\UserTicket::where('user_id',$user->id)->where('tournament_id',$Tournament->id)->first();
			if(!empty($user_tickets)){
				$data['tickets']['total_ticket'] = $user_tickets->total_ticket;
				$data['tickets']['used_ticket'] = $user_tickets->used_ticket;
				$data['tickets']['remain_ticket'] = $user_tickets->remain_ticket;
			}

		}

		return $data;
	}
}

if (! function_exists('DateFormate')) {
	function DateFormate($date, $is_timestamp=false){
		//$date = str_replace('/', '-', $date);
		$date = $date;
		if($is_timestamp==false){
			$date = strtotime($date);
		}
		return date(config('site_setting.DATE_FORMATE','d-m-Y').' H:i:s' ,$date);
	}
}

if (! function_exists('DateFormateJs')) {
	function DateFormateJs($date){
		if(!empty($date)){	
			$date = \Carbon\Carbon::createFromFormat(config('site_setting.DATE_FORMATE','d-m-Y').' H:i:s', $date)->toDateTimeString();
			
			return date(config('site_setting.DATE_FORMATE','d-m-Y').' H:i' ,strtotime($date));
		}else{
			return "";
			$date = date("Y-m-d H:i:s");
			return date(config('site_setting.DATE_FORMATE','d-m-Y').' H:i' ,strtotime($date));
		}
	}
}

if (! function_exists('DateTimePickerDateFormate')) {
	function DateTimePickerDateFormate($lower=0){
		$fotmate = config('site_setting.DATE_FORMATE','d-m-Y');
		
		if($fotmate == "m/d/Y"){
			$fotmate = "MM/DD/YYYY";
		}if($fotmate == "d/m/Y"){
			$fotmate = "DD/MM/YYYY";
		}if($fotmate == "d-m-Y"){
			$fotmate = "DD-MM-YYYY";
		}if($fotmate == "Y-m-d"){
			$fotmate = "YYYY-MM-DD";
		}
		if($lower){
			return strtolower($fotmate);
		}
		return $fotmate;
	}
}

if (! function_exists('DefaultDateFormate')) {
	function DefaultDateFormate(){
		return config('site_setting.DATE_FORMATE','d-m-Y').' H:i:s';
	}
}
if (! function_exists('DefaultDateFormateJs')) {
	function DefaultDateFormateJs(){
		return config('site_setting.DATE_FORMATE','d-m-Y').' H:i';
	}
}



if(!function_exists('serverside_validate'))
{
    function serverside_validate($validator=[])
    {
        if(!empty($validator)) {
            if($validator->fails()) {
                http_response_code(200);
                $response['status'] = 0;
                $response['message'] = $validator->errors()->first();
                header("Content-Type: application/json");
                echo json_encode($response); die;
            }
        }
    }
}

if(!function_exists('generate_otp'))
{
	function generate_otp($length = 4, $testmode=false)
	{
        if ($length <= 0){
            return false;
        }
		$otp_testmode_status = config('site_setting.OTP_TESTMODE_STATUS', '0');
        $code   = "";
		$chars  = "1234567890";
        if($testmode==true || $otp_testmode_status=='1'){
        	$chars  = "1";
        }
        srand((double)microtime() * 1000000);
        for ($i = 0; $i < $length; $i++){
            $code = $code . substr($chars, rand() % strlen($chars), 1);
		}
		if($code[0]==0){ $code[0] = rand(1,9); }
        return $code;
    }
}

if(!function_exists('generate_otp_old'))
{
    function generate_otp_old($length=4,$testmode=false)
    {
        if($length<=0) {
            return false;
        }
        $result = '';
        $generator = '1357902468';
        if($testmode == true) {
            $generator;
        }
        for($i=1; $i<=$length; $i++) {
            $result .= substr($generator,(rand() % (strlen($generator))),1);
        }
        if($result[0] == 0) {
            $result[0] = rand(1,9);
            return $result;
        }
        return $result;
    }
}

if(!function_exists('send_otp')) {
	function send_otp($phone_number, $otp) {
		$otp_testmode_status = config('site_setting.OTP_TESTMODE_STATUS', '0');
		if($otp_testmode_status=='0'){
			$auth_key = config('site_setting.SMS_AUTH_KEY');
			$template_id = config('site_setting.SMS_TEMPLATE_ID');
			$company_name = config('site_setting.SMS_COMPANY_NAME');
			$curl = curl_init();
			$otp = (int)$otp;
			if ((strpos($phone_number, '+91') == false) && (strpos($phone_number, '91') == false)) {
				$phone_number = '+91'.$phone_number;
			}
			$phone_number = ltrim($phone_number,'+');
			// dd($phone_number);
			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://api.msg91.com/api/v5/otp?authkey=".$auth_key."&template_id=".$template_id."&mobile=".$phone_number."&otp=".$otp."&company_name=".$company_name,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_SSL_VERIFYHOST => 0,
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_HTTPHEADER => array(
					"content-type: application/json"
				),
			));
			$response = curl_exec($curl);
			$err = curl_error($curl);
			curl_close($curl);
			save_log('MSG91 Response: '.json_encode($response).', URL: '."https://api.msg91.com/api/v5/otp?authkey=".$auth_key."&template_id=".$template_id."&mobile=".$phone_number."&otp=".$otp."&company_name=".$company_name);
			if ($err) {
				//echo "cURL Error #:" . $err;
				save_log('MSG91 Response(Error): '.$err);
				return false;
			} else {
				return $response;
			}
		}else{
			return true;
		}
	}
}

if(!function_exists('getUserStatusHtml'))
{
    function getUserStatusHtml($status)
    {           
        $html = '';
                        
        if($status == 'Active'){
            $html .= '<label class="badge badge-success mr-1">Active</label>';
        } else if($status == 'Inactive'){
            $html .= '<label class="badge badge-warning mr-1">Inactive</label>';
        } else if($status == '3'){
            $html .= '<label class="badge badge-danger mr-1">Deleted</label>';
        } else if($status == '4'){
            $html .= '<label class="badge badge-dark mr-1">Blocked</label>';
        }else if($status == "Y"){
			$html .= '<label class="badge badge-success mr-1">Yes</label>';
        }else if ($status =="N"){
        	$html .= '<label class="badge badge-danger mr-1">No</label>';
        }
        
        return $html;
    }
}

if(!function_exists('geStatusHtml'))
{
    function getStatusHtml($status)
    {           
        $html = '';
                        
        if($status == 'Active'){
            $html .= '<label class="badge badge-success mr-1">Active</label>';
        } else if($status == 'Inactive'){
            $html .= '<label class="badge badge-warning mr-1">Inactive</label>';
        } else if($status == '3'){
            $html .= '<label class="badge badge-danger mr-1">Deleted</label>';
        } else if($status == '4'){
            $html .= '<label class="badge badge-dark mr-1">Blocked</label>';
        }else if($status == "Y"){
			$html .= '<label class="badge badge-success mr-1">Yes</label>';
        }else if ($status =="N"){
        	$html .= '<label class="badge badge-danger mr-1">No</label>';
        }else if ($status =="W"){
        	$html .= '<label class="badge badge-success mr-1">win</label>';
        }else if ($status =="L"){
        	$html .= '<label class="badge badge-danger mr-1">Loss</label>';
        }else if ($status =="P"){
        	$html .= '<label class="badge badge-warning mr-1">Pending</label>';
        }else{
        	$html .= '<label class="badge badge-danger mr-1">'.$status.'</label>';
        }
        
        return $html;
    }
}


if(!function_exists('getUserImg'))
{
    function getUserImg($name='')
    {
        if(!empty($name) && file_exists(public_path('uploads/users/'.$name)))
        {
            return asset('uploads/users/'.$name);
        } else {
            return asset('uploads/default/default-user.png');
        }
    }
}

// Begin - Custom Script

if(!function_exists('admin_profile_image'))
{
	function admin_profile_image($name='')
	{
		if(!empty($name) && file_exists(public_path('uploads/admin/'.$name)))
		{
			return url('uploads/admin/'.$name);
		}else{
			return url('uploads/default/default-admin.png');
		}
	}
}

if(!function_exists('user_profile_image'))
{
	function user_profile_image($name='')
	{
		if(!empty($name) && file_exists(public_path('uploads/users/'.$name)))
		{
			return url('uploads/users/'.$name);
		}else{
			return url('uploads/default/default-user.png');
		}
	}
}

if(!function_exists('get_paginate_params'))
{
	function get_paginate_params($request)
	{
		$page = ($request->page) ? intval($request->page) : 1;
		$page_size = ($request->page_size) ? intval($request->page_size) : 10;
		$return_data = [
			'page' => $page,
			'page_size' => $page_size,
			'offset' => ($page-1) * $page_size,
			'search_term' => $request->search_term ?? '',
		];
		return $return_data;
	}
}

if(!function_exists('get_pagination'))
{
	function get_pagination($parameters, $total_records)
	{
		$max_page_num = ceil($total_records/$parameters['page_size']);
		$is_have_more_records = 'Y';
		if($parameters['page'] >= $max_page_num){ $is_have_more_records = 'N'; }
		return [
			'page'=>intval($parameters['page']),'page_size'=>intval($parameters['page_size']),
			'skip_records'=>$parameters['offset'],
			'total_records'=>$total_records,'max_page_num'=>$max_page_num,'is_have_more_records'=>$is_have_more_records
		];
	}
}

if(!function_exists('send_notification'))
{
	function send_notification($user_id=0,$type='',$title,$message,$adata = array(),$device_token='',$device_type='A',$isSilent=false)
	{
		$target = array();
		$push_notification_status = 'Y';
		if(empty($device_token))
		{
			$user_data = DB::table('user_devices')->leftJoin('users','user_devices.user_id','=','users.user_id')->select('user_devices.user_id','user_devices.device_token','user_devices.device_type','push_notification_status')->where('user_devices.user_id', $user_id)->first();
			if(empty($user_data)){ return false; }
			$push_notification_status = $user_data->push_notification_status;
			array_push($target,$user_data->device_token);
			$device_type = $user_data->device_type;
		} else {
			if(is_array($device_token)){
				$target = array_merge($target,$device_token);
			}else{
				array_push($target,$device_token);
			}
		}

		$result = true;
		$isNotification = true;
		if(($push_notification_status == 'N') || ($isSilent == true))
		{
			$result = send_push_notification($title,$message,$type,$adata,$target,$device_type,true);
		} else {
			$result = send_push_notification($title,$message,$type,$adata,$target,$device_type,false);
		}
		if($result)
		{
			$json_adata = json_encode($adata);
			if(!empty($user_id)){
				add_user_notification($user_id,$type,$title,$message,$json_adata);
			}
		}
		return $result;
	}
}

if(!function_exists('add_user_notification'))
{
	function add_user_notification($user_id=0,$type='GENERAL',$title='',$message='',$json_data=array())
	{
		if(!empty($json_data) && is_array($json_data))
		{
			$json_data = json_encode($json_data);
		}
		$insert_data = array(
			'user_id' => $user_id,
			'notification_type'=> $type,
			'notification_title' => $title,
			'notification_message' => $message,
			'notification_json' =>$json_data,
			'created_at' => time(),
			'updated_at' => time()
		);
		$subscribe_id = DB::table('user_notifications')->insert($insert_data);
	}
}

/* Begin - Send Notification Using FCM */
if(!function_exists('send_push_notification')){
	function send_push_notification($title, $messageString, $type='GENERAL', $additional_data, $target, $device_type = '',$isSilent = false) {
		
		$url = 'https://fcm.googleapis.com/fcm/send';
        $notification_data = array();
        
        $siteTitle = config('app.name');
        if(!empty($title)){ $siteTitle = $title; }
        $notification_data['title'] = $siteTitle;
        $notification_data['ticker'] = $siteTitle;
        $notification_data['vibrate'] = 1;
        $notification_data['sound'] = 1;
        $notification_data['message'] = $messageString;
        $notification_data['alert'] = $messageString;
        $notification_data['type'] = $type;

        if(!empty($additional_data)){
        	if((is_array($additional_data)) && (count($additional_data) > 0)){
        		foreach($additional_data as $key=>$val){ $notification_data[$key] = $val; }
        	}
        }
        $notification_data['additional_data'] = $additional_data; 

        $fields = array();
        if($device_type == "I"){
            $notification_data['body'] = $notification_data['message'];
            // $fields['notification'] = $notification_data;
            if($isSilent == true){
                $fields['content_available'] = true;
                $fields['data'] = $notification_data;
            }else{
                $fields['notification'] = $notification_data;
            }
        }else{
            $fields['data'] = $notification_data;
        }

        $fields['priority'] = "high";
        $fields['registration_ids'] = $target;
		$fcm_server_key = config('site_setting.FCM_KEY_LIVE');
        $headers = array('Content-Type:application/json','Authorization:key='.$fcm_server_key);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);

		save_log('FCM Response New: '.$result.', target'.json_encode($target));

        if ($result === FALSE) {
           // die('FCM Send Error: ' . curl_error($ch));
        }

        curl_close($ch);
        return $result;
	}
}

if(!function_exists('send_push_notification'))
{
	function send_push_notification($title,$message,$type,$additional_data=[],$target,$device_type='',$isSilent = false)
	{

		$SERVER_API_KEY = config('site_setting.FCM_KEY_LIVE');
		if(empty($SERVER_API_KEY)){
			return false;
		}

		$url = 'https://fcm.googleapis.com/fcm/send';
		$notification_data = array();
		$siteTitle = config('app.name');
		if(!empty($title)) {
			$siteTitle = $title;
		}
		$notification_data['title'] = $siteTitle;
		$notification_data['ticker'] = $siteTitle;
		$notification_data['vibrate'] = 1;
		$notification_data['sound'] = 1;
		$notification_data['message'] = $message;
		$notification_data['alert'] = $message;
		$notification_data['body'] = $message;
		$notification_data['type'] = $type;
		
		if(is_array($additional_data) && isset($additional_data['image']) && !empty($additional_data['image'])){
			$notification_data['image'] = $additional_data['image'];
		}

		// if(!empty($additional_data))
		// {
		// 	if((is_array($additional_data)) && (count($additional_data)>0)){
		// 		foreach($additional_data as $key => $val) {
		// 			$notification_data[$key] = $val;
		// 		}
		// 	}
		// }
		if(!empty($target) && !is_array($target)){
			$target = explode(',',$target);
		}
		// $target_ = [$target];
		$fields = array();
		if(!empty($additional_data))
			$fields['data'] = $additional_data;
		
		$fields['notification'] = $notification_data;

		/*if($device_type == 'I')
		{
			$notification_data['body'] = $notification_data['message'];
			if($isSilent == true)
			{
				$fields['content-available'] = true;
				$fields['data'] = $notification_data;
			} else {
				$fields['notification'] = $notification_data;
			}
		} else {
			$fields['notification'] = $notification_data;
		}*/

		$fields['priority'] = 'high';
		$fields['registration_ids'] = $target;
		
		//dump($fields);
		$server_key = $SERVER_API_KEY;
		$headers = array('Content-Type:application/json','Authorization:key='.$server_key);
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($fields));
		$response = curl_exec($ch);
		// echo '<pre>';print_r($result);die;
		save_log('FCM Response: '.$response.', target'.json_encode($target));
		if($response === false)
		{
			$result_noti = 0;
		} else {
			$result_noti = 1;
		}
		curl_close($ch);
		return $result_noti;
	}
}
/* End - Send Notification Using FCM */

/* Begin - Send Notification Using One Signal */
/* if(!function_exists('send_push_notification')){
	function send_push_notification($title, $message, $type, $additional_data=[], $target,$device_type='',$isSilent = false){
		// \Log::info('In send_push_notification');
		// $app_id = config('app.ONESIGNAL_APP_ID');
		// $app_id = DB::table('constants')->where('constant_name','ONESIGNAL_APP_ID')->first()->constant_value;
		$app_id = get_setting('ONESIGNAL_APP_ID');
		if(!is_array($target)){
			$target = explode(',', $target);
		}
		$headings = ["en" => $title];
		$contents = ["en" => $message];
		$subtitle = ["en" => $message];
		$additional_data['type'] = $type;
        
        $fields = array(
            'app_id' => $app_id,
            'include_player_ids' => $target,
            'data' => $additional_data,
            'headings' => $headings,
            'contents' => $contents,
            'subtitle' => $subtitle,
        );
        
        $fields = json_encode($fields);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
		// $responseInfo = curl_getinfo($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

		// echo '<pre>';print_r($response);die;
		
		save_log('Onesignal Response: '.$response.', target'.json_encode($target));

		if($http_code==200){
			return true;
			// return $response;
		}else{
			return false;
		}
	}
} */
/* End - Send Notification Using One Signal */

if(!function_exists('float_amount')){
	function float_amount($amount=0, $decimals=2){
		return number_format($amount, $decimals, '.', '') + 0;
	}
}

if(!function_exists('save_log')){
	function save_log($content){
		DB::table('logs')->insert(['log'=>$content, 'created_at'=>current_date(), 'updated_at'=>current_date()]);
		/* if(empty($content)){
			return FALSE;
		}
		return true; */
	}
}

if(!function_exists('current_date')){
	function current_date(){
		return date('Y-m-d H:i:s');
	}
}

if(!function_exists('current_time')){
	function current_time(){
		return time();
	}
}

if(!function_exists('get_filter_date_range')){
	function get_filter_date_range($range_type='TODAY', $start_date='', $end_date='', $show_all=false, $timestamp=true){
		$start_time = $end_time = '';
        if ($range_type=='TODAY') {
			if ($timestamp==true) {
				$start_time = strtotime('today');
            	$end_time = strtotime(date('Y-m-d 23:59:59'));
			}else{
				$start_time = date('Y-m-d 00:00:00');
            	$end_time = date('Y-m-d 23:59:59'); 
			}
        }elseif($range_type=='YESTERDAY'){
            if ($timestamp==true) {
                $start_time = strtotime('yesterday');
                $end_time = strtotime(date('Y-m-d 23:59:59', strtotime('yesterday')));
            }else{
				$start_time = date('Y-m-d 00:00:00', strtotime('yesterday'));
                $end_time = date('Y-m-d 23:59:59', strtotime('yesterday'));
			}
		}elseif($range_type=='LAST_7_DAYS'){
            if ($timestamp==true) {
				$start_time = strtotime(date('Y-m-d 00:00:00',strtotime('-6 days')));
            	$end_time = strtotime(date('Y-m-d 23:59:59'));
            }else{
				$start_time = date('Y-m-d 00:00:00',strtotime('-6 days'));
            	$end_time = date('Y-m-d 23:59:59');
			}
		}elseif($range_type=='LAST_30_DAYS'){
            if ($timestamp==true) {
                $start_time = strtotime(date('Y-m-d 00:00:00', strtotime('-29 days')));
                $end_time = strtotime(date('Y-m-d 23:59:59'));
            }else{
				$start_time = date('Y-m-d 00:00:00', strtotime('-29 days'));
                $end_time = date('Y-m-d 23:59:59');
			}
		}elseif($range_type=='THIS_WEEK'){
            if ($timestamp==true) {
                $start_time = strtotime("this week monday");
                $end_time = strtotime(date("Y-m-d 23:59:59", strtotime("this week sunday")));
            }else{
				$start_time = date("Y-m-d 00:00:00", strtotime("this week monday"));
                $end_time = date("Y-m-d 23:59:59", strtotime("this week sunday"));
			}
		}elseif($range_type=='PREVIOUS_WEEK'){
            if ($timestamp==true) {
                $start_time = strtotime("last week monday");
                $end_time = strtotime(date("Y-m-d 23:59:59", strtotime("last week sunday")));
            }else{
				$start_time = date("Y-m-d 00:00:00", strtotime("last week monday"));
                $end_time = date("Y-m-d 23:59:59", strtotime("last week sunday"));
			}
		}elseif($range_type=='THIS_MONTH'){
            if ($timestamp==true) {
                $start_time = strtotime(date('Y-m-01 00:00:00', strtotime('this month')));
                $end_time = strtotime(date('Y-m-t 23:59:59', strtotime('this month')));
            }else{
				$start_time = date('Y-m-01 00:00:00', strtotime('this month'));
                $end_time = date('Y-m-t 23:59:59', strtotime('this month'));
			}
		}elseif($range_type=='PREVIOUS_MONTH'){
            if ($timestamp==true) {
                $start_time = strtotime(date('Y-m-01 00:00:00', strtotime('last month')));
                $end_time = strtotime(date('Y-m-t 23:59:59', strtotime('last month')));
            }else{
				$start_time = date('Y-m-01 00:00:00',strtotime('last month'));
            	$end_time = date('Y-m-t 23:59:59',strtotime('last month'));
			}
		}elseif($range_type=='THIS_YEAR'){
            if ($timestamp==true) {
                $start_time = strtotime(date('Y-01-01 00:00:00'));
                $end_time = strtotime(date('Y-12-31 23:59:59'));
            }else{
				$start_time = date('Y-01-01 00:00:00');
            	$end_time = date('Y-12-31 23:59:59');
			}
		}elseif($range_type=='PREVIOUS_YEAR'){
            if ($timestamp==true) {
                $start_time = strtotime(date('Y-01-01 00:00:00', strtotime('last year')));
                $end_time = strtotime(date('Y-12-31 23:59:59', strtotime('last year')));
            }else{
				$start_time = date('Y-01-01 00:00:00', strtotime('last year'));
            	$end_time = date('Y-12-31 23:59:59', strtotime('last year'));
			}
		}elseif($range_type=='CUSTOM'){
            if ($timestamp==true) {
                if (empty($start_date)) {
                    $start_date = date('Y-m-d');
                }
                if (empty($end_date)) {
                    $end_date = date('Y-m-d');
                }
                $start_time = strtotime(date("Y-m-d 00:00:00", strtotime($start_date)));
                $end_time = strtotime(date("Y-m-d 23:59:59", strtotime($end_date)));
            }else{
				if (empty($start_date)) {
                    $start_date = date('Y-m-d');
                }
                if (empty($end_date)) {
                    $end_date = date('Y-m-d');
                }
                $start_time = date("Y-m-d 00:00:00", strtotime($start_date));
                $end_time = date("Y-m-d 23:59:59", strtotime($end_date));
			}
		}else{
            if ($timestamp==true) {
                if ($show_all==true) {
                    $start_time = 946665000; // 2000-01-01 00:00:00
                    $end_time = strtotime(date('Y-m-d 23:59:59'));
                } else {
                    $start_time = strtotime('today');
                    $end_time = strtotime(date('Y-m-d 23:59:59'));
                }
            }else{
				if ($show_all==true) {
                    $start_time = '2000-01-01 00:00:00';
                    $end_time = date('Y-m-d 23:59:59');
                } else {
                    $start_time = date('Y-m-d 00:00:00');
                    $end_time = date('Y-m-d 23:59:59');
                }
			}
		}
		return ['start_time'=>$start_time, 'end_time'=>$end_time];
	}
}

if ( ! function_exists('humanize'))
{
	function humanize($str, $separator = '_')
	{
		return ucwords(preg_replace('/['.preg_quote($separator).']+/', ' ', trim(strtolower($str))));
	}
}

if(!function_exists('generate_uniq_order_formatted_id')){
	function get_uniq_order_formatted_id($order_prefix=''){
		$prefix = 'ODR';
		if(!empty($order_prefix)){
			$prefix = $order_prefix;
		}
		$random_code = time();
		$formatted_id = $prefix.rand(111,999).$random_code;
		return $formatted_id;
	}
}

if(!function_exists('verify_app_version')){
	function verify_app_version($device_type='A', $app_version=1){
		$min_app_version = intval(config('site_setting.APP_VERSION'));
		// $min_app_version_android = config('site_setting.MINIMUM_APP_VERSION_ANDROID');
		// $min_app_version_ios = config('site_setting.MINIMUM_APP_VERSION_IOS');
		$apk_url = config('site_setting.DOWNLOAD_URL');
		$response = [];
		if($device_type=='I'){
            if($app_version < $min_app_version){
				http_response_code(200);
				// http_response_code(426);
				$response['status'] = 0;
                $response['success'] = false;
                $response['data'] = (object)['version'=>(string)$min_app_version];
				$response['message'] = __('api.update_version');
				$response['url'] = $apk_url;
				$response['min_app_version'] = $min_app_version;
				header("Content-Type: application/json");
				echo json_encode($response); die;
			}
        }else{
            if($app_version < $min_app_version){
				http_response_code(200);
				// http_response_code(426);
				$response['status'] = 0;
                $response['success'] = false;
                $response['data'] = (object)['version'=>(string)$min_app_version];
				$response['message'] = __('api.update_version');
				$response['url'] = $apk_url;
				$response['min_app_version'] = $min_app_version;
				header("Content-Type: application/json");
				echo json_encode($response); die;
			}
        }
	}
}

if(!function_exists('pending_withdrawal_req_counter')){
	function pending_withdrawal_req_counter(){
		$result = 0;
		$key = 'pending_withdrawal_req_counter';
        if (Cache::has($key)) {
            $result = Cache::get($key);
        } else {
            $result = \DB::table('payment_withdrawal_transaction')->where(['status' => 'PENDING'])->count();
        }
        return $result;
	}
}

if(!function_exists('pending_kyc_counter')){
	function pending_kyc_counter(){
		$result = 0;
		$key = 'pending_kyc_counter';
        if (Cache::has($key)) {
            $result = Cache::get($key);
        } else {
            $result = \DB::table('kyc_documents')->where('status', 'Inreview')->count();
        }
        return $result;
	}
}

if(!function_exists('pending_contact_inquiries_counter')){
	function pending_contact_inquiries_counter(){
		$result = 0;
		$key = 'pending_contact_inquiries_counter';
        if (Cache::has($key)) {
            $result = Cache::get($key);
        } else {
            $result = \DB::table('contact_us_requests')->where('seen', 0)->count();
		}
        return $result;
	}
}

// End - Custom Script
