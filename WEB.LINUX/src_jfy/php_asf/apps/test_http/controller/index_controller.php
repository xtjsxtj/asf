<?php

class index_controller extends base_controller {       
    public function index($param) {
        log::prn_log(DEBUG, json_encode($param));
        
        $result = $this->mysql->select_one('select * from optlog order by optid desc limit 1');
        
        return json_encode($result);
    }
}
