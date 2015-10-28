<?php

/**
 * ASF http默认路由配置类
 * @author jiaofuyou@qq.com
 * @date   2015-10-25
 */

class Route_config{
    public static $default_route = [
        //以下这条为系统底层自动填加的路由规则
        
        // POST http://localhost/index/index
        ['POST', '/{controller}/{action}[/]',        '_handler.controller_action'],
        
        // POST http://localhost/index
        ['POST', '/{controller}[/]',                 '_handler.controller'],         
        
        // POST http://localhost/index/index/prm/id
        [['GET','POST'], '/{controller}/{param:.+}', '_handler.controller_param'],
    ]; 
}
