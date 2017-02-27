<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "platfrom_auth_info".
 *
 * @property string $component_verify_ticket
 * @property string $component_access_token
 * @property string $component_access_token_expire
 * @property string $pre_auth_code
 * @property string $pre_auth_code_expire
 */
class PlatfromAuthInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'platfrom_auth_info';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['component_verify_ticket', 'component_access_token', 'component_access_token_expire', 'pre_auth_code', 'pre_auth_code_expire'], 'required'],
            [['component_access_token_expire', 'pre_auth_code_expire'], 'integer'],
            [['component_verify_ticket', 'component_access_token', 'pre_auth_code'], 'string', 'max' => 120],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'component_verify_ticket' => 'Component Verify Ticket',
            'component_access_token' => 'Component Access Token',
            'component_access_token_expire' => 'Component Access Token Expire',
            'pre_auth_code' => 'Pre Auth Code',
            'pre_auth_code_expire' => 'Pre Auth Code Expire',
        ];
    }
}
