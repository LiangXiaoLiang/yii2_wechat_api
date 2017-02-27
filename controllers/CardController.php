<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use yii\curl;
use app\common\Wx_common;



class CardController extends Controller
{
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
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $authorizer_appid = 'xxx'; //第三方平台的id
        $cache = Yii::$app->cache;
        $component_verify_ticket = $cache->get("component_verify_ticket");
        $authorizer_access_token = Wx_common::getAuthorizer_access_token($component_verify_ticket,$authorizer_appid);  //获取公众号的授权token
        $jsapi_ticket = Wx_common::getJsapi_ticket($authorizer_access_token,$authorizer_appid); 
        $card_api_ticket = Wx_common::getCardApiTicket($authorizer_access_token,$authorizer_appid);  //卡券的api_ticket
        $nonceStr = Wx_common::getNonce_Str(16);        
        $timestamp = time();
        $url = "http://wx.cloudhd.cn/card";
        $string = "jsapi_ticket=$jsapi_ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";  
        $signature = sha1($string);         //jssdk的signature
        
        $card_id = "pnan0vsHmSFqCTpyzk8NzS4PQqWY";
        $card_code ='';
        $openid ='';
        $cardTmpArr= array($openid,$card_code,$card_api_ticket,$timestamp,$nonceStr,$card_id);         
        sort($cardTmpArr,SORT_STRING);
        $cardTmpStr = implode($cardTmpArr);
        $cardSignature = sha1($cardTmpStr);  //卡券的signature
        $cardExtArr = [
                'code' => $card_code,
                'openid' => $openid,
                'timestamp' => $timestamp,
                'nonce_str' => $nonceStr,
                'signature' => $cardSignature,
        ];
        $cardExt = json_encode($cardExtArr);
        
        return $this->render('index',[
            'appId' => $authorizer_appid,
            'timestamp' => $timestamp,
            'nonceStr'=>$nonceStr,
            'signature'=>$signature,
            'cardExt'=>$cardExt,
            'card_id'=>$card_id,
        ]);
    }
}
