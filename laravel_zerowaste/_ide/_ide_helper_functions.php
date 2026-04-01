<?php
class __AuthStub { /** @return __UserStub */ public function user() { return new __UserStub; } }
class __UserStub { public $nombre, $foto_perfil; }
class __RequestStub { public function routeIs($v) { return false; } }
class __RedirectStub { 
    /** @return __RedirectStub */ public function route($r) { return clone $this; } 
    /** @return __RedirectStub */ public function with($k, $v) { return clone $this; } 
    /** @return __RedirectStub */ public function withErrors($v) { return clone $this; } 
    /** @return __RedirectStub */ public function withInput($v = null) { return clone $this; } 
    /** @return __RedirectStub */ public function intended($path = '/') { return clone $this; } 
}

if (!function_exists('route')) { function route($name, ...$parameters) { return ''; } }
if (!function_exists('back')) { function back($status = 302, $headers = [], $fallback = false) { return new __RedirectStub; } }
if (!function_exists('asset')) { function asset($path) { return ''; } }
if (!function_exists('session')) { function session($key = null, $default = null) { return ''; } }
if (!function_exists('request')) { /** @return __RequestStub */ function request($key = null, $default = null) { return new __RequestStub; } }
if (!function_exists('old')) { function old($key, $default = null) { return ''; } }
if (!function_exists('view')) { function view($view, $data = []) { return ''; } }
if (!function_exists('auth')) { /** @return __AuthStub */ function auth() { return new __AuthStub; } }
if (!function_exists('redirect')) { /** @return __RedirectStub */ function redirect($to = null, $status = 302, $headers = [], $secure = null) { return new __RedirectStub; } }
if (!function_exists('bcrypt')) { function bcrypt($value, $options = []) { return ''; } }
