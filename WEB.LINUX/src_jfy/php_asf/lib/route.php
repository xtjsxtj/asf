<?php

/**
 * ASF http路由分析处理类
 * @author jiaofuyou@qq.com
 * @date   2015-10-25
 * 
 * 使用第三方fast-route库
 * https://github.com/nikic/FastRoute
 */

class route{
    private $dispatcher;
    private $config_route;     
    
    public function __construct($config) {
        $this->config_route = array_merge($config, Route_config::$default_route);
        //var_dump($this->config_route);        
        $this->dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
            foreach($this->config_route as $route){
                log::prn_log(DEBUG, 'addRoute: '.  json_encode($route));
                $r->addRoute($route[0], $route[1], [$this, $route[2]]); 
            }
        });
    }   
    
    public function handel_route($method, $uri){
        $route_info = $this->dispatcher->dispatch($method, $uri);
        log::prn_log(DEBUG, json_encode($route_info));
        switch ($route_info[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                return 404;
                break;
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allow_methods = $route_info[1];
                return 405;
                break;
            case FastRoute\Dispatcher::FOUND:
                $handler = $route_info[1][1];
                if ( substr($handler,0,9) === '_handler.' ) {
                    return call_user_func(array($this,substr($handler,9)), $route_info);                    
                } else {
                    list($ret['class'],$ret['fun']) = explode('.', $handler);
                    $ret['param'] = $route_info[2];
                    return $ret;
                }
                break;
        }
    }
    
    private function controller_action($route_info){
        return [
            'class' => $route_info[2]['controller'],
            'fun' => $route_info[2]['action'],
        ];
    }
    
    private function controller_action_param($route_info){
        $class = $route_info[2]['controller'];
        $fun = $route_info[2]['action'];
        unset($route_info[2]['controller']);      
        unset($route_info[2]['action']);
        return [
            'class' => $class,
            'fun' => $fun,
            'param' => explode('/', $route_info[2]),
        ];
    }
    
    private function controller($route_info){
        return [
            'class' => $route_info[2]['controller'],
            'fun' => 'index',
        ];
    }
    
    private function controller_param($route_info){
        $class = $route_info[2]['controller'];
        unset($route_info[2]['controller']);
        return [
            'class' => $class,            
            'fun' => 'index',
            'param' => explode('/', $route_info[2]),
        ];        
    }
    
}
