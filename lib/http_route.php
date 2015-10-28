<?php

class http_route{
    private $dispatcher;
    private $default_route = [
        ['POST', '/{controller}/{action}[/]',        'controller_action'],
        ['POST', '/{controller}[/]',                 'controller'],         
        [['GET','POST'], '/{controller}/{param:.+}', 'controller_param'],
    ]; 
    private $config_route;     
    
    public function __construct($config) {
        $this->config_route = array_merge($config, $this->default_route);
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
                $handler = $route_info[1];
                return call_user_func($handler, $route_info);
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
            'param' => $route_info[2],
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
            'param' => $route_info[2],
        ];        
    }
    
}
