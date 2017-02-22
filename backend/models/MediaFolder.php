<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "media_folder".
 *
 * @property integer $id
 * @property string $name
 * @property string $directory
 * @property integer $row_status
 * @property integer $created_at
 * @property integer $created_by
 * @property integer $updated_at
 * @property integer $updated_by
 */
class MediaFolder extends \common\models\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'media_folder';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'directory'], 'required'],
            [ 'name', 'uniquenessValidation'],
            [['directory'], 'folderValidation'],
            [['row_status', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['name', 'directory'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'directory' => 'Directory',
            'row_status' => 'Row Status',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            
            $post = Yii::$app->request->post();
            if ( !empty( $post ) )
            {
                $this->directory = 'media/uploader/' . strtolower($post['MediaFolder']['directory']) . '/';
            }
            
            return true;
        } else {
            return false;
        }
    }

    public function afterSave($insert, $changedAttributes)
    {
        $post = Yii::$app->request->post();
        if ( isset($post['MediaFolder']) )
        {
            mkdir( ASSETS_PATH . 'uploader/' . strtolower($post['MediaFolder']['directory']) . '/' );
        }
        return parent::afterSave($insert, $changedAttributes);
    }

    public function folderValidation($attribute)
    {
        if ( !preg_match( '/^[a-zA-Z_0-9\/]*$/', $this->$attribute ) )
        {
            $this->addError( $attribute, $this->getAttributeLabel($attribute) . ' must be character, number or underscore' );
            return false;
        }
        return true;
    }

    public static function getDirectory( $id )
    {
        $query = static::findOne($id);
        return $query->directory;
    }
}