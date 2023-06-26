<?php

class AngellEye_WordPress_Custom_Route_Handler {
    private static ?AngellEye_WordPress_Custom_Route_Handler $_instance = null;
    protected $routes = array();

    protected $force_flush = false;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        add_action('parse_request',       array($this, 'parseRequestAction'));
        add_filter('query_vars',          array($this, 'queryVarsFilter'));
        add_filter('rewrite_rules_array', array($this, 'rewriteRulesArrayFilter'));
        add_action('wp_loaded',           array($this, 'wpLoadedAction'));
    }

    public function forceFlush() {
        $this->force_flush = true;
    }

    public function parseRequestAction($query)
    {
        if ($query->matched_rule and isset($this->routes[$query->matched_rule]))
        {
            $route = $this->routes[$query->matched_rule];

            $this->doCallback($route, $query);

            if ($route['template']) {
                $this->doTemplate($route);
            }

            exit;
        }
    }

    public function addRoute($match, $callback, $template = null, $query_vars = array())
    {
        $this->routes[$match] = compact('callback', 'template', 'query_vars');
    }

    public function wpLoadedAction()
    {
        $rules = get_option('rewrite_rules');
        $missing_routes = false;

        foreach ($this->routes as $key => $value) {
            $missing_routes += !isset($rules[$key]);
        }

        if ($missing_routes || $this->force_flush) {
            global $wp_rewrite;
            $wp_rewrite->flush_rules();
        }
    }

    public function rewriteRulesArrayFilter($rules)
    {
        $newrules = array();
        foreach ($this->routes as $match => $route) {
            $newrules[$match] = $this->makeRuleUrl($route);
        }

        return $newrules + $rules;
    }

    public function queryVarsFilter($vars)
    {
        foreach($this->routes as $route)
        {
            foreach($route['query_vars'] as $key => $value) {
                $vars[] = $key;
            }
        }

        return $vars;
    }

    protected function doCallback($route, $query)
    {
        $params = array();

        // params are in the same order as given in the array
        foreach($route['query_vars'] as $name => $match) {
            $params[] = $query->query_vars[$name];
        }

        call_user_func_array($route['callback'], $params);
    }

    protected function doTemplate($route)
    {
        $candidates = (array) $route['template'];

        foreach($candidates as $candidate)
        {
            if (file_exists($candidate))
            {
                include $candidate;
                break;
            }
        }
    }

    protected function makeRuleUrl($route)
    {
        $q_vars = array();

        foreach($route['query_vars'] as $name => $match) {
            $q_vars[] = $name . '=$matches[' . $match . ']';
        }

        return 'index.php?' . implode('&', $q_vars);
    }
}
