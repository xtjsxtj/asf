<?php

class base_controller extends controller { 
    public function _init(){
        $this->content = $this->request->rawContent();
    }    
    
    public function _deinit(){
        
    }
}
