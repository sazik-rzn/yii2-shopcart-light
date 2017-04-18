<?php

namespace sazik\shopcart\light;
interface IPositionModel {
    public function getPrice();
    public function getID();
    public static function getByID($id);
}

