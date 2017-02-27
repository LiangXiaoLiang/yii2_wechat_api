<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\common\Wx_common;




class SiteController extends Controller
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
        $cache = Yii::$app->cache;
        $component_verify_ticket = $cache->get("component_verify_ticket");
        $component_access_token = Wx_common::getComponent_access_token($component_verify_ticket);
         $pre_auth_code = Wx_common::askPre_auth_code($component_access_token);

        return $this->render('index', [
            'pre_auth_code' => $pre_auth_code,
            'component_appid'=>Yii::$app->params['appId'] ,
        ]);
    }
    
    //第三平台调用接口调用次数清零
//    public function actionClear_quota()
//    {
//        $cache = Yii::$app->cache;
//        $component_verify_ticket = $cache->get("component_verify_ticket");
//        $component_access_token = Wx_common::getComponent_access_token($component_verify_ticket);
//        $url = "https://api.weixin.qq.com/cgi-bin/component/clear_quota?component_access_token=".$component_access_token;
//        $postData = [
//                    "component_appid"=>Yii::$app->params['appId'] ,
//
//        ];
//
//
//        $data_string = json_encode($postData);
//
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
//        curl_setopt($ch, CURLOPT_URL,$url); 
//        curl_setopt($ch, CURLOPT_HEADER, 0);
//        curl_setopt($ch, CURLOPT_POST, 1);      
//        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
//
//        $result = curl_exec($ch);        
//        $result_arr = json_decode($result,true);        
//        if(empty($result_arr['errcode'])){
//            echo "clear quota success!";
//        }else{
//            echo $result_arr['errcode'].":".$result_arr['errmsg'];
//        }
        
//    }

}
