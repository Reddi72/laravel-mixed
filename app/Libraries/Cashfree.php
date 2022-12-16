<?php

namespace App\Libraries;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class Cashfree{

    private $payment_mode = '';
    private $host = '';
    private $payout_host = '';
    private $order_host = '';
    private $payout_auth_token = '';
    private $app_id = '';
    private $secret_key = '';
    private $payout_client_id = '';
    private $payout_client_secret = '';

    public function __construct()
    {
		$test_mode = config('site_setting.CASHFREE_TEST_MODE_STATUS') ?? 1;

        if(!$test_mode){
            $this->payment_mode = 'PROD';
            $this->host = 'https://api.cashfree.com/';
            $this->payout_host = 'https://payout-api.cashfree.com/';
            $this->order_host = 'https://api.cashfree.com/';
			$this->app_id = config('site_setting.CASHFREE_APP_ID');
			$this->secret_key = config('site_setting.CASHFREE_SECRET_KEY');
			$this->payout_client_id = config('site_setting.CASHFREE_PAYOUT_CLIENT_ID');
			$this->payout_client_secret = config('site_setting.CASHFREE_PAYOUT_CLIENT_SECRET');
        }else{
            $this->payment_mode = 'TEST';
            $this->host = 'https://test.cashfree.com/';
            $this->payout_host = 'https://payout-gamma.cashfree.com/';
            $this->order_host = 'https://sandbox.cashfree.com/';
			$this->app_id = config('site_setting.CASHFREE_APP_ID_TEST');
			$this->secret_key = config('site_setting.CASHFREE_SECRET_KEY_TEST');
			$this->payout_client_id = config('site_setting.CASHFREE_PAYOUT_CLIENT_ID_TEST');
			$this->payout_client_secret = config('site_setting.CASHFREE_PAYOUT_CLIENT_SECRET_TEST');
        }
		// save_log('Cashfree init method log: test_mode:'.$test_mode.', payment_mode: '.$this->payment_mode.', host: '.$this->host.', app_id: '.$this->app_id.', secret_key: '.$this->secret_key.', payout_client_id: '.$this->payout_client_id.', payout_client_secret: '.$this->payout_client_secret);
    }

    public function index()
    {
        return false;die;
    }

    function verify_signature($data, $payout=false){
		$response = false;
		$secret_key = $this->secret_key;
		if($payout){
			$secret_key = $this->payout_client_secret;
		}
		$signature = $data["signature"];
		unset($data["signature"]); // $data now has all the POST parameters except signature
		unset($data["request_data_json_encoded"]); // remove own created variable
		ksort($data); // Sort the $data array based on keys
		$postData = "";
		foreach ($data as $key => $value)
		{
			if (strlen($value) > 0)
			{
				$postData .= $value;
			}
		}
		// echo $postData;die;
		$hash_hmac = hash_hmac('sha256', $postData, $secret_key, true);
		// Use the clientSecret from the oldest active Key Pair.
		$computedSignature = base64_encode($hash_hmac);
		if ($signature == $computedSignature)
		{
			// Proceed based on $event
			$response = true;
		} else {
			// Reject this call
			$response = false;
			// Log for error
			$error_message = 'Cashfree-'.$this->payment_mode.' Error: Signature mismatch, data: '.json_encode($data).', signature: '.$signature.', computedSignature: '.$computedSignature;
			save_log($error_message);
			// echo 'signature: '.$signature.', computedSignature: '.$computedSignature;die;
		}
		return $response;
	}
	
	function generate_token($data)
	{
		$user_id = isset($data['user_id']) ? $data['user_id'] : 0;
		$amount = $data['amount'] ?? 0;
		$currency = isset($data['currency']) ? $data['currency'] : 'INR';
		$order_id = $data['order_id'] ?? 'CFORDER'.time();

        // Set URL
        $url = $this->host . "api/v2/cftoken/order";
        // Set post data
        $post_data = [
            'orderId' =>  $order_id,
            'orderAmount' => $amount,
            'orderCurrency' => $currency,
        ];
        // Set headers
        $headers = [
            'x-client-id' => $this->app_id,
            'x-client-secret' => $this->secret_key,
            'Content-Type' => 'application/json'
        ];
        try{
            // Initialize HTTP client
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', $url, ['json' => $post_data, 'headers' => $headers]);
            $raw_response_body = $response->getBody();
            $response_body = json_decode($raw_response_body, true); // body response
            $status_code = $response->getStatusCode(); // status code
            if($status_code==200){
                $error_message = 'Cashfree-'.$this->payment_mode.' Success: Method: create order, Response: '.trim($raw_response_body);
                save_log($error_message);
                $cftoken = $response_body["cftoken"];
                return $cftoken;
            }else{
                // Cashfree error occured
                $error_message = 'Cashfree-'.$this->payment_mode.' Error: Method: create order, Message: '.$response_body["message"].', User Id-'.$user_id.', Amount: '.$amount.', Response: '.$raw_response_body;
                save_log($error_message);
                return false;
            }
        }catch (\Exception $e) {
            $message = $e->getMessage();
            // Cashfree error occured
            $error_message = 'Cashfree-'.$this->payment_mode.' Exception Error: Method: create order, Message: '.$message.', User Id-'.$user_id.', Amount: '.$amount;
            save_log($error_message);
            return false;
        }
	}

    function verify_payment($response_data=[])
    {
        $response = false;
		if(!empty($response_data) && isset($response_data["orderId"]))
		{
			$request_data_json_encoded = isset($response_data['request_data_json_encoded']) ? $response_data['request_data_json_encoded'] : '';
			$orderId = $response_data["orderId"];
			$orderAmount = $response_data["orderAmount"];
			$referenceId = $response_data["referenceId"];
			$txStatus = $response_data["txStatus"]; // txStatus can be SUCCESS, FLAGGED, PENDING, FAILED, CANCELLED, USER_DROPPED - https://docs.cashfree.com/docs/transaction-lifecycle#payment-state-details
			$paymentMode = $response_data["paymentMode"];
			$txMsg = $response_data["txMsg"];
			$txTime = $response_data["txTime"];
			$signature = $response_data["signature"];
			$data = $orderId.$orderAmount.$referenceId.$txStatus.$paymentMode.$txMsg.$txTime;
			$hash_hmac = hash_hmac('sha256', $data, $this->secret_key, true) ;
			$computedSignature = base64_encode($hash_hmac);
			if ($signature == $computedSignature)
			{
				$orderAmount = float_amount($orderAmount);
				// Get Transaction Details
				$payment_transaction = DB::table('payment_transaction')->where('transaction_order_id',$orderId)->first();
				if(!empty($payment_transaction) && ($payment_transaction->transaction_status=='PENDING' || $payment_transaction->transaction_status=='FLAGGED'))
				{
					if(float_amount($payment_transaction->transaction_amount)==$orderAmount)
					{
						$user_id = $payment_transaction->user_id;
						$update_data = [];
						$update_data['transaction_reference_id'] = $referenceId;
						$update_data['transaction_status'] = $txStatus;
						// $update_data['transaction_type'] = $txStatus;
						if($txStatus == 'SUCCESS')
						{
							$update_data['transaction_type'] = 'Paid';
						}elseif($txStatus == 'FAILED' || $txStatus == 'CANCELLED'){
							$update_data['transaction_type'] = 'Failed';
						}elseif($txStatus == 'USER_DROPPED'){
							$update_data['transaction_type'] = 'Cancelled';
						}elseif(($response_body['payment_status'] == 'NOT_ATTEMPTED') || ($response_body['payment_status'] == 'FLAGGED')){
							$update_data['transaction_type'] = 'Pending';
							$update_data['transaction_status'] = 'PENDING';
						}
						$update_data['transaction_payment_mode'] = strtolower($paymentMode);
						// $update_data['transaction_message'] = ltrim($txMsg,'00::');
						$update_data['transaction_message'] = str_replace('00::', '', $txMsg);
						$update_data['transaction_signature'] = $signature;
						$update_data['transaction_time'] = strtotime($txTime);
						$update_data['cf_response'] = $request_data_json_encoded;
						$update_where = ['transaction_order_id'=>$orderId, 'transaction_id'=>$payment_transaction->transaction_id];
						$update = DB::table('payment_transaction')->where($update_where)->update($update_data);
						if($update)
						{
							if($txStatus=='SUCCESS')
							{
								$response = true;

								// Check coupon code
								$coupon_additional_amount = 0;
								$coupon_code = $payment_transaction->coupon_code;
								if(!empty($coupon_code))
								{
									$time = time();
									$coupon_data = Db::table('coupons')->where(['coupon_code'=>$coupon_code, 'coupon_status'=>'Active'])->where('coupon_start_date','<=',$time)->where('coupon_end_date','>=',$time)->whereColumn('coupon_use_limit', '>', 'coupon_use_count')->first();
									if(!empty($coupon_data))
									{
										$coupon_min_amount = $coupon_data->coupon_discount_on_min_amount;
										if($orderAmount >= $coupon_min_amount){
											if($coupon_data->coupon_discount_type == 'F')
											{
												$coupon_additional_amount = $coupon_data->coupon_discount;
											}elseif($coupon_data->coupon_discount_type == 'P'){
												$coupon_percentage = $coupon_data->coupon_discount;
												$coupon_max_discount = intval($coupon_data->coupon_max_discount);
												$discount_amount = float_amount(($orderAmount * $coupon_percentage) / 100);
												if($discount_amount > $coupon_max_discount)
												{
													$coupon_additional_amount = $coupon_max_discount;
												}else{
													$coupon_additional_amount = $discount_amount;
												}
											}
											DB::table('coupons')->where(['coupon_id'=>$coupon_data->coupon_id, 'coupon_code'=>$coupon_code])->increment('coupon_use_count');
										}
									}
								}
								$orderAmount = $orderAmount + $coupon_additional_amount;

								// Add money to user wallet
								$transaction_message = sprintf('₹%d added in your wallet',$orderAmount);
								$extra_data = ['internal_order_id'=>$orderId,'wallet_histroy_message'=>$transaction_message];
								$result = User::add_balance($user_id, $orderAmount, 'user_wallet_balance', $extra_data);
								// End - Wallet history

								// Begin - Send notification
								$n_title = 'Money Added Successfully';
								$n_message = $transaction_message;
								$adata = ['amount'=>$orderAmount];
								send_notification($user_id, 'WALLET', $n_title, $n_message, $adata);
								// End - Send notification
							}else{
								$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail. Txn Status is not "SUCCESS"!';
								save_log($log_data);
							}
						}else{
							$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in Payment Data Updation!';
							save_log($log_data);
						}
					}else{
						// Invalid Transaction Amount
						$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in amount validation! Order Amount: '.$payment_transaction->transaction_amount.', Paid Aamount: '.$orderAmount;
						save_log($log_data);
					}
				}else{
					// Transaction Already Proceed
					if(!empty($payment_transaction) && ($payment_transaction->transaction_status=='SUCCESS'))
					{
						$response = true;
					}
					// Transaction Status Isseu
					$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in status validation! Transaction Status: '.$payment_transaction->transaction_status;
					save_log($log_data);
				}
			} else {
				// Reject this call
				$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in signature validation! Signature: '.$signature.', ComputedSignature: '.$computedSignature;
				save_log($log_data);
			}
			// Add Log
			$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Response: '.$request_data_json_encoded;
			save_log($log_data);
		} elseif (!empty($response_data) && isset($response_data['type'])){
			$payment = $response_data['data']['payment'];
			$order = $response_data['data']['order'];
			// Get Transaction Details
			$payment_transaction = DB::table('payment_transaction')->where('transaction_order_id',$order['order_id'])->first();
			if(!empty($payment_transaction) && ($payment_transaction->transaction_status=='PENDING'))
			{
				$user_id = $payment_transaction->user_id;
				$request_data_json_encoded = isset($response_data['request_data_json_encoded']) ? $response_data['request_data_json_encoded'] : '';

				// Begin - Update Transaction Entry in Databse
				$transaction_type = 'Pending';
				if($response_data["type"]=='PAYMENT_SUCCESS_WEBHOOK'){
					$transaction_type = 'Paid';
				}elseif($response_data["type"]=='PAYMENT_USER_DROPPED_WEBHOOK'){
					$transaction_type = 'Cancelled';
				}elseif($response_data["type"]=='PAYMENT_FAILED_WEBHOOK'){
					$transaction_type = 'Failed';
				}
				$update_data = [];
				$update_data['transaction_type'] = $transaction_type;
				$update_data['transaction_reference_id'] = $payment['cf_payment_id'];
				$update_data['transaction_status'] = $payment['payment_status'];
				$update_data['transaction_payment_mode'] = strtolower($payment['payment_group']);
				$update_data['transaction_message'] = str_replace('00::', '', $payment['payment_message']);
				$update_data['transaction_signature'] = '';
				$update_data['transaction_time'] = strtotime($payment['payment_time']);
				$update_data['cf_response'] = $request_data_json_encoded;
				$update_where = ['transaction_order_id'=>$order['order_id'], 'transaction_id'=>$payment_transaction->transaction_id];
				$update = DB::table('payment_transaction')->where($update_where)->update($update_data);
				// End - Update Transaction Entry in Databse

				if($response_data["type"]=='PAYMENT_SUCCESS_WEBHOOK')
				{
					$payment_amount = float_amount($payment['payment_amount']);
					if(float_amount($payment_transaction->transaction_amount)==$payment_amount)
					{
						$response = true;

						// Check coupon code
						$coupon_additional_amount = 0;
						$coupon_code = $payment_transaction->coupon_code;
						if(!empty($coupon_code))
						{
							$time = time();
							$coupon_data = Db::table('coupons')->where(['coupon_code'=>$coupon_code, 'coupon_status'=>'Active'])->where('coupon_start_date','<=',$time)->where('coupon_end_date','>=',$time)->whereColumn('coupon_use_limit', '>', 'coupon_use_count')->first();
							if(!empty($coupon_data))
							{
								$coupon_min_amount = $coupon_data->coupon_discount_on_min_amount;
								if($payment_amount >= $coupon_min_amount){
									if($coupon_data->coupon_discount_type == 'F')
									{
										$coupon_additional_amount = $coupon_data->coupon_discount;
									}elseif($coupon_data->coupon_discount_type == 'P'){
										$coupon_percentage = $coupon_data->coupon_discount;
										$coupon_max_discount = intval($coupon_data->coupon_max_discount);
										$discount_amount = float_amount(($payment_amount * $coupon_percentage) / 100);
										if($discount_amount > $coupon_max_discount)
										{
											$coupon_additional_amount = $coupon_max_discount;
										}else{
											$coupon_additional_amount = $discount_amount;
										}
									}
									DB::table('coupons')->where(['coupon_id'=>$coupon_data->coupon_id, 'coupon_code'=>$coupon_code])->increment('coupon_use_count');
								}
							}
						}
						$payment_amount = $payment_amount + $coupon_additional_amount;

						// Add money to user wallet
						$transaction_message = sprintf('₹%d added in your wallet',$payment_amount);
						$extra_data = ['internal_order_id'=>$order['order_id'],'wallet_histroy_message'=>$transaction_message];
						$result = User::add_balance($user_id, $payment_amount, 'user_wallet_balance', $extra_data);
						// End - Wallet history

						// Begin - Send notification
						$n_title = 'Money Added Successfully';
						$n_message = $transaction_message;
						$adata = ['amount'=>$payment_amount];
						send_notification($user_id, 'WALLET', $n_title, $n_message, $adata);
						// End - Send notification
					}else{
						// Invalid Transaction Amount
						$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in amount validation! Order Amount: '.$payment_transaction->transaction_amount.', Paid Aamount: '.$payment_amount;
						save_log($log_data);
					}
				}elseif($response_data["type"]=='PAYMENT_USER_DROPPED_WEBHOOK'){
					// Begin - Send notification
					$n_title = 'Payment Failed!';
					$n_message = 'We were unable to process your payment';
					$adata = [];
					send_notification($user_id, 'WALLET', $n_title, $n_message, $adata);
					// End - Send notification
				}elseif($response_data["type"]=='PAYMENT_FAILED_WEBHOOK'){
					// Begin - Send notification
					$n_title = 'Payment Failed!';
					$n_message = 'We were unable to process your payment';
					$adata = [];
					send_notification($user_id, 'WALLET', $n_title, $n_message, $adata);
					// End - Send notification
				}else{
					// Invalid Webhook Type
					$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in Type! Type: '.$response_data["type"];
					save_log($log_data);
				}
			}else{
				// Invalid Transaction Order Id
				$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail Due to Transaction not Availabe in Database! Order Id: '.$order['order_id'];
				save_log($log_data);
			}
		}else{
			$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Called Without Parameters or Order ID! Parameters: '.json_encode($response_data);
			save_log($log_data);
		}
		return $response;
    }

	function get_payment_status($orderId='')
	{
		$response = false;
		if(!empty($orderId))
		{
			$payment_transaction = DB::table('payment_transaction')->select('transaction_id','user_id','transaction_status','transaction_type','transaction_amount','coupon_code')->where('transaction_internal_order_id',$orderId)->first();
			if(empty($payment_transaction)){
				return $response;
			}
			$transaction_status = $payment_transaction->transaction_status;
			$transaction_type = $payment_transaction->transaction_type;
			$transaction_amount = $payment_transaction->transaction_amount;
			try{
				// Set URL
				$url = $this->order_host . "pg/orders/" . $orderId . "/payments";
				// Set headers
				$headers = [
					'x-client-id' => $this->app_id,
					'x-client-secret' => $this->secret_key,
					'x-api-version' => '2022-01-01',
					'Content-Type' => 'application/json'
				];
				// Initialize HTTP client
				$client = new \GuzzleHttp\Client(['headers' => $headers]);
				$http_response = $client->request('GET', $url);
				$raw_response_body = $http_response->getBody();
				$response_body = json_decode($raw_response_body, true); // body response
				$status_code = $http_response->getStatusCode(); // status code
				if($status_code==200){
					if(!empty($response_body) && isset($response_body[0])){
						$log_message = 'Cashfree-'.$this->payment_mode.' Success: Method: pg/orders/order_id/payments, Response: '.trim($raw_response_body);
						save_log($log_message);
						$response_body = $response_body[0];
						$response = true;
						$payment_amount = $response_body['order_amount'];
						if(!empty($payment_transaction) && ($transaction_status=='PENDING' || $transaction_status=='FLAGGED'))
						{
							if(float_amount($transaction_amount)==$payment_amount)
							{
								$user_id = $payment_transaction->user_id;
								$update_data = [];
								$update_data['transaction_reference_id'] = $response_body['cf_payment_id'];
								$update_data['transaction_status'] = $response_body['payment_status'];
								if($response_body['payment_status'] == 'SUCCESS')
								{
									$update_data['transaction_type'] = 'Paid';
								}elseif($response_body['payment_status'] == 'FAILED' || $response_body['payment_status'] == 'CANCELLED'){
									$update_data['transaction_type'] = 'Failed';
								}elseif($response_body['payment_status'] == 'USER_DROPPED'){
									$update_data['transaction_type'] = 'Cancelled';
									$update_data['transaction_status'] = 'CANCELLED';
								}elseif(($response_body['payment_status'] == 'NOT_ATTEMPTED') || ($response_body['payment_status'] == 'FLAGGED')){
									$update_data['transaction_type'] = 'Pending';
									$update_data['transaction_status'] = 'PENDING';
								}
								$update_data['transaction_payment_mode'] = strtolower($response_body['payment_group']);
								$update_data['transaction_message'] = str_replace('00::', '', $response_body['payment_message']);
								$update_data['transaction_time'] = strtotime($response_body['payment_completion_time']);
								$update_data['cf_response'] = $raw_response_body;
								$update_where = ['transaction_order_id'=>$orderId, 'transaction_id'=>$payment_transaction->transaction_id];
								$update = DB::table('payment_transaction')->where($update_where)->update($update_data);
								if($update)
								{
									if($response_body['payment_status']=='SUCCESS')
									{
										$response = true;

										// Check coupon code
										$coupon_additional_amount = 0;
										$coupon_code = $payment_transaction->coupon_code;
										if(!empty($coupon_code))
										{
											$time = time();
											$coupon_data = Db::table('coupons')->where(['coupon_code'=>$coupon_code, 'coupon_status'=>'Active'])->where('coupon_start_date','<=',$time)->where('coupon_end_date','>=',$time)->whereColumn('coupon_use_limit', '>', 'coupon_use_count')->first();
											if(!empty($coupon_data))
											{
												$coupon_min_amount = $coupon_data->coupon_discount_on_min_amount;
												if($payment_amount >= $coupon_min_amount){
													if($coupon_data->coupon_discount_type == 'F')
													{
														$coupon_additional_amount = $coupon_data->coupon_discount;
													}elseif($coupon_data->coupon_discount_type == 'P'){
														$coupon_percentage = $coupon_data->coupon_discount;
														$coupon_max_discount = intval($coupon_data->coupon_max_discount);
														$discount_amount = float_amount(($payment_amount * $coupon_percentage) / 100);
														if($discount_amount > $coupon_max_discount)
														{
															$coupon_additional_amount = $coupon_max_discount;
														}else{
															$coupon_additional_amount = $discount_amount;
														}
													}
													DB::table('coupons')->where(['coupon_id'=>$coupon_data->coupon_id, 'coupon_code'=>$coupon_code])->increment('coupon_use_count');
												}
											}
										}
										$payment_amount = $payment_amount + $coupon_additional_amount;

										// Add money to user wallet
										$transaction_message = sprintf('₹%d added in your wallet',$payment_amount);
										$extra_data = ['internal_order_id'=>$orderId,'wallet_histroy_message'=>$transaction_message];
										$result = User::add_balance($user_id, $payment_amount, 'user_wallet_balance', $extra_data);
										// End - Wallet history

										// Begin - Send notification
										$n_title = 'Money Added Successfully';
										$n_message = $transaction_message;
										$adata = ['amount'=>$payment_amount];
										send_notification($user_id, 'WALLET', $n_title, $n_message, $adata);
										// End - Send notification
									}else{
										$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail. Txn Status is not "SUCCESS"!';
										save_log($log_data);
									}
								}else{
									$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in Payment Data Updation!';
									save_log($log_data);
								}
							}else{
								// Invalid Transaction Amount
								$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in amount validation! Order Amount: '.$payment_transaction->transaction_amount.', Paid Aamount: '.$payment_amount;
								save_log($log_data);
							}
						}else{
							// Transaction Already Proceed
							if(!empty($payment_transaction) && ($payment_transaction->transaction_status=='SUCCESS'))
							{
								$response = true;
							}
							// Transaction Status Isseu
							$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in status validation! Transaction Status: '.$payment_transaction->transaction_status;
							save_log($log_data);
							return $response;
						}
					}else{
						// Cashfree error occured
						$error_message = 'Cashfree-'.$this->payment_mode.' Error: Method: pg/orders/order_id/payments, orderId-'.$orderId.', Message: '.$response_body["message"].', Response: '.$raw_response_body;
						save_log($error_message);
						return false;
					}
				}else{
					// Cashfree error occured
					$error_message = 'Cashfree-'.$this->payment_mode.' Error: Method: pg/orders/order_id/payments, orderId-'.$orderId.', Message: '.$response_body["message"].', Response: '.$raw_response_body;
					save_log($error_message);
					return false;
				}
			}catch (\Exception $e) {
				$message = $e->getMessage();
				// Cashfree error occured
				$error_message = 'Cashfree-'.$this->payment_mode.' Exception Error: Method: pg/orders/order_id/payments, orderId-'.$orderId.', Message: '.$message;
				save_log($error_message);
				return false;
			}
		}
		return $response;
	}

	function verify_payment_tmp($response_data=[])
    {
        $response = false;
		if($this->payment_mode == 'PROD')
		{
			if(!empty($response_data) && isset($response_data["orderId"]))
			{
				$request_data_json_encoded = isset($response_data['request_data_json_encoded']) ? $response_data['request_data_json_encoded'] : '';
				$orderId = $response_data["orderId"];
				$orderAmount = $response_data["orderAmount"];
				$referenceId = $response_data["referenceId"];
				$txStatus = $response_data["txStatus"]; // txStatus can be SUCCESS, FLAGGED, PENDING, FAILED, CANCELLED, USER_DROPPED
				$paymentMode = $response_data["paymentMode"];
				$txMsg = $response_data["txMsg"];
				$txTime = $response_data["txTime"];
				$signature = $response_data["signature"];
				$data = $orderId.$orderAmount.$referenceId.$txStatus.$paymentMode.$txMsg.$txTime;
				$hash_hmac = hash_hmac('sha256', $data, $this->secret_key, true) ;
				$computedSignature = base64_encode($hash_hmac);
				if ($signature == $computedSignature)
				{
					$orderAmount = float_amount($orderAmount);
					// Get Transaction Details
					$payment_transaction = DB::table('payment_transaction')->where('transaction_order_id',$orderId)->first();
					if(!empty($payment_transaction) && ($payment_transaction->transaction_status=='PENDING' || $payment_transaction->transaction_status=='FLAGGED'))
					{
						if(float_amount($payment_transaction->transaction_amount)==$orderAmount)
						{
							$user_id = $payment_transaction->user_id;
							$update_data = [];
							$update_data['transaction_reference_id'] = $referenceId;
							$update_data['transaction_status'] = $txStatus;
							$update_data['transaction_type'] = $txStatus;
							if($txStatus == 'SUCCESS')
							{
								$update_data['transaction_type'] = 'Paid';
							}elseif($txStatus == 'FAILED' || $txStatus == 'CANCELLED'){
								$update_data['transaction_type'] = 'Failed';
							}elseif($txStatus == 'USER_DROPPED'){
								$update_data['transaction_type'] = 'Cancelled';
							}
							$update_data['transaction_payment_mode'] = strtolower($paymentMode);
							// $update_data['transaction_message'] = ltrim($txMsg,'00::');
							$update_data['transaction_message'] = str_replace('00::', '', $txMsg);
							$update_data['transaction_signature'] = $signature;
							$update_data['transaction_time'] = strtotime($txTime);
							$update_data['cf_response'] = $request_data_json_encoded;
							$update_where = ['transaction_order_id'=>$orderId, 'transaction_id'=>$payment_transaction->transaction_id];
							$update = DB::table('payment_transaction')->where($update_where)->update($update_data);
							if($update)
							{
								if($txStatus=='SUCCESS')
								{
									$response = true;
									// Add money to user wallet
									$transaction_message = sprintf('₹%d added in your wallet',$orderAmount);
									$extra_data = ['internal_order_id'=>$orderId,'wallet_histroy_message'=>$transaction_message];
									$result = User::add_balance($user_id, $payment_transaction->transaction_amount, 'user_wallet_balance', $extra_data);
									// End - Wallet history

									// Begin - Send notification
									$n_title = 'Money Added Successfully';
									$n_message = $transaction_message;
									$adata = ['amount'=>$orderAmount];
									send_notification($user_id, 'WALLET', $n_title, $n_message, $adata);
									// End - Send notification
								}else{
									$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail. Txn Status is not "SUCCESS"!';
									save_log($log_data);
								}
							}else{
								$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in Payment Data Updation!';
								save_log($log_data);
							}
						}else{
							// Invalid Transaction Amount
							$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in amount validation! Order Amount: '.$payment_transaction->transaction_amount.', Paid Aamount: '.$orderAmount;
							save_log($log_data);
						}
					}else{
						// Transaction Already Proceed
						if(!empty($payment_transaction) && ($payment_transaction->transaction_status=='SUCCESS'))
						{
							$response = true;
						}
						// Transaction Status Isseu
						$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in status validation! Transaction Status: '.$payment_transaction->transaction_status;
						save_log($log_data);
					}
				} else {
					// Reject this call
					$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in signature validation! Signature: '.$signature.', ComputedSignature: '.$computedSignature;
					save_log($log_data);
				}
				// Add Log
				$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Response: '.$request_data_json_encoded;
				save_log($log_data);
			}else{
				$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Called Without Parameters or Order ID! Parameters: '.json_encode($response_data);
				save_log($log_data);
			}
		}else{
			if(!empty($response_data) && isset($response_data['type']))
			{
				$payment = $response_data['data']['payment'];
				$order = $response_data['data']['order'];
				// Get Transaction Details
				$payment_transaction = DB::table('payment_transaction')->where('transaction_order_id',$order['order_id'])->first();
				if(!empty($payment_transaction) && ($payment_transaction->transaction_status=='PENDING'))
				{
					$user_id = $payment_transaction->user_id;
					$request_data_json_encoded = isset($response_data['request_data_json_encoded']) ? $response_data['request_data_json_encoded'] : '';

					// Begin - Update Transaction Entry in Databse
					$transaction_type = 'Pending';
					if($response_data["type"]=='PAYMENT_SUCCESS_WEBHOOK'){
						$transaction_type = 'Paid';
					}elseif($response_data["type"]=='PAYMENT_USER_DROPPED_WEBHOOK'){
						$transaction_type = 'Cancelled';
					}elseif($response_data["type"]=='PAYMENT_FAILED_WEBHOOK'){
						$transaction_type = 'Failed';
					}
					$update_data = [];
					$update_data['transaction_type'] = $transaction_type;
					$update_data['transaction_reference_id'] = $payment['cf_payment_id'];
					$update_data['transaction_status'] = $payment['payment_status'];
					$update_data['transaction_payment_mode'] = strtolower($payment['payment_group']);
					$update_data['transaction_message'] = str_replace('00::', '', $payment['payment_message']);
					$update_data['transaction_signature'] = '';
					$update_data['transaction_time'] = strtotime($payment['payment_time']);
					$update_data['cf_response'] = $request_data_json_encoded;
					$update_where = ['transaction_order_id'=>$order['order_id'], 'transaction_id'=>$payment_transaction->transaction_id];
					$update = DB::table('payment_transaction')->where($update_where)->update($update_data);
					// End - Update Transaction Entry in Databse

					if($response_data["type"]=='PAYMENT_SUCCESS_WEBHOOK')
					{
						$payment_amount = float_amount($payment['payment_amount']);
						if(float_amount($payment_transaction->transaction_amount)==$payment_amount)
						{
							$response = true;

							// Check coupon code
							$coupon_additional_amount = 0;
							$coupon_code = $payment_transaction->coupon_code;
							if(!empty($coupon_code))
							{
								$time = time();
								$coupon_data = Db::table('coupons')->where(['coupon_code'=>$coupon_code, 'coupon_status'=>'Active'])->where('coupon_start_date','<=',$time)->where('coupon_end_date','>=',$time)->whereColumn('coupon_use_limit', '>', 'coupon_use_count')->first();
								if(!empty($coupon_data))
								{
									$coupon_min_amount = $coupon_data->coupon_discount_on_min_amount;
									if($payment_amount >= $coupon_min_amount){
										if($coupon_data->coupon_discount_type == 'F')
										{
											$coupon_additional_amount = $coupon_data->coupon_discount;
										}elseif($coupon_data->coupon_discount_type == 'P'){
											$coupon_percentage = $coupon_data->coupon_discount;
											$coupon_max_discount = intval($coupon_data->coupon_max_discount);
											$discount_amount = float_amount(($payment_amount * $coupon_percentage) / 100);
											if($discount_amount > $coupon_max_discount)
											{
												$coupon_additional_amount = $coupon_max_discount;
											}else{
												$coupon_additional_amount = $discount_amount;
											}
										}
										DB::table('coupons')->where(['coupon_id'=>$coupon_data->coupon_id, 'coupon_code'=>$coupon_code])->increment('coupon_use_count');
									}
								}
							}
							$payment_amount = $payment_amount + $coupon_additional_amount;

							// Add money to user wallet
							$transaction_message = sprintf('₹%d added in your wallet',$payment_amount);
							$extra_data = ['internal_order_id'=>$order['order_id'],'wallet_histroy_message'=>$transaction_message];
							$result = User::add_balance($user_id, $payment_amount, 'user_wallet_balance', $extra_data);
							// End - Wallet history

							// Begin - Send notification
							$n_title = 'Money Added Successfully';
							$n_message = $transaction_message;
							$adata = ['amount'=>$payment_amount];
							send_notification($user_id, 'WALLET', $n_title, $n_message, $adata);
							// End - Send notification
						}else{
							// Invalid Transaction Amount
							$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in amount validation! Order Amount: '.$payment_transaction->transaction_amount.', Paid Aamount: '.$payment_amount;
							save_log($log_data);
						}
					}elseif($response_data["type"]=='PAYMENT_USER_DROPPED_WEBHOOK'){
						// Begin - Send notification
						$n_title = 'Payment Failed!';
						$n_message = 'We were unable to process your payment';
						$adata = [];
						send_notification($user_id, 'WALLET', $n_title, $n_message, $adata);
						// End - Send notification
					}elseif($response_data["type"]=='PAYMENT_FAILED_WEBHOOK'){
						// Begin - Send notification
						$n_title = 'Payment Failed!';
						$n_message = 'We were unable to process your payment';
						$adata = [];
						send_notification($user_id, 'WALLET', $n_title, $n_message, $adata);
						// End - Send notification
					}else{
						// Invalid Webhook Type
						$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in Type! Type: '.$response_data["type"];
						save_log($log_data);
					}
				}else{
					// Invalid Transaction Order Id
					$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail Due to Transaction not Availabe in Database! Order Id: '.$order['order_id'];
					save_log($log_data);
				}
			}else{
				$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Called Without Parameters or Order ID! Parameters: '.json_encode($response_data);
				save_log($log_data);
			}
		}
		return $response;
    }

    function verify_payment_old_method($response_data=[])
    {
        $response = false;
		if(!empty($response_data) && isset($response_data["orderId"]))
		{
            $request_data_json_encoded = isset($response_data['request_data_json_encoded']) ? $response_data['request_data_json_encoded'] : '';
			$orderId = $response_data["orderId"];
			$orderAmount = $response_data["orderAmount"];
			$referenceId = $response_data["referenceId"];
			$txStatus = $response_data["txStatus"]; // txStatus can be SUCCESS, FLAGGED, PENDING, FAILED, CANCELLED, USER_DROPPED
			$paymentMode = $response_data["paymentMode"];
			$txMsg = $response_data["txMsg"];
			$txTime = $response_data["txTime"];
			$signature = $response_data["signature"];
			$data = $orderId.$orderAmount.$referenceId.$txStatus.$paymentMode.$txMsg.$txTime;
			$hash_hmac = hash_hmac('sha256', $data, $this->secret_key, true) ;
			$computedSignature = base64_encode($hash_hmac);
            if ($signature == $computedSignature)
			{
                $orderAmount = float_amount($orderAmount);
                // Get Transaction Details
                $payment_transaction = DB::table('payment_transaction')->where('transaction_order_id',$orderId)->first();
                if(!empty($payment_transaction) && ($payment_transaction->transaction_status=='PENDING' || $payment_transaction->transaction_status=='FLAGGED'))
                {
                    if(float_amount($payment_transaction->transaction_amount)==$orderAmount)
                    {
                        $user_id = $payment_transaction->user_id;
						$update_data = [];
						$update_data['transaction_reference_id'] = $referenceId;
						$update_data['transaction_status'] = $txStatus;
						// if($txStatus == 'SUCCESS' || $txStatus == 'FLAGGED')
						// {
						// 	$update_data['transaction_stat'] = 'Paid';
						// }elseif($txStatus == 'FAILED' || $txStatus == 'CANCELLED' || $txStatus == 'USER_DROPPED'){
						// 	$update_data['transaction_stat'] = 'Failed';
						// }
						$update_data['transaction_payment_mode'] = $paymentMode;
						$update_data['transaction_message'] = ltrim($txMsg,'00::');
						$update_data['transaction_signature'] = $signature;
						$update_data['transaction_time'] = strtotime($txTime);
						$update_data['cf_response'] = $request_data_json_encoded;
						$update_where = ['transaction_order_id'=>$orderId, 'transaction_id'=>$payment_transaction->transaction_id];
                        $update = DB::table('payment_transaction')->where($update_where)->update($update_data);
                        if($update)
						{
                            if($txStatus=='SUCCESS')
							{
                                $response = true;
								// Add money to user wallet
                                $transaction_message = sprintf('₹%d added in your wallet',$orderAmount);
                                $extra_data = ['internal_order_id'=>$orderId,'wallet_histroy_message'=>$transaction_message];
                                $result = User::add_balance($user_id, $payment_transaction->transaction_amount, 'user_wallet_balance', $extra_data);
                                // End - Wallet history

                                // Begin - Send notification
                                $n_title = 'Money Added Successfully';
                                $n_message = $transaction_message;
                                $adata = ['amount'=>$orderAmount];
                                send_notification($user_id, 'WALLET', $n_title, $n_message, $adata);
                                // End - Send notification
                            }else{
								$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail. Txn Status is not "SUCCESS"!';
								save_log($log_data);
							}
                        }else{
							$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in Payment Data Updation!';
							save_log($log_data);
						}
                    }else{
                        // Invalid Transaction Amount
						$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in amount validation! Order Amount: '.$payment_transaction->transaction_amount.', Paid Aamount: '.$orderAmount;
						save_log($log_data);
					}
                }else{
                    // Transaction Already Proceed
					if(!empty($payment_transaction) && ($payment_transaction->transaction_status=='SUCCESS'))
					{
						$response = true;
					}
                    // Transaction Status Isseu
					$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in status validation! Transaction Status: '.$payment_transaction->transaction_status;
					save_log($log_data);
				}
            } else {
				// Reject this call
				$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Fail in signature validation! Signature: '.$signature.', ComputedSignature: '.$computedSignature;
				save_log($log_data);
			}
			// Add Log
			$log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Response: '.$request_data_json_encoded;
			save_log($log_data);
        }else{
            $log_data = 'Cashfree-'.$this->payment_mode.' Payment Webhook Called Without Parameters or Order ID! Parameters: '.json_encode($response_data);
			save_log($log_data);
        }
		return $response;
    }

    function generate_auth_token()
	{
		$url = $this->payout_host . "payout/v1/authorize";
		// Get cURL resource
		$curl = curl_init();
		// Set some options - we are passing in a useragent too here
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Content-Type:application/json',
			'X-Client-Id: '.$this->payout_client_id,
			'X-Client-Secret: '.$this->payout_client_secret,
		));
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_POST => 1,
		));
		// Send the request & save response to $resp
		$resp = curl_exec($curl);
		// Close request to clear up some resources
		curl_close($curl);

        save_log('Cashfree-'.$this->payment_mode.' generate auth token (authorize) Response: '.$resp);
			
		$cf_response = json_decode($resp, true);
		if($cf_response['status']=='SUCCESS')
		{
			$this->payout_auth_token = $cf_response['data']['token'];
			return true;
		}else{
			return false;
		}
	}

    function add_beneficiary($beneficiary_data=[])
	{
		// $response = false;
		$response['status'] = 0;
		$response['message'] = '';
		if(!empty($beneficiary_data))
		{
			$this->generate_auth_token();
			$request_data = [
				'beneId'=>$beneficiary_data['beneficiary_id'],
				'name'=>$beneficiary_data['beneficiary_name'],
				'email'=>$beneficiary_data['beneficiary_email'],
				'phone'=>$beneficiary_data['beneficiary_phone'],
				'cardNo'=>'',
				'address1'=>$beneficiary_data['beneficiary_address'],
				'address2'=>'',
				'city'=>$beneficiary_data['beneficiary_city']??'',
				'state'=>$beneficiary_data['beneficiary_state']??'',
				'pincode'=>$beneficiary_data['beneficiary_pincode']??''
			];
			if($beneficiary_data['transfer_mode']=='banktransfer')
			{
				$request_data['bankAccount'] = $beneficiary_data['beneficiary_account_number'];
				$request_data['ifsc'] = $beneficiary_data['beneficiary_ifsc_code'];
			}elseif($beneficiary_data['transfer_mode']=='upi'){
				$request_data['vpa'] = $beneficiary_data['beneficiary_vpa'];
			}
			$url = $this->payout_host . "payout/v1/addBeneficiary";
			// Get cURL resource
			$curl = curl_init();
			// Set some options - we are passing in a useragent too here
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				'Content-Type:application/json',
				'Authorization: Bearer ' . $this->payout_auth_token,
			));
			curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $url,
				CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => json_encode ($request_data)
			));
			// Send the request & save response to $resp
			$resp = curl_exec($curl);
			// Close request to clear up some resources
			curl_close($curl);
			save_log('Cashfree-'.$this->payment_mode.' addBeneficiary Response: '.json_encode($resp));
			
			$cf_response = json_decode($resp, true);
			if($cf_response['status']=='SUCCESS')
			{
				// $response = true;
				$response['status'] = 1;
				$response['message'] = '';
			}elseif($cf_response['subCode']==409){
				// $response = true;
				$response['status'] = 0;
				$response['message'] = $cf_response["message"];
				// Log for error
				$error_message = 'Cashfree-'.$this->payment_mode.' Error: Message: '.$cf_response["message"].', User Id-'.$beneficiary_data['user_id'].', Beneficiary ID: '.$beneficiary_data['beneficiary_id'].' , Response: '.$resp;
				save_log($error_message);
			}else{
				$response['status'] = 0;
				$response['message'] = $cf_response["message"];
				// Log for error
				$error_message = 'Cashfree-'.$this->payment_mode.' Error: Message: '.$cf_response["message"].', User Id-'.$beneficiary_data['user_id'].', Beneficiary ID: '.$beneficiary_data['beneficiary_id'].' , Response: '.$resp;
				save_log($error_message);
			}
		}
		return $response;
	}

    function remove_beneficiary($beneficiary_id='')
	{
		$response = false;
		if(!empty($beneficiary_id))
		{
			$this->generate_auth_token();
			$request_data = [
				'beneId'=>$beneficiary_id,
			];
			$url = $this->payout_host . "payout/v1/removeBeneficiary";
			// Get cURL resource
			$curl = curl_init();
			// Set some options - we are passing in a useragent too here
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				'Content-Type:application/json',
				'Authorization: Bearer ' . $this->payout_auth_token,
			));
			curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $url,
				CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => json_encode ($request_data)
			));
			// Send the request & save response to $resp
			$resp = curl_exec($curl);
			// Close request to clear up some resources
			curl_close($curl);
			
			save_log('Cashfree-'.$this->payment_mode.' removeBeneficiary Response ('.$beneficiary_id.'): '.json_encode($resp));

			$cf_response = json_decode($resp, true);
			if($cf_response['status']=='SUCCESS')
			{
				$response = true;
			}
		}
		return $response;
	}

    function request_transfer($transfer_data=[])
	{
		$response = false;
		if(!empty($transfer_data))
		{
			$this->generate_auth_token();
			$transfer_mode = 'banktransfer'; // Allowed values are: banktransfer, upi, paytm, amazonpay, and card
			if($transfer_data['transfer_mode'] == 'upi'){
				$transfer_mode = 'upi';
			}
			$request_data = [
				'beneId'=>$transfer_data['beneficiary_id'],
				'amount'=>$transfer_data['amount'],
				'transferId'=>$transfer_data['transfer_id'],
				'transferMode'=>$transfer_mode,
			];
			$url = $this->payout_host . "payout/v1/requestTransfer";
			// Get cURL resource
			$curl = curl_init();
			// Set some options - we are passing in a useragent too here
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				'Content-Type:application/json',
				'Authorization: Bearer ' . $this->payout_auth_token,
			));
			curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $url,
				CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => json_encode ($request_data)
			));
			// Send the request & save response to $resp
			$resp = curl_exec($curl);
			// Close request to clear up some resources
			curl_close($curl);
            
            save_log('Cashfree-'.$this->payment_mode.' request transfer Response: '.$resp);
			
			$cf_response = json_decode($resp, true);
			$transaction_status = $cf_response['status'];
			if($cf_response['status']=='SUCCESS' || $cf_response['status']=='PENDING')
			{
				$response = true;
				if($cf_response['status']=='SUCCESS' && $cf_response['data']['acknowledged']==0)
				{
					$transaction_status = 'PROCESSING';
				}elseif($cf_response['status']=='SUCCESS' && $cf_response['data']['acknowledged']==0){
                    $transaction_status = 'SUCCESS';
				}elseif($cf_response['status']=='ERROR'){
					$transaction_status = 'FAILED';
				}
			}elseif($cf_response['status']=='ERROR'){
				$transaction_status = 'FAILED';
			}
			$utr = $cf_response['data']['utr'] ?? '';
            
            $update_data = [];
            $update_data['transaction_status'] = $transaction_status;
            $update_data['transaction_message'] = $cf_response['message'];
            $update_data['transaction_reference_id'] = $utr;
            $update_data['cf_response'] = $resp;
            $update_data['updated_at'] = time();
            $update_where = ['transaction_internal_order_id'=>$transfer_data['transfer_id']];            
            $update = DB::table('payment_withdrawal_transaction')->where($update_where)->update($update_data);
			if($cf_response['status']=='SUCCESS' || $cf_response['status']=='PENDING'){
				$response = true; // if you remove this then withdrawal refund is distributing twice
			}
			save_log('Cashfree-'.$this->payment_mode.' Update transaction data where:'.json_encode($update_where).', update_data: '.json_encode($update_data).', update: '.json_encode($update));
		}
		return $response;
	}

    function verify_payout($response_data=[])
    {
        $response = false;
        if(!empty($response_data) && isset($response_data["transferId"]))
		{
			$post_data_json_encoded = isset($response_data['post_data_json_encoded']) ? $response_data['post_data_json_encoded'] : '';

			if($this->verify_signature($response_data,true))
			{
				$withdrawal_transaction = DB::table('payment_withdrawal_transaction')->select('transaction_status')->where('transaction_internal_order_id',$response_data['transferId'])->first();
				if(!empty($withdrawal_transaction))
				{
					$event = $response_data['event'];
					$update = DB::table('payment_withdrawal_transaction')->where(['transaction_internal_order_id'=>$response_data['transferId']])->update(['transaction_webhook_event'=>$event,'cf_response'=>$post_data_json_encoded,'updated_at'=>time()]);

					$transfer_status = $this->get_transfer_status($response_data['transferId']);
					save_log('Cashfree-'.$this->payment_mode.' Payout Verify Log Event: '.$event.', transferId: '.$response_data['transferId']);
				}
			}else{
				$event = $response_data['event'];
				$update = DB::table('payment_withdrawal_transaction')->where(['transaction_internal_order_id'=>$response_data['transferId']])->update(['transaction_webhook_event'=>$event,'cf_response'=>$post_data_json_encoded,'updated_at'=>time()]);
				// Log for error
				$error_message = 'Cashfree-'.$this->payment_mode.' Payout Error: Transfer ID: '.($response_data['transferId']);
				save_log($error_message);
			}
        }else{
            $log_data = 'Cashfree-'.$this->payment_mode.' Payout Webhook Called Without Parameters or Transfer ID! Parameters: '.json_encode($response_data);
            save_log($log_data);
        }
    }

	function get_transfer_status($transferId='')
	{
		$response = false;
		if(!empty($transferId))
		{
			$transaction_data = DB::table('payment_withdrawal_transaction')->select('transaction_status','acknowledged')->where('transaction_internal_order_id',$transferId)->first();
			if(empty($transaction_data)){
				return $response;
			}
			$transaction_status = $transaction_data->transaction_status;
			$acknowledged = $transaction_data->acknowledged;
			$this->generate_auth_token();
			$url = $this->payout_host . "payout/v1/getTransferStatus";
			// Get cURL resource
			$curl = curl_init();
			// Set some options - we are passing in a useragent too here
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				'Content-Type:application/json',
				'Authorization: Bearer ' . $this->payout_auth_token,
			));
			curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $url.'?transferId='.$transferId,
			));
			// Send the request & save response to $resp
			$resp = curl_exec($curl);
			// Close request to clear up some resources
			curl_close($curl);

			save_log('Cashfree-'.$this->payment_mode.' getTransferStatus Response: '.$resp);
			
			$cf_response = json_decode($resp, true);
			if($cf_response['status']=='SUCCESS')
			{
				$update_data = ['updated_at'=>time()];
				$transfer_data = $cf_response['data']['transfer'];
				if(!empty($transfer_data))
				{
					$response = true;
					$update_data['transaction_reference_id'] = $transfer_data['utr'] ?? '';
					$update_data['transaction_time'] = strtotime($transfer_data['processedOn']);
					$update_data['cf_response'] = json_encode($transfer_data);
					if($transfer_data['status']=='SUCCESS' && $transfer_data['acknowledged']==0)
					{
						$update_data['transaction_status'] = 'PROCESSING';
						$update_data['transaction_message'] = 'Transfer is under process';
					}elseif($transfer_data['status']=='SUCCESS' && $transfer_data['acknowledged']==1 && $acknowledged=='0'){
						$update_data['transaction_status'] = 'SUCCESS';
						$update_data['acknowledged'] = 1;
						$update_data['transaction_message'] = 'Transfer completed successfully';
						$update_data['transaction_time'] = time();
						$process = $this->payout_success_process($transferId, $transfer_data);
					}elseif(($transfer_data['status']=='ERROR' || $transfer_data['status']=='FAILED') && $transaction_status!='FAILED'){
						$update_data['transaction_status'] = 'FAILED';
						$update_data['transaction_message'] = 'Amount refunded successfully';
						$process = $this->payout_fail_process($transferId, $transfer_data, true);
					}elseif(($transfer_data['status']=='REVERSED') && $transaction_status!='FAILED'){
						$update_data['transaction_status'] = 'FAILED';
						$update_data['transaction_message'] = 'Amount refunded successfully';
						$process = $this->payout_reversed_process($transferId, $transfer_data, true);
					}
				}
				$update = DB::table('payment_withdrawal_transaction')->where(['transaction_internal_order_id'=>$transferId])->update($update_data);
			}else{
				$update_data = ['updated_at'=>time()];

				if(isset($cf_response['data']['transfer']) && (!empty($transfer_data)))
				{
					$transfer_data = $cf_response['data']['transfer'];
					$update_data['transaction_status'] = $transfer_data['status'];
					$update_data['cf_response'] = json_encode($transfer_data);
				}
				$update = DB::table('payment_withdrawal_transaction')->where(['transaction_internal_order_id'=>$transferId])->update($update_data);
			}
		}
		return $response;
	}

    function payout_success_process($transferId='', $pg_response=[])
	{
		$response = false;
		if(!empty($transferId))
		{
			$withdrawal_transaction = DB::table('payment_withdrawal_transaction')->where('transaction_internal_order_id',$transferId)->first();
			if(!empty($withdrawal_transaction))
			{
                $response = true;
                $transaction_amount = float_amount($withdrawal_transaction->transaction_amount);
				$user_id = $withdrawal_transaction->user_id;

				// Begin - Send notification
				$n_title = 'Amount Withdrawal Successfully';
				$n_message = sprintf('₹%d withdrawal request processed successfully',$transaction_amount);
				$adata = ['amount'=>$transaction_amount];
				send_notification($user_id, 'WALLET', $n_title, $n_message, $adata);
				// End - Send notification
			}
		}
		return $response;
	}

	function payout_fail_process($transferId='', $pg_response=[], $send_email=false)
	{
		$response = false;
		if(!empty($transferId))
		{
			$response = $this->payout_refund_process($transferId, $pg_response, $send_email);
		}
		return $response;
	}

	function payout_reversed_process($transferId='', $pg_response=[], $send_email=false)
	{
		$response = false;
		if(!empty($transferId))
		{
			$response = $this->payout_refund_process($transferId, $pg_response, $send_email);
		}
		return $response;
	}

    function payout_refund_process($transferId='', $response_data=[], $send_email=false)
    {
        $response = false;
        if (!empty($transferId))
        {
            $withdrawal_transaction = DB::table('payment_withdrawal_transaction')->where('transaction_internal_order_id',$transferId)->first();
            if(!empty($withdrawal_transaction)){
                $user_id = $withdrawal_transaction->user_id;
                $transaction_amount = float_amount($withdrawal_transaction->transaction_amount);

                // Refund amount to user winning balance
                $transaction_message = sprintf('Withdrawal Refund',$transaction_amount); // sprintf('₹%d Withdrawal Refund',$amount);
                $extra_data = ['transaction_type'=>'WITHDRAW_REFUND', 'internal_order_id'=>$withdrawal_transaction->transaction_internal_order_id, 'wallet_histroy_message'=>$transaction_message];
                $update = User::add_balance($user_id, $transaction_amount, 'user_winning_amount_balance', $extra_data);
                if($update){
					// Begin - Send notification
					$n_title = 'Transaction has been failed';
					$n_message = sprintf('Your withdrawal request for ₹%d has been failed',$transaction_amount);
					$adata = ['amount'=>$transaction_amount];
					send_notification($user_id, 'WALLET', $n_title, $n_message, $adata);
					// End - Send notification

                    // Begin - Send notification
                    $n_title = 'Withdrawal Refund';
                    $n_message = sprintf('₹%d withdrawal refund added in your wallet',$transaction_amount);
                    $adata = ['amount'=>$transaction_amount];
                    send_notification($user_id, 'WALLET', $n_title, $n_message, $adata);
                    // End - Send notification
                }
                $response = true;
            }
        }
        return $response;
    }

}
