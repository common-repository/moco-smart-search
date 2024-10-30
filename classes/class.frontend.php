<?php
class MoCo_SmartFront{

    private $tpls, $layouts = array();

    /**
     * SmartFront constructor.
     */
    public function __construct() {
        $this->tpls = __DIR__ . '/../templates/';
        $this->layouts['filters'] = 'filters';
    }

    private function loadTemplate($tpl, $view = 'frontend'){
        $templateFile = $this->tpls . $view . '/tpl.' . $tpl . '.php';
        if(file_exists($templateFile)) {
            require_once($templateFile);
        }
    }

    public static function view($tpl, $view = 'frontend'){
        $templateFile = __DIR__ . '/../templates/' . $view . '/tpl.' . $tpl . '.php';
        if(file_exists($templateFile)) {
            require_once($templateFile);
        }
    }

    public function __get($name){
        $this->loadTemplate($name);
    }
}