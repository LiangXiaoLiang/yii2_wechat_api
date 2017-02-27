<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'language' =>  'zh-CN',   //支持中文
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'modules' => [ 
        'redactor' => [    //redaction 文本编辑器
        'class' => 'yii\redactor\RedactorModule', 
        'imageAllowExtensions'=>['jpg','png','gif'] 
        ], 
    ],    
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'humanluo7pJ-_scEYGQ0L2YlTT0X2Exp',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'curl' => [
            'class' => 'yii\curl\Curl',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
        
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                          "<controller:\w+>/<action:\w+>"=>"<controller>/<action>",
                          "<controller:\w+>/<action:\w+>/<authorizer_appid:\w+>"=>"<controller>/<action>",
                          "<controller:\w+>/<action:\w+>/<auth_code:\w+>/<expires_in:\w+>"=>"<controller>/<action>",
                          "<controller:\w+>/<action:\w+>/<authorizer_appid:\w+>/<signature:\w+>/<timestamp:\w+>/<nonce:\w+>/<encrypt_type:\w+>/<msg_signature:\w+>"=>"<controller>/<action>",     //注意这里配置，是获取微信转发公众号的粉丝消息、事件随get过来的解密参数的关键
                          "<controller:\w+>/<action:\w+>/<signature:\w+>/<timestamp:\w+>/<nonce:\w+>/<encrypt_type:\w+>/<msg_signature:\w+>"=>"<controller>/<action>",     //注意这里配置，是获取微信授权事件get过来的解密参数的关键
            ],
        ],
        'assetManager' => [
            'bundles' => [
                'yii\web\JqueryAsset' => [
                    'jsOptions' => [
                        'position' => \yii\web\View::POS_HEAD,
                    ]
                ],
            ],
        ],
        
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
