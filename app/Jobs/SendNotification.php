<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $data;

    public function __construct($data=[])
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user_id = $this->data['user_id'] ?? 0;
        $type = $this->data['type'] ?? config('custom.notification_types')['general'];
        $title = $this->data['title'] ?? '';
        $message = $this->data['message'] ?? '';
        $adata = $this->data['adata'] ?? [];
        $device_token = $this->data['device_token'] ?? '';
        $device_type = $this->data['device_type'] ?? 'A';
        $isSilent = (isset($this->data['isSilent']) && ($this->data['isSilent']===true)) ? true : false;
        send_notification($user_id,$type,$title,$message,$adata,$device_token,$device_type,$isSilent);
    }
}
