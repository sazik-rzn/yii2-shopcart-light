<?php

namespace sazik\shopcart\light;

use Yii;

/**
 * ShopCart component. 
 */
class Shopcart extends \yii\base\Component {

    /**
     * Classname of Product model
     * @var string 
     */
    public $model_class_name = false;
    
    /**
     * ID of current user component 
     * @var string 
     */
    public $user_component_id = false;
    
    /**
     * Name of ShopCart cookie
     * @var string 
     */
    public $cookie_name = 'sazik_shopcart_light';
    
    /**
     * Limit count of positions. If this value is 0, then count is unlimited
     * @var int 
     */
    public $limit = 0;
    
    /**
     * Value of ShopCart cookie
     * @var string 
     */
    private $cookie_value = false;
    
    /**
     * User`s ID
     * @var int 
     */
    private $user_id = false;
    
    /**
     * Array of positions, where keys of array is IDs of positions
     * @var array 
     */
    private $postions = [];

    public function init() {
        parent::init();
        $this->getUser();
        $this->getCookie();
        $this->reload();
    }

    /**
     * Get users`s ID
     */
    private function getUser() {
        if ($this->user_component_id) {
            $user = \Yii::$app->get($this->user_component_id);
            if ($user && !$user->isGuest) {
                $this->user_id = $user->id;
            }
        }
    }

    /**
     * Set cookie key
     */
    private function setCookie() {
        $this->cookie_value = md5(time() . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        \Yii::$app->response->cookies->add(new \yii\web\Cookie([
            'name' => $this->cookie_name,
            'value' => $this->cookie_value,
            'expire' => time() + 60 * 60 * 24 * 365
        ]));
    }

    /**
     * try to get cookie key or run $this->setCookie()
     */
    private function getCookie() {
        $cookie = \Yii::$app->request->cookies->get($this->cookie_name);
        if ($cookie) {
            $this->cookie_value = $cookie->value;
        } else {
            $this->setCookie();
        }
    }

    /**
     * Get position by ID
     * @param int $id
     * @return null|PositionModel
     */
    public function getPosition($id) {
        if (isset($this->postions[$id])) {
            return $this->postions[$id];
        }
        return null;
    }
    
    /**
     * Get all positions
     * @return array
     */
    public function getPositions(){
        return $this->postions;
    }

    /**
     * Add or update position
     * @param IPositionModel $model
     * @param int $count
     * @param int|boolean $custom_price
     * @return boolean
     */
    public function add($model, $count, $custom_price=false) {
        $position = PositionModel::add($model, $custom_price, $count, $this->user_id, $this->cookie_value);
        if ($position && ($this->limit == 0 || count($this->postions) < $this->limit)) {
            $this->postions[$position->id] = $position;
            return true;
        }
        return false;
    }

    /**
     * Remove position from ShopCart
     * @param int $position_id
     */
    public function rm($position_id) {
        if (isset($this->postions[$position_id])) {
            $this->postions[$position_id]->rm();
            unset($this->postions[$position_id]);
        }
    }
    
    public function clear(){
        foreach ($this->postions as $position){
            $position->delete();
        }
        $this->reload();
    }

    /**
     * Update position count by ID
     * @param int $id
     * @param int $count
     * @param boolean $direction - true is countUp or false is countDown
     * @param boolean $hard - hard set count
     * @return PositionModel|boolean 
     */
    public function update($id, $count, $direction, $hard = false) {
        if (isset($this->postions[$id])) {
            if (!$hard) {
                if ($direction) {
                    $this->postions[$id]->countUp($count);
                } else {
                    $this->postions[$id]->countDown($count);
                }
            } else {
                $this->postions[$id]->setCount($count);
            }
            return $this->postions[$id];
        }
        return false;
    }

    /**
     * Get common count of all positions
     * @return int
     */
    public function getCommonCount() {
        $count = 0;
        foreach ($this->postions as $position) {
            $count = $count + $position->getCount();
        }
        return $count;
    }

    /**
     * Get common coast of all positions
     * @return int
     */
    public function getCommonCoast() {
        $coast = 0;
        foreach ($this->postions as $position) {
            $coast = $coast + $position->getCoast();
        }
        return $coast;
    }

    /**
     * Load, Reload and cookie_key|user_id remap all positions
     */
    public function reload() {
        $postions = PositionModel::Magic($this->model_class_name, $this->user_id, $this->cookie_value);
        $this->postions = [];
        foreach ($postions as $position) {
            if(!$position->getModel()){
                $position->rm();
                continue;
            }
            if ($position->user_id == null && $this->user_id) {
                $position->user_id = $this->user_id;
                $position->save();
            }
            if ($position->cookie_key == null && $this->cookie_value) {
                $position->cookie_key = $this->cookie_value;
                $position->save();
            }
            $this->postions[$position->id] = $position;
        }
    }

}
