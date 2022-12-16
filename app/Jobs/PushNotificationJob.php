<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\User;

use App\Models\Broadcasts;
use Carbon\Carbon;
use DB;
use Spatie\Permission\Models\Role;

class PushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $broadcast_id;
    public function __construct($broadcast_id)
    {
        $this->broadcast_id =$broadcast_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(){
        $Broadcasts = Broadcasts::find($this->broadcast_id);
        if($Broadcasts->send_to =="all"){
            $query ="SELECT device_token,user_id FROM user_devices";
            $data =DB::select($query);
            foreach(array_chunk($data, 1000) as $devices){
                $device_tokens = array();
                foreach($devices as $device){
                   $device_tokens[] = $device->device_token;
                }
               //dd($device_tokens);
                $this->sendPushnotification($Broadcasts,$device_tokens);
            }           
        }else{
            $users = User::select('id')->with('UserDevice')->whereIn('id',explode(",",$Broadcasts->send_to))->get();
            if(!empty($users)){
                $device_tokens = array();
                foreach ($users as $key => $user) {
                    if(!empty($user->UserDevice)){
                        $device_tokens[] = $user->UserDevice->device_token;
                    }
                }
                $this->sendPushnotification($Broadcasts,$device_tokens);
            }
        }

    }   
       
    public function sendPushnotification($Broadcasts,$device_tokens){
        $SERVER_API_KEY = config('site_setting.FCM_KEY');
        if(empty($SERVER_API_KEY)){
            return false;
        }        
        $device_type ="";        
        $url = 'https://fcm.googleapis.com/fcm/send';        
        $image ="";
        if(!empty($Broadcasts->image))
            $image = asset('uploads/Broadcasts/'.$Broadcasts->image);

         if(!empty($Broadcasts->image_link))
            $image =$Broadcasts->image_link;

        $data = [
            "data"=>[
                'type'=>'notification',
                'id'=>$Broadcasts->id,
            ],
            "registration_ids" => $device_tokens,
            "notification" => [
                "title" => $Broadcasts->broadcast_title,
                "ticker" => $Broadcasts->broadcast_title,
                "body" => $Broadcasts->broadcast_message,  
                "vibrate" => 1,  
                "sound" => 1,  
                "image"=>$image
            ]
        ];
        $dataString = json_encode($data);        
        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];
        try {
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_POST,true);
            curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$dataString);
            $result = curl_exec($ch);
            curl_close($ch);
        }catch(\Exception $e) {
          echo 'Message: ' .$e->getMessage();
        }

    }
   
}
