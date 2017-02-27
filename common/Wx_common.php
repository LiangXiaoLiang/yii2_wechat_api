<?php
/**
 * @author xiaoliang <xiaoliang9981@163.com>
 * @since  2017
 */
namespace app\common;

use Yii;

/**
 *  该类主要用于刷新已经授权给第三方平台的公众号的$authorizer_access_token、$jsapi_ticket、卡券 $api_ticket
 *  */
class Wx_common
{
    //获取component_access_token
    static public function getComponent_access_token($component_verify_ticket){
        $cache = Yii::$app->cache;
        $component_access_token = $cache->get("component_access_token");
        $component_access_token_expire = $cache->get("component_access_token_expire");
        if($component_access_token_expire-time()<600){
                $component_access_token = self::refreshComponent_access_token($component_verify_ticket);
        }
        return $component_access_token;
    }
    //获取$authorizer_access_token
    static public function getAuthorizer_access_token($component_verify_ticket,$authorizer_appid){
        $cache = Yii::$app->cache;
        $authorizer_access_token = $cache->get($authorizer_appid."_authorizer_access_token");
        $authorizer_access_token_expire = $cache->get($authorizer_appid."_authorizer_access_token_expire");
        if($authorizer_access_token_expire-time()<600){
                $authorizer_access_token = self::authorizer_refresh_token($component_verify_ticket,$authorizer_appid);
        }
        return $authorizer_access_token;
    }
    
    //获得jsapi_ticket
    static public function getJsapi_ticket($authorizer_access_token,$authorizer_appid){
        $cache = Yii::$app->cache;
        $jsapi_ticket = $cache->get($authorizer_appid."_jsapi_ticket");
        $jsapi_ticket_expire = $cache->get($authorizer_appid."_jsapi_ticket_expire");
        if($jsapi_ticket_expire-time()<600){
                $jsapi_ticket = self::refreshJsapi_ticket($authorizer_access_token,$authorizer_appid);
        }
        return $jsapi_ticket;
    }
    
    //获得卡券api_ticket
    static public function getCardApiTicket($authorizer_access_token,$authorizer_appid){
        $cache = Yii::$app->cache;
        $card_api_ticket = $cache->get($authorizer_appid."_card_api_ticket");
        $card_api_ticket_expire = $cache->get($authorizer_appid."_card_api_ticket_expire");
        if($card_api_ticket_expire-time()<600){
                $card_api_ticket = self::refreshCardApiTicket($authorizer_access_token,$authorizer_appid);
        }
        return $card_api_ticket;
    }

//刷新component_access_token
    static public function refreshComponent_access_token($component_verify_ticket){
        
        $url = "https://api.weixin.qq.com/cgi-bin/component/api_component_token";

        $postData = [
                    "component_appid"=>Yii::$app->params['appId'] ,
                    "component_appsecret"=> Yii::$app->params['component_appsecret'] ,
                    "component_verify_ticket"=> $component_verify_ticket , 
        ];
        
        $data_string = json_encode($postData);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_URL,$url); 
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);      
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        
        $result = curl_exec($ch);        
        $result_arr = json_decode($result,true);       
        if(empty($result_arr['errcode'])){
                $component_access_token = $result_arr['component_access_token'];
                $component_access_token_expire = $result_arr['expires_in']+time();

                Yii::$app->db->createCommand()->update('platfrom_auth_info', [ 
                    'component_access_token' => $component_access_token ,
                    'component_access_token_expire' => $component_access_token_expire,
                        ],'id=1')->execute();  
                //将$component_access_token，$component_access_token_expire写入缓存方便调用
                
                $cache = Yii::$app->cache;
                $cache->set("component_access_token", $component_access_token);
                $cache->set("component_access_token_expire", $component_access_token_expire);
                return $component_access_token;
        }else{
                 Yii::$app->db->createCommand()->update('platfrom_auth_info', [
                    'component_access_token' => $result_arr['errcode'].','.$result_arr['errmsg'],
                        ],'id=1')->execute();  
        }
    }
    
//获取预授权码pre_auth_code
    
    static public function askPre_auth_code($component_access_token){
        
        $url = "https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=".$component_access_token;

        $postData = [
                    "component_appid"=>Yii::$app->params['appId'] ,
        ];
        
        $data_string = json_encode($postData);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_URL,$url); 
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);      
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);

        $result = curl_exec($ch);       
        $result_arr = json_decode($result,true);  
        if(empty($result_arr['errcode'])){

                $pre_auth_code= $result_arr['pre_auth_code'];

                return $pre_auth_code;
        }else{
                Yii::$app->db->createCommand()->update('platfrom_auth_info', [
                    'pre_auth_code' => $result_arr['errcode'].','.$result_arr['errmsg'],
                    'pre_auth_code_error_time' => time(), 
                        ],'id=1')->execute();  
        }
    }    
    /**
     * 刷新公众号的authorizer_access_token
     * @property string $authorizer_appid
     * 
     */                                                                                                     
    static public function authorizer_refresh_token($component_verify_ticket,$authorizer_appid){
        
                if(!empty($authorizer_appid)){

                        $component_access_token = self::getComponent_access_token($component_verify_ticket);
                        $app_result = Yii::$app->db->createCommand("SELECT authorizer_refresh_token  FROM wechat_app_info WHERE authorizer_appid='".$authorizer_appid."'")->queryOne();  
                        $authorizer_refresh_token = $app_result['authorizer_refresh_token'];

                        $url = "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=".$component_access_token;

                        $postData = [
                                    "component_appid"=>Yii::$app->params['appId'] ,
                                    "authorizer_appid"=>$authorizer_appid,
                                    "authorizer_refresh_token"=>$authorizer_refresh_token,
                        ];

                        $data_string = json_encode($postData);

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
                        curl_setopt($ch, CURLOPT_URL,$url); 
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        curl_setopt($ch, CURLOPT_POST, 1);      
                        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);

                        $result = curl_exec($ch);        
                          curl_close($ch);

                        $result_arr = json_decode($result,true);       

                        if(empty($result_arr['errcode'])){
                                $authorizer_access_token = $result_arr['authorizer_access_token'];
                                $authorizer_access_token_expire = $result_arr['expires_in']+time() ;

                                 Yii::$app->db->createCommand()->update('wechat_app_info', [
                                                        'authorizer_access_token' => $authorizer_access_token ,
                                                        'authorizer_access_token_expire' => $authorizer_access_token_expire,
                                                            ], "authorizer_appid ='".$authorizer_appid."'" )->execute();  
                                 //写入缓存
                                 $cache = Yii::$app->cache;
                                 $cache->set($authorizer_appid."_authorizer_access_token", $authorizer_access_token);
                                 $cache->set($authorizer_appid."_authorizer_access_token_expire", $authorizer_access_token_expire);
                                 return $authorizer_access_token;
                        }else{                                                                                                                                                              //获取失败记录返回的错误信息
                                 Yii::$app->db->createCommand()->update('wechat_app_info', [
                                                        'authorizer_access_token' => $result_arr['errcode'].','.$result_arr['errmsg'] ,
                                                        'authorizer_access_token_expire' => time() ,
                                                            ], "authorizer_appid ='".$authorizer_appid."'" )->execute();                                                                                                         
                        }     
    }else{
        echo 'error:authorizer_appid=null';
    }       
    }
    
    
    
        //采用http GET方式请求获得卡券的api_ticket
    
        static public  function refreshCardApiTicket($authorizer_access_token,$authorizer_appid){
        
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$authorizer_access_token&type=wx_card";


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_URL,$url); 
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);

        $result = curl_exec($ch);       
        $result_arr = json_decode($result,true);  
        if(empty($result_arr['errcode'])){

                $card_api_ticket = $result_arr['ticket'];
                $card_api_ticket_expire = $result_arr['expires_in']+time();

                Yii::$app->db->createCommand()->update('wechat_app_info', [
                                       'card_api_ticket' => $card_api_ticket ,
                                       'card_api_ticket_expire' => $card_api_ticket_expire,
                                           ], "authorizer_appid ='".$authorizer_appid."'" )->execute();  
                //写入缓存
                $cache = Yii::$app->cache;
                $cache->set($authorizer_appid."_card_api_ticket", $card_api_ticket);
                $cache->set($authorizer_appid."_card_api_ticket_expire", $card_api_ticket_expire);                
                return $card_api_ticket;
        }else{
                Yii::$app->db->createCommand()->update('wechat_app_info', [
                                       'card_api_ticket' => $result_arr['errcode'].":".$result_arr['errmsg'] ,
                                       'card_api_ticket_expire' => time() ,
                                           ], "authorizer_appid ='".$authorizer_appid."'" )->execute();  
        }
    }    
    
    
        //采用http GET方式请求获得jsapi_ticket
    
        static public  function refreshJsapi_ticket($authorizer_access_token,$authorizer_appid){
        
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$authorizer_access_token."&type=jsapi";


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_URL,$url); 
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);

        $result = curl_exec($ch);       
        $result_arr = json_decode($result,true);  
        if(empty($result_arr['errcode'])){

                $jsapi_ticket = $result_arr['ticket'];
                $jsapi_ticket_expire = $result_arr['expires_in']+time();

                Yii::$app->db->createCommand()->update('wechat_app_info', [
                                       'jsapi_ticket' => $jsapi_ticket ,
                                       'jsapi_ticket_expire' => $jsapi_ticket_expire,
                                           ], "authorizer_appid ='".$authorizer_appid."'" )->execute();  
                //写入缓存
                $cache = Yii::$app->cache;
                $cache->set($authorizer_appid."_jsapi_ticket", $jsapi_ticket);
                $cache->set($authorizer_appid."_jsapi_ticket_expire", $jsapi_ticket_expire);                
                return $jsapi_ticket;
        }else{
                Yii::$app->db->createCommand()->update('wechat_app_info', [
                                       'jsapi_ticket' => $result_arr['errcode'].":".$result_arr['errmsg'] ,
                                       'jsapi_ticket_expire' => time() ,
                                           ], "authorizer_appid ='".$authorizer_appid."'" )->execute();  
        }
    } 
    
    
    static public function getNonce_Str($length)
{
    $str = null;
    $pattern='1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
    for($i=0;$i<$length;$i++)
    {
      $str .= $pattern{mt_rand(0,35)};    //生成php随机数
    }
    return $str;
   }
}
