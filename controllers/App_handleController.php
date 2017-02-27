<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\common\Wx_common;

class App_handleController extends Controller
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
//    public function init()  
//    {  
//        $this->layout='main';  
//    } 
    
    /**
    * 
    * 公众号的事件处理.
    * @property string $authorizer_appid
     * @return string
     */
    public function actionMsg_receive()
    {
//      $this->putLog("记录开始：");
        $authorizer_appid    = isset($_GET['authorizer_appid']);
        $timeStamp    =  isset($_GET['timestamp']);
        $nonce        =  isset($_GET['nonce']);
        $encrypt_type =  isset($_GET['encrypt_type']);
        $msg_sign     =  isset($_GET['msg_signature']);
        $raw_msg =  file_get_contents('php://input'); 

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

                                                    $array_ToUserName = $xml->getElementsByTagName('ToUserName'); //获取消息ToUserName
                                                    $ToUserName = $array_ToUserName->item(0)->nodeValue;      
                                                    $array_FromUserName = $xml->getElementsByTagName('FromUserName'); //获取消息FromUserName
                                                    $FromUserName = $array_FromUserName->item(0)->nodeValue;      
                                                    $array_MsgType= $xml->getElementsByTagName('MsgType'); //获取消息类型
                                                    $MsgType = $array_MsgType->item(0)->nodeValue;      
                                                    switch ($MsgType)
                                                            {
                                                            case "event":                     //消息类型是事件
                                                                        $array_Event= $xml->getElementsByTagName('Event'); //获取具体事件内容
                                                                        $Event = $array_Event->item(0)->nodeValue;                       
                                                                        $Content = $Event."from_callback";                  //测试回复的内容
                                                                        $text = "<xml><ToUserName><![CDATA[".$FromUserName."]]></ToUserName><FromUserName><![CDATA[".$ToUserName."]]></FromUserName><CreateTime>".$timeStamp."</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[".$Content."]]></Content></xml>";

                                                                        $encryptMsg = '';
                                                                        $errCode = $wechat->encryptMsg($text, $timeStamp, $nonce, $encryptMsg);
                                                                        echo $encryptMsg;
//                                                                        $this->putLog("返回事件测试成功");
                                                                        break;        
                                                            case "text":                     //消息类型是事件
                                                                        $array_Content= $xml->getElementsByTagName('Content'); //获取具体事件内容
                                                                        $recerve_Content = $array_Content->item(0)->nodeValue;            
                                                                        $authorizer_appid = "wx570bc396a51b8ff8";      
//                                                                        $this->putLog($recerve_Content);
                                                                        if($recerve_Content==="TESTCOMPONENT_MSG_TYPE_TEXT"){
                                                                                $Content = "TESTCOMPONENT_MSG_TYPE_TEXT_callback";                  //测试回复的内容
                                                                                $text = "<xml><ToUserName><![CDATA[".$FromUserName."]]></ToUserName><FromUserName><![CDATA[".$ToUserName."]]></FromUserName><CreateTime>".$timeStamp."</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[".$Content."]]></Content></xml>";

                                                                                $encryptMsg = '';
                                                                                $errCode = $wechat->encryptMsg($text, $timeStamp, $nonce, $encryptMsg);
                                                                                echo $encryptMsg;
//                                                                                $this->putLog("返回消息TESTCOMPONENT_MSG_TYPE_TEXT_callback测试成功");
                                                                        }else{
                                                                                $AuthorizationCode = trim(str_replace("QUERY_AUTH_CODE:","",$recerve_Content));
//                                                                                $this->putLog("获得query_auth_code:".$AuthorizationCode);
                                                                                /**
                                                                                 * 获取$authorizer_access_token
                                                                                 */      
                                                                                $cache = Yii::$app->cache;     
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
                                                                                                                    ], "authorizer_appid ='".$authorizer_appid."'" )->execute();  
                                                                                }else{                                                                                                                                                              //获取失败记录返回的错误信息
                                                                                         Yii::$app->db->createCommand()->update('wechat_app_info', [
                                                                                                                'authorizer_access_token' => '更新授权失败'.$result_arr['errcode'].','.$result_arr['errmsg'] ,
                                                                                                                'authorizer_refresh_token' => $result_arr['errcode'].','.$result_arr['errmsg'],
                                                                                                                'authorizer_access_token_expire' => time() ,
                                                                                                                'create_time' => time() ,
                                                                                                                'status' => 8 ,  //更新授权成功，但获取$authorizer_access_token失败
                                                                                                                'func_info' => 0 ,
                                                                                                                    ], "authorizer_appid ='".$authorizer_appid."'" )->execute();                                                                                                         
                                                                                }                                                                         
                                                                                
                                                                                
                                                                                
                                                                                $Content = $AuthorizationCode."_from_api";                  //测试回复的内容
                                                                                $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$authorizer_access_token;
                                                                                $postData = [
                                                                                                        "touser"=>$FromUserName,
                                                                                                        "msgtype"=>"text",
                                                                                                        "text"=>
                                                                                                        [
                                                                                                             "content"=>$Content,
                                                                                                        ]
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
                                                                                        $this->putLog("返回api消息测试成功");
                                                                                }else{
                                                                                        $log=$result_arr['errcode'].$result_arr['errmsg'];
                                                                                        $this->putLog("返回api消息测试失败".$log);
                                                                                }

                                                                        break;        
                                                                        }
                                                            }
                                            }
        }
    }
    
//    public function actionRefresh_token()
//    {
//        $this->authorizer_refresh_token($authorizer_appid);
//    }
        private function putLog($log)
    {
     $log .= "\n";
    // $logDir = dirname( __FILE__ );
    // $logPath = $logDir . "/curl_log.txt";
     $logPath = "/var/www/html/wx_log.txt";
     if ( !file_exists( $logPath ) )
     {
      $handle = fopen( $logPath, 'w' );
      fclose ( $handle );
     }
     file_put_contents( $logPath, $log, FILE_APPEND );
    }
}
