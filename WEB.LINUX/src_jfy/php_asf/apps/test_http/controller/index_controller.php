<?php

class index_controller extends base_controller {       
    public function index() {
        log::prn_log(DEBUG, json_encode($this->param));
        
        $db = $this->mysql;
        
        $result = $db->gearman_queue->insert([
            'unique_key' => 'd847233c-1ef2-11e5-9130-2c44fd7aee72',
            'function_name' => 'test',
            'priority' => 1,
            'data' => 'test',
            'when_to_run' => 0,
        ]);
        if ( $result === false ) return 'error';
        
        $result = $db->select_one("select * from gearman_queue where unique_key='d847233c-1ef2-11e5-9130-2c44fd7aee72'");
        if ( $result === false ) return 'error';
        var_dump($result);
        
        $result = $db->gearman_queue->update([
            'function_name' => 'testtest',
            'priority' => 100,
            'data' => 'testtesttesttest',
            'when_to_run' => 100,
        ],[
            'unique_key' => 'd847233c-1ef2-11e5-9130-2c44fd7aee72',
        ]);
        if ( $result === false ) return 'error';        
        var_dump($db->select_one("select * from gearman_queue where unique_key='d847233c-1ef2-11e5-9130-2c44fd7aee72'"));
        
        $result = $db->gearman_queue->delete([
            'unique_key' => 'd847233c-1ef2-11e5-9130-2c44fd7aee72',
        ]);         
        if ( $result === false ) return 'error';
        
        $result = $db->select_more("select * from gearman_queue limti 3");
        if ( $result === false ) return 'error';
        var_dump($result);
        
        return 'ok';
    }
}
