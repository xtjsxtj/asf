<?php

class index_controller extends base_controller {       
    public function index($param) {
        log::prn_log(DEBUG, json_encode($param));
        return $this->content;
    }
}
