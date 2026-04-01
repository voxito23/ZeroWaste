<?php

namespace Illuminate\Database {
    class Seeder {
        public function call($class, $silent = false, array $parameters = []) {}
    }
}

namespace Illuminate\Database\Console\Seeds {
    trait WithoutModelEvents {}
}

namespace Illuminate\Database\Eloquent {
    class Model {
        /**
         * @param array $attributes
         * @param array $values
         * @return static
         */
        public static function updateOrCreate(array $attributes, array $values = []) { return new static; }
        
        /**
         * @param array $attributes
         * @return static
         */
        public static function create(array $attributes = []) { return new static; }
        
        /**
         * @param mixed $id
         * @param array $columns
         * @return static|null
         */
        public static function find($id, $columns = ['*']) { return new static; }
        
        /**
         * @return static[]
         */
        public static function all($columns = ['*']) { return []; }
    }
    class Builder {
        public function get($columns = ['*']) { return new Collection(); }
    }
    class Collection {}
}

namespace Illuminate\Routing {
    class Router {
        public static function prefix($prefix) { return new RouteRegistrar(); }
    }
    class RouteRegistrar {
        public function group($callback) {}
        public function middleware($middleware) { return $this; }
    }
}

namespace Illuminate\Foundation\Auth {
    class User extends \Illuminate\Database\Eloquent\Model {}
}

namespace Illuminate\Notifications {
    trait Notifiable {}
}
