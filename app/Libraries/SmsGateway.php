<?php

namespace App\Helpers;

class SmsGateway
{
    public static function send_sms($mobiles, $message)
    {
        // Code Ref URL: https://api.msg91.com/apidoc/samplecode/php-sample-code-send-sms.php
        if(empty($mobiles) || empty($message)){
            $response = ['status'=>0, 'message'=>'Mobile and message should not be empty!'];
            return $response;
        }
        //Your authentication key
        $authKey = config('custom.SMS91_AUTHKEY');

        //Multiple mobiles numbers separated by comma
        if(is_array($mobiles)){
            // $mobiles = array_map('phone_with_country_code', $mobiles);
        }else{
            // $mobiles = phone_with_country_code($mobiles);
        }

        //Sender ID,While using route4 sender id should be 6 characters long.
        $senderId = config('custom.SMS91_SENDERID');

        //Your message to send, Add URL encoding here.
        $message = urlencode($message);

        //Define route 
        $route = "default"; // In API for Transactional SMS use route=4 & for Promotional SMS use route=1

        //Prepare you post parameters
        $postData = array(
            'authkey'   => $authKey,
            'mobiles'   => $mobiles,
            'message'   => $message,
            'sender'    => $senderId,
            'route'     => $route
        );

        //API URL
        $url = config('custom.SMS91_API_URL');

        // init the resource
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData
            //,CURLOPT_FOLLOWLOCATION => true
        ));

        //Ignore SSL certificate verification
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

        //get response
        $response   = curl_exec($curl);
        $err        = curl_error($curl);
        curl_close($curl);

        //Print error if any
        if ($err):
            // echo "cURL Error #:" . $err; die();
            save_log('MSG91 Response(Error): '.$err);
            return FALSE;
        else:
            save_log('MSG91 Response(Success): '.json_encode($response));
            $response = json_decode($response,TRUE);
            $response['success'] = $response['success'] ?? '';
            if(!empty($response['success']) && $response['success']):
                return $response;
            else:
                return FALSE;
            endif;
        endif;


        $response = ['status'=>1, 'message'=>'SMS sent successfully'];
        return $response;
    }
}
