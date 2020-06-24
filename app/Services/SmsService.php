<?php
/**
 * Created by PhpStorm.
 * User: sdf_sky
 * Date: 2017/1/4
 * Time: 下午12:03
 */

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmsService
{
   public static function sendSms($mobile,$smsTemplateId,$params){
       if(!config('services.sms_open')){
           return false;
       }
       if(!is_mobile($mobile) || !$smsTemplateId){
           return false;
       }
       return self::submailSmsSend($mobile,$smsTemplateId,$params);
   }

   public static function sendSmsCode($mobile){
       $code = random_number(6);
       Cache::put("sms_code_$mobile",$code,600);
       return self::sendSms($mobile,Setting()->get('sms_code_template'),['code'=>$code]);
   }


   public static function verifySmsCode($mobile,$code){
        $storeCode = Cache::get("sms_code_$mobile","");
        if($storeCode != $code){
            return false;
        }

       return true;
   }

   private static function submailPost($smsapi,$data)
   {
       $query = http_build_query($data);
       $options['http'] = array(
           'timeout' => 60,
           'method' => 'POST',
           'header' => 'Content-type:application/x-www-form-urlencoded',
           'content' => $query
       );
       $context = stream_context_create($options);
       $result = file_get_contents($smsapi, false, $context);
       $output = trim($result, "\xEF\xBB\xBF");
       return json_decode($output, true);
   }

   protected static function submailSmsSend($mobile,$smsTemplateId,$params)
   {
        $data['to']  =    trim($mobile);
        $data['project']   =   trim($smsTemplateId);
        $data['appid'] =   config('submailsms.access_key');
        $data['signature'] =   config('submailsms.access_secret');
        $data['vars']   =  json_encode($params);
        $ress = self::submailPost('https://api.mysubmail.com/message/xsend',$data);
        if($ress['status'] != 'success'){
            Log::error("sms_send_error".json_encode($ress));
            return false;
        }
        return true;
   }

}
