<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use yii\wxBizMsgCrypt;
use app\common\Wx_common;


class AuthController extends Controller
{
public $enableCsrfValidation = false;    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * 
     *获取component_verify_ticke，微信服务器每隔10分钟下发一次，需要解密获取。
     * @return string
     */
    public function actionMsg_receive()
    {
        echo 'success';
        $timeStamp    =  isset($_GET['timestamp']);
        $nonce        =  isset($_GET['nonce']);
        $encrypt_type =  isset($_GET['encrypt_type']);
        $msg_sign     =  isset($_GET['msg_signature']);
        $raw_msg =  file_get_contents('php://input'); 
        $cache = Yii::$app->cache;                
        $encodingAesKey = Yii::$app->params['encodingAesKey'];
        $token = Yii::$app->params['token'];
        $appId = Yii::$app->params['appId'];
        
        $wechat = new \yii\wxBizMsgCrypt\WXBizMsgCrypt($token, $encodingAesKey, $appId);

        if($raw_msg!=null){
                                            $encryptMsg=$raw_msg;     
                                            $xml_tree = new \DOMDocument();
                                            $xml_tree->loadXML($encryptMsg);
                                            $array_e = $xml_tree->getElementsByTagName('Encrypt');
                                            $encrypt = $array_e->item(0)->nodeValue;
                                            $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
                                            $from_xml = sprintf($format, $encrypt);
                                            $msg = '';
                                            $errCode = $wechat->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
        
                                            if ($errCode == 0) {

                                                                                $xml = new \DOMDocument();
                                                                                $xml->loadXML($msg);
                                                                                
                                                                                $array_infotype = $xml->getElementsByTagName('InfoType');
                                                                                $info_type = $array_infotype->item(0)->nodeValue;      
                                                                                switch ($info_type)
                                                                                        {
                                                                                        case "component_verify_ticket":                     //获取component_verify_ticket
                                                                                                    $array_ComponentVerifyTicket = $xml->getElementsByTagName('ComponentVerifyTicket');
                                                                                                   $component_verify_ticket = $array_ComponentVerifyTicket->item(0)->nodeValue;                                                                
                                                                                                    Yii::$app->db->createCommand()->update('platfrom_auth_info', [ 'component_verify_ticket' => $component_verify_ticket ],'id=1')->execute(); 
                                                                                                    $cache = Yii::$app->cache;
                                                                                                    $cache->set("component_verify_ticket", $component_verify_ticket);
                                                                                                     break;  
                                                                                        case "authorized":                      //公众号初次授权
                                                                                                    $array_AuthorizationCode= $xml->getElementsByTagName('AuthorizationCode');
                                                                                                    $AuthorizationCode = $array_AuthorizationCode->item(0)->nodeValue;                                                                
                                                                                                    $array_AuthorizerAppid = $xml->getElementsByTagName('AuthorizerAppid');
                                                                                                    $AuthorizerAppid = $array_AuthorizerAppid->item(0)->nodeValue;            
                                                                                                    $app_result = Yii::$app->db->createCommand("SELECT authorizer_appid  FROM wechat_app_info WHERE authorizer_appid='".$AuthorizerAppid."'")->queryOne();  
                                                                                                    $authorizer_appid = $app_result['authorizer_appid'];
                                                                                                    if(empty($authorizer_appid)){
                                                                                                            Yii::$app->db->createCommand()->insert('wechat_app_info', [
                                                                                                                    'authorizer_appid' => $AuthorizerAppid ,
                                                                                                                    'authorization_code' => $AuthorizationCode,
                                                                                                                    'create_time' => time() ,
                                                                                                                    'status' => 1 ,
                                                                                                                    'func_info' => 1 ,                                                                                                                
                                                                                                                        ])->execute();       
                                                                                                    }else{
                                                                                                            Yii::$app->db->createCommand()->update('wechat_app_info', [
                                                                                                                                   'create_time' => time() ,
                                                                                                                                   'status' => 1 ,
                                                                                                                                   'func_info' => 1 ,
                                                                                                                                       ], "authorizer_appid ='".$AuthorizerAppid."'" )->execute();                                                                                                          
                                                                                                    }

                                                                                                    /**
                                                                                                     * 初次授权获取$authorizer_access_token，$authorizer_refresh_token
                                                                                                     */                                                                                                             
                                                                                                    
                                                                                                    $component_verify_ticket = $cache->get("component_verify_ticket");
                                                                                                    $component_access_token = Wx_common::getComponent_access_token($component_verify_ticket);
                                                                                                    $url = "https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=".$component_access_token;
                                                                                                    $postData = [
                                                                                                                "component_appid"=>Yii::$app->params['appId'] ,
                                                                                                                "authorization_code" => $AuthorizationCode,

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
                                                                                                            $authorizer_appid = $result_arr['authorization_info']['authorizer_appid'];
                                                                                                            $authorizer_access_token = $result_arr['authorization_info']['authorizer_access_token'];
                                                                                                            $authorizer_refresh_token = $result_arr['authorization_info']['authorizer_refresh_token'];
                                                                                                            $authorizer_access_token_expire = $result_arr['authorization_info']['expires_in'];
                                                                                                            $func_info = (string)$result_arr['authorization_info']['func_info'];
           
                                                                                                             Yii::$app->db->createCommand()->update('wechat_app_info', [
                                                                                                                                    'authorizer_access_token' => $authorizer_access_token ,
                                                                                                                                    'authorizer_refresh_token' => $authorizer_refresh_token ,
                                                                                                                                    'authorizer_access_token_expire' => $authorizer_access_token_expire+time() ,
                                                                                                                                    'create_time' => time() ,
                                                                                                                                    'status' => 1 ,
                                                                                                                                    'func_info' => 1 ,
                                                                                                                                        ], "authorizer_appid ='".$AuthorizerAppid."'" )->execute();  
                                                                                                    }else{                                                                                                                                                              //获取失败记录返回的错误信息
                                                                                                             Yii::$app->db->createCommand()->update('wechat_app_info', [
                                                                                                                                    'authorizer_access_token' => "获取授权失败".$result_arr['errcode'].','.$result_arr['errmsg'] ,
                                                                                                                                    'authorizer_refresh_token' => $result_arr['errcode'].','.$result_arr['errmsg'],
                                                                                                                                    'authorizer_access_token_expire' => time() ,
                                                                                                                                    'create_time' => time() ,
                                                                                                                                    'status' => 9 ,//授权成功，但获取$authorizer_access_token失败
                                                                                                                                    'func_info' => 0 ,  //待完善
                                                                                                                                        ], "authorizer_appid ='".$AuthorizerAppid."'" )->execute();                                                                                                         
                                                                                                    }                                                                                                    
                                                                                                      break;
                                                                                        case "updateauthorized":            //公众号更新授权
                                                                                                    $array_AuthorizationCode= $xml->getElementsByTagName('AuthorizationCode');
                                                                                                    $AuthorizationCode = $array_AuthorizationCode->item(0)->nodeValue;                                                                
                                                                                                    $array_AuthorizerAppid = $xml->getElementsByTagName('AuthorizerAppid');
                                                                                                    $AuthorizerAppid = $array_AuthorizerAppid->item(0)->nodeValue;                                                                                               
                                                                                                    Yii::$app->db->createCommand()->update('wechat_app_info', [
                                                                                                            'authorization_code' => $AuthorizationCode,
                                                                                                         ], "authorizer_appid ='".$AuthorizerAppid."'" )->execute();  

                                                                                                    /**
                                                                                                     * 更新授权获取$authorizer_access_token，$authorizer_refresh_token
                                                                                                     */      

                                                                                                    $component_verify_ticket = $cache->get("component_verify_ticket");
                                                                                                    $component_access_token = Wx_common::getComponent_access_token($component_verify_ticket);
                                                                                                    $url = "https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=".$component_access_token;
                                                                                                    $postData = [
                                                                                                                "component_appid"=>Yii::$app->params['appId'] ,
                                                                                                                "authorization_code" => $AuthorizationCode,

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
                                                                                                            $authorizer_appid = $result_arr['authorization_info']['authorizer_appid'];
                                                                                                            $authorizer_access_token = $result_arr['authorization_info']['authorizer_access_token'];
                                                                                                            $authorizer_refresh_token = $result_arr['authorization_info']['authorizer_refresh_token'];
                                                                                                            $authorizer_access_token_expire = $result_arr['authorization_info']['expires_in'];
                                                                                                            $func_info = (string)$result_arr['authorization_info']['func_info'];
           
                                                                                                             Yii::$app->db->createCommand()->update('wechat_app_info', [
                                                                                                                                    'authorizer_access_token' => $authorizer_access_token ,
                                                                                                                                    'authorizer_refresh_token' => $authorizer_refresh_token ,
                                                                                                                                    'authorizer_access_token_expire' => $authorizer_access_token_expire+time() ,
                                                                                                                                    'create_time' => time() ,
                                                                                                                                    'status' => 1 ,
                                                                                                                                    'func_info' => 1 , //待完善
                                                                                                                                        ], "authorizer_appid ='".$AuthorizerAppid."'" )->execute();  
                                                                                                    }else{                                                                                                                                                              //获取失败记录返回的错误信息
                                                                                                             Yii::$app->db->createCommand()->update('wechat_app_info', [
                                                                                                                                    'authorizer_access_token' => '更新授权失败'.$result_arr['errcode'].','.$result_arr['errmsg'] ,
                                                                                                                                    'authorizer_refresh_token' => $result_arr['errcode'].','.$result_arr['errmsg'],
                                                                                                                                    'authorizer_access_token_expire' => time() ,
                                                                                                                                    'create_time' => time() ,
                                                                                                                                    'status' => 8 ,  //更新授权成功，但获取$authorizer_access_token失败
                                                                                                                                    'func_info' => 0 ,
                                                                                                                                        ], "authorizer_appid ='".$AuthorizerAppid."'" )->execute();                                                                                                         
                                                                                                    }                                                                                                     
                                                                                                 break;
                                                                                        case "unauthorized":            //公众号取消授权
                                                                                                    $array_AuthorizationCode= $xml->getElementsByTagName('AuthorizationCode');
                                                                                                    $AuthorizationCode = $array_AuthorizationCode->item(0)->nodeValue;                                                                
                                                                                                    $array_AuthorizerAppid = $xml->getElementsByTagName('AuthorizerAppid');
                                                                                                    $AuthorizerAppid = $array_AuthorizerAppid->item(0)->nodeValue;                                                                                               
                                                                                                    Yii::$app->db->createCommand()->update('wechat_app_info', [
                                                                                                            'status' => 0,
                                                                                                         ], "authorizer_appid ='".$AuthorizerAppid."'" )->execute();  
                                                                                                 break;
                                                                                        }

                                            } else {
                                                            Yii::$app->db->createCommand()->update('platfrom_auth_info', [ 'component_verify_ticket' => $errCode ],'id=1')->execute();                                    
                                            }
        }

     }
//授权后回调URI
     public function actionAuth_callback(){
                return $this->render('index');
     }
}
