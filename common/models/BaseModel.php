<?php
namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\helpers\StringHelper;

use common\components\Upload;

class BaseModel extends ActiveRecord
{

    const STATUS_DELETED = -1;
    const STATUS_DISACTIVE = 0;
    const STATUS_ACTIVE  = 1;

    public $getStatus = [ -1 => 'Deleted', 0 => 'Disactive', 1 => 'Active' ]; 

	public function init()
	{

	}

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * Alphabets Validation
     * Function ini untuk memvalidasikan sebuah teks menjadi alphabet
     * @category a-z, A-Z, (spasi)
     * 
     * @param      <type>   $attribute  The attribute
     *
     * @return     boolean  ( description_of_the_return_value )
     */
    public function alphabetsValidation($attribute)
    {
        if ( !preg_match( '/^[a-zA-Z ]+$/', $this->$attribute ) )
        {
            $this->addError( $attribute, $this->getAttributeLabel($attribute) . ' should be alphabet' );
            return false;
        }
        return true;
    }

    /**
     * Character Validation
     * Fungsi ini untuk memvalidasikan character - character yang diijinkan
     * @category a-z, A-Z, _(underscore), 0-9
     * 
     * @param  [type] $attribute [description]
     * @return [type]            [description]
     */
    public function characterValidation($attribute)
    {
        if ( !preg_match( '/^[a-zA-Z_0-9]*$/', $this->$attribute ) )
        {
            $this->addError( $attribute, $this->getAttributeLabel($attribute) . ' must be character, number or underscore' );
            return false;
        }
        return true;
    }


    /**
     * born Date Validation
     * Fungsi ini untuk memvalidasikan tanggal lahir tidak boleh hari ini atau esok
     *
     * @param      <type>   $attribute  The attribute
     *
     * @return     boolean  ( description_of_the_return_value )
     */
    public function bornDateValidation($attribute)
    {
        $date = $this->$attribute;

        if( $date >= date('Y-m-d') )
        {
            $this->addError( $attribute, 'Date must be less than today');
            return false;
        } 
        return true;
    }


    public function uniquenessValidation( $attribute, $params)
    {
        $countSameEmail = static::find()->where([$attribute => $this->$attribute])->count();
        if ($countSameEmail) {
            $this->addError($attribute, ucfirst($attribute) . ' is already taken.');
        }
    }

    /**
     * Gets the status.
     * Jika menggunakan function ini harus ada field row_status ditable
     * 
     * @param      <type>  $key    The key
     *
     * @return     <type>  The status.
     */
    public function getStatus()
    {
        $flag = $this->row_status;
        return $this->getStatus[$flag];
    }

    public static function getError($model)
    {
        $errorMessage = null;
        foreach ( $model->getErrors() as $field => $messages ) {
            foreach ( $messages as $message ) {
                $errorMessage .= $message . "\n";
            }
        }
        return $errorMessage;
    }
    /**
     * Saves a data.
     *
     * @param      <object>  $model  ( The Model )
     * @param      <array>  $datas  ( variable ini biasanya terdapat dari method POST )
     *
     * @return     array 
     * @var status, message
     */
    public static function saveData( $model, $datas )
    {
        $modelName = StringHelper::basename(get_class($model));

        foreach ( $datas[ $modelName ] as $field => $data ) {
            $model->$field = $data;
        }

        if ($model->save() && $model->validate())
        {
         
            Upload::save($model);
            return [ 'status' => true, 'message' => 'Success', 'id' => $model->id ];

        } else {
            $errorMessage = static::getError($model);

            return [ 'status' => false, 'message' => $errorMessage ];
        }
    }

    /**
     * Delete Data
     * Function ini tidak dapat menghapus database
     * hanya mengubah row status pada table menjadi -1 (DELETE)
     * 
     * @param      <type>                  $model  The model
     * @param      <type>                  $id     The identifier
     *
     * @throws     \yii\web\HttpException  (description)
     *
     * @return     array
     */
    public static function deleteData( $model, $id )
    {
        $model = $model::findOne($id);

        if ( empty( $model ) ) throw new \yii\web\HttpException(404, MSG_DATA_NOT_FOUND);

        $model->row_status = -1;
        if ( $model->save() )
        {
            return [ 'status' => true, 'id' => $model->tableSchema->primaryKey ];
        } else {
            $errorMessage = static::getError($model);
            return [ 'status' => false, 'message' => $errorMessage ];
        }

    }

    public static function lists()
    {
        $rowCondition = ['>', static::tableName() . '.row_status', -1];
        return static::find()->andWhere($rowCondition);
    }
}