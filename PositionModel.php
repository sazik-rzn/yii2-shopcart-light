<?php

namespace sazik\shopcart\light;

/**
 * @property int $id
 * @property int $model_id
 * @property int $count 
 * @property int $price_per_piece 
 * @property int $user_id 
 * @property int $updated_at 
 * @property int $created_at
 * @property string $cookie_key
 */
class PositionModel extends \yii\db\ActiveRecord {

    public static function tableName() {
        return 'ShopCartPosition';
    }
    
    /**
     * Model instance
     * @var IPositionModel 
     */
    public $model = null;
    private $model_class_name = false;

    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::className(),
        ];
    }

    public function loadModel() {
        if ($this->model_class_name) {
            $object = \Yii::createObject($this->model_class_name);
            if ($object instanceof IPositionModel) {
                $position_model = $class_name::getByID($this->model_id);
                if ($position_model instanceof IPositionModel) {
                    $this->model = $position_model;
                }
            }
        }
    }

    public function getModel() {
        if (!($this->model instanceof IPositionModel)) {
            $this->loadModel();
        }
        return $this->model;
    }

    public function rm() {
        return $this->delete();
    }

    public function getCount() {
        return $this->count;
    }

    public function getCoast() {
        return $this->count * $this->price_per_piece;
    }

    public function setCount($count) {
        if ($count > 0) {
            $this->count = $count;
            $this->save();
        }
        return $this->rm();
    }

    public function countDown($count) {
        $this->count = $this->count - $count;
        if ($this->count <= 0) {
            return $this->rm();
        }
        return $this->save();
    }

    public function countUp($count) {
        $this->count = $this->count + $count;
        return $this->save();
    }

    public static function add($model, $custom_price = false, $count = 1, $user_id = false, $cookie_key = false) {
        if ($model instanceof IPositionModel) {
            $position = self::checkByModelID($model->getID(), $user_id, $cookie_key);
            if ($position) {
                $position->countUp($count);
            } else {
                $position = new PositionModel;
                if ($cookie_key) {
                    $position->cookie_key = $cookie_key;
                }
                if ($user_id) {
                    $position->user_id = $user_id;
                }
                $position->model_id = $model->getID();
                $position->count = $count;
                $position->price_per_piece = ($custom_price) ? $custom_price : $model->getPrice();
                $position->save();
            }
            return $position;
        }
        return false;
    }

    private static function checkByModelID($model_id, $user_id = false, $cookie_key = false) {
        $positions = self::find()->andWhere(['model_id' => $model_id]);
        if ($user_id && !$cookie_key) {
            $positions = $positions->andWhere(['user_id' => $user_id]);
        } elseif (!$user_id && $cookie_key) {
            $positions = $positions->andWhere(['cookie_key' => $cookie_key]);
        }
        elseif($user_id && $cookie_key){
            $positions = $positions->andWhere("user_id={$user_id} OR cookie_key={$cookie_key}");
        }
        else {
            return null;
        }
        return $positions->one();
    }

    private static function getAllPositions($user_id = false, $cookie_key = false) {
        $positions = self::find();
        if ($user_id && !$cookie_key) {
            $positions = $positions->andWhere(['user_id' => $user_id]);
        } elseif (!$user_id && $cookie_key) {
            $positions = $positions->andWhere(['cookie_key' => $cookie_key]);
        }
        elseif($user_id && $cookie_key){
            $positions = $positions->andWhere("user_id={$user_id} OR cookie_key={$cookie_key}");
        }
        return $positions->all();
    }

    public static function Magic($class_name, $user_id = false, $cookie_key = false) {
        $postions = self::getAllPositions($user_id, $cookie_key);
        $_positions = [];
        foreach ($postions as $position) {
            $position->model_class_name = $class_name;
            $position->loadModel();            
            if ($position->model) {
                $_positions[] = $position;
            }
        }
        return $_positions;
    }

    public static function find() {
        return parent::find();
    }

}
