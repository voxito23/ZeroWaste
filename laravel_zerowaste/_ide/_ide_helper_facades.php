<?php
namespace Illuminate\Support\Facades {
    class Hash {
        public static function make($value, array $options = []) { return ''; }
        public static function check($value, $hashedValue, array $options = []) { return false; }
    }
    class Route {
        /**
         * @param  string  $prefix
         * @return \Illuminate\Routing\RouteRegistrar
         */
        public static function prefix($prefix) { return \Illuminate\Routing\Router::prefix($prefix); }
        
        public static function get($uri, $action = null) {}
        public static function post($uri, $action = null) {}
        public static function put($uri, $action = null) {}
        public static function patch($uri, $action = null) {}
        public static function delete($uri, $action = null) {}
        public static function group($attributes, $routes) {}
        public static function fallback($action) {}
        public static function redirect($uri, $destination, $status = 302) {}
        public static function middleware($middleware) { return new self; }
        public static function name($name) { return new self; }
    }
    
    class Auth {
        public static function routes(array $options = []) {}
        public static function user() { return new \__UserStub; }
        public static function check() { return false; }
        public static function guest() { return false; }
        public static function logout() {}
        public static function attempt(array $credentials = [], $remember = false) { return false; }
        public static function login($user, $remember = false) {}
    }
}
