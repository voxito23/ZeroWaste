<?php

/**
 * Custom IDE Helper definitions for Intelephense
 * Resolves unknown class & unresolved function lint errors
 */

namespace {
    if (!function_exists('public_path')) {
        /**
         * Get the path to the public folder.
         *
         * @param  string  $path
         * @return string
         */
        function public_path($path = '')
        {
            return app('path.public').($path ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : $path);
        }
    }

    if (!function_exists('storage_path')) {
        /**
         * Get the path to the storage folder.
         *
         * @param  string  $path
         * @return string
         */
        function storage_path($path = '')
        {
            return app('path.storage').($path ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : $path);
        }
    }
    
    class __UserStub {
        /** @var bool */
        public $is_admin;
        /** @var string|null */
        public $foto_perfil;
        /** @var string */
        public $nombre;
    }
    if (!function_exists('app')) {
        /**
         * @return mixed
         */
        function app($make = null, $parameters = []) { return null; }
    }
}

namespace Illuminate\Support\Facades {
    class DB {
        /**
         * @param string $table
         * @return \Illuminate\Database\Query\Builder
         */
        public static function table($table) { return new \Illuminate\Database\Query\Builder; }

        /**
         * @param string $value
         * @return \Illuminate\Database\Query\Expression
         */
        public static function raw($value) { return new \Illuminate\Database\Query\Expression; }
    }

    class Http {
        /**
         * @param int $seconds
         * @return static
         */
        public static function timeout($seconds) { return new static; }

        /**
         * @param string $url
         * @param array $query
         * @return \Illuminate\Http\Client\Response
         */
        public static function get($url, $query = null) { return new \Illuminate\Http\Client\Response; }
    }
}

namespace Illuminate\Http\Client {
    class Response {
        /** @return bool */
        public function successful() { return true; }
        /** @return array */
        public function json($key = null, $default = null) { return []; }
    }
}

namespace Illuminate\Support {
    class Carbon extends \DateTime {
        /**
         * @return static
         */
        public static function now() { return new static; }

        /**
         * @param string $format
         * @return string
         */
        public function format($format) { return ''; }

        /**
         * @param string $time
         * @param string|null $tz
         * @return static
         */
        public static function parse($time = null, $tz = null) { return new static; }
    }
}

namespace Illuminate\Database\Query {
    class Builder {
        /**
         * @param string $column
         * @param mixed $operator
         * @param mixed $value
         * @return static
         */
        public function select(...$columns) { return $this; }

        /**
         * @return static
         */
        public function whereNotNull($columns) { return $this; }

        /**
         * @return static
         */
        public function groupBy(...$groups) { return $this; }

        /**
         * @return static
         */
        public function orderBy($column, $direction = 'asc') { return $this; }

        /**
         * @return static
         */
        public function limit($value) { return $this; }

        /**
         * @return \Illuminate\Support\Collection
         */
        public function get() { return new \Illuminate\Support\Collection; }
    }

    class Expression {}
}

namespace Illuminate\Support {
    class Collection {
        /** @return static */
        public function reverse() { return $this; }
        /** @return static */
        public function values() { return $this; }
    }
}

namespace Barryvdh\DomPDF {
    class PDF {
        /**
         * @param string $filename
         * @return mixed
         */
        public function download($filename = 'document.pdf') { return null; }

        /**
         * @param string $filename
         * @return static
         */
        public function save($filename) { return $this; }
    }
}

namespace Barryvdh\DomPDF\Facade {
    class Pdf {
        /**
         * @param string $view
         * @param array $data
         * @return \Barryvdh\DomPDF\PDF
         */
        public static function loadView($view, $data = []) { return new \Barryvdh\DomPDF\PDF; }
    }
}

namespace Illuminate\Http {
    class UploadedFile {
        /**
         * @return string
         */
        public function getClientOriginalExtension() { return ''; }
        
        /**
         * @param string $directory
         * @param string|null $name
         * @return mixed
         */
        public function move($directory, $name = null) { return null; }
    }
    
    class Request {
        /**
         * @param array $rules
         * @param array $messages
         * @param array $customAttributes
         * @return array
         */
        public function validate(array $rules, ...$params) { return []; }
        
        /**
         * @param array|mixed $keys
         * @return array
         */
        public function only($keys) { return []; }

        /**
         * @param string $key
         * @return bool
         */
        public function hasFile($key) { return false; }

        /**
         * @param string $key
         * @return \Illuminate\Http\UploadedFile|array|null
         */
        public function file($key = null, $default = null) { return null; }

        /**
         * @param string $key
         * @return bool
         */
        public function has($key) { return false; }
        
        /**
         * @param string|null $key
         * @param mixed $default
         * @return mixed
         */
        public function input($key = null, $default = null) { return null; }

        /**
         * @param string|array $key
         * @return bool
         */
        public function filled($key) { return false; }

        /**
         * @return \__SessionStub
         */
        public function session() { return new \__SessionStub; }
    }

    class Response {}
}
namespace {
    class __SessionStub {
        public function invalidate() {}
        public function regenerateToken() {}
    }
}

namespace Illuminate\Database\Eloquent\Factories {
    trait HasFactory {}
}

namespace Illuminate\Database\Eloquent {
    /**
     * @method static \Illuminate\Database\Eloquent\Builder query()
     * @method static int count($columns = '*')
     * @method static \Illuminate\Database\Eloquent\Builder where($column, $operator = null, $value = null, $boolean = 'and')
     * @method static \Illuminate\Database\Eloquent\Builder whereBetween($column, array $values, $boolean = 'and', $not = false)
     * @method static \Illuminate\Database\Eloquent\Builder orderBy($column, $direction = 'asc')
     * @method static \Illuminate\Database\Eloquent\Builder orderByDesc($column)
     * @method static \Illuminate\Database\Eloquent\Builder limit($value)
     * @method static \Illuminate\Support\Collection|\static[] get($columns = ['*'])
     * @method static static create(array $attributes = [])
     * @method static static find($id, $columns = ['*'])
     * @method static \Illuminate\Database\Eloquent\Builder filter_by($column, $value)
     */
    class Model {
        /**
         * @return \Illuminate\Database\Eloquent\Relations\HasMany
         */
        public function hasMany($related, $foreignKey = null, $localKey = null) { return new \Illuminate\Database\Eloquent\Relations\HasMany; }

        /**
         * @param array $attributes
         * @return bool
         */
        public function update(array $attributes = []) { return true; }

        /** @return bool|null */
        public function delete() { return true; }

        /** @return bool */
        public function save(array $options = []) { return true; }
    }

    /**
     * @method static int count($columns = '*')
     * @method \Illuminate\Database\Eloquent\Builder where($column, $operator = null, $value = null, $boolean = 'and')
     * @method \Illuminate\Database\Eloquent\Builder whereBetween($column, array $values, $boolean = 'and', $not = false)
     * @method \Illuminate\Database\Eloquent\Builder whereNotNull($columns, $boolean = 'and')
     * @method \Illuminate\Database\Eloquent\Builder orderBy($column, $direction = 'asc')
     * @method \Illuminate\Database\Eloquent\Builder orderByDesc($column)
     * @method \Illuminate\Database\Eloquent\Builder limit($value)
     * @method \Illuminate\Support\Collection get($columns = ['*'])
     * @method \Illuminate\Database\Eloquent\Builder filter($column, $value)
     */
    class Builder {
        /**
         * @return int
         */
        public function count($columns = '*') { return 0; }

        /**
         * @return static
         */
        public function where($column, $operator = null, $value = null) { return $this; }

        /**
         * @return static
         */
        public function whereBetween($column, array $values) { return $this; }

        /**
         * @return static
         */
        public function orderBy($column, $direction = 'asc') { return $this; }

        /**
         * @return static
         */
        public function orderByDesc($column) { return $this; }

        /**
         * @return static
         */
        public function limit($value) { return $this; }

        /**
         * @return \Illuminate\Support\Collection
         */
        public function get($columns = ['*']) { return new \Illuminate\Support\Collection; }
    }
}

namespace Illuminate\Database\Eloquent\Relations {
    class HasMany {}
}

namespace Illuminate\Routing {
    /**
     * @method \Illuminate\Routing\RouteRegistrar middleware(string|array $middleware)
     */
    class RouteRegistrar {}
}
