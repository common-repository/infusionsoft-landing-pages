<?php
namespace Infusionsoft\WordPress\LandingPages;

if (!defined('ABSPATH')) {
    die();
}

class Core {
    public function __construct() {
        add_action('template_redirect', array($this, 'templateRedirect'), 1);
    }

    public function templateRedirect() {
        $permalink_structure = get_option( 'permalink_structure' );
        $is_plain_ifs = ! $permalink_structure && isset( $_GET['ifs'] ) && $_GET['ifs'] ? true : false;
        if ( ! is_404() && ! $is_plain_ifs ) {

            return;
        }
        // get the landing page
        $landing_page = self::findMatchingInfusionsoftLandingPage($_SERVER['REQUEST_URI'], $is_plain_ifs);

        // Make sure we have a trailing slash
        if ($landing_page) {
            $url = wp_parse_url($_SERVER['REQUEST_URI']);

            if (substr($url['path'], -1) <> '/') {
                $new_url = $url['path'] . '/';
                if (!empty($url['query'])) {
                    $new_url .= '?' . $url['query'];
                }
                wp_redirect($new_url);
                exit;
            } else {
                self::renderInfusionsoftLandingPage($landing_page);
            }
        }
    }

    /**
     * @param $url
     * @param $is_plain_ifs Is this a plain-permalink URL
     * @return array|bool
     */
    public static function findMatchingInfusionsoftLandingPage($url, $is_plain_ifs) {
        $url = wp_parse_url($url);

        // modify url for plain permalink query
        if ( $is_plain_ifs && !empty($url['query']) ) {
            $url['path'] = '/' . array_pop( explode( 'ifs=', $url['query'] ) );
        }

        if (empty($url['path'])) {
            return false;
        }

        if (!empty($url['path'])) {
            if (substr($url['path'], -1) === '/') {
                $url['path'] = substr($url['path'], 0, -1);
            }
        }
        
        $home=site_url( '', 'relative');
        $result = false;

        if ( !empty($url['path']) ) {
            $pages = Core::getInfusionsoftLandingPages();
            foreach ($pages as $page) {
                if ($page['active']) {
					if ($home.$page['stub'] === $url['path']) {
                    //if ($home.$page['stub'] === $url['path']) {
                        $result = $page;
                        $result['thankyou'] = false;
                    }
                	if ($home.$page['stub'] . '/thank-you.html' === $url['path']) {
                    //if ($page['stub'] . '/thank-you.html' === $url['path']) {
                        $result = $page;
                        $result['thankyou'] = true;
                    }
                }
            }
        }

        return $result;
    }

    public static function getInfusionsoftLandingPages() {
        $pages = get_option('ILPPages', array());
        return $pages;
    }

    public static function getLandingPage($url) {
        $signature = 'ILPPageCache_' . sha1($url);
        $valid = true;
        $cached_page = get_option($signature, array('body' => '', 'time' => 1));

        if (!is_array($cached_page)
            || empty($cached_page['body'])
            || time() - $cached_page['time'] >= 30) {
            $valid = false;
        }

        if ($valid) {
            $cached_page['body'] .= '<!-- Cached -->';
        } else {
            $args = array('timeout' => 10);
            $content = wp_remote_get($url, $args);
            if (is_array($content) && isset($content['body'])) {
                $cached_page['body'] = $content['body'];
                $cached_page['time'] = time();
                if (!empty($cached_page['body'])) {
                    update_option($signature, $cached_page, false);
                }
            } elseif (is_wp_error($content)) {
                $error = $content->get_error_message();
                error_log($error);
            }
        }

        return $cached_page['body'];
    }

    public static function recordView($page) {
        if (!empty($page['thankyou'])) {
            return;
        }
        if (stripos($_SERVER['HTTP_ACCEPT'] === false, 'html')) {
            return;
        }
        if ($_SERVER['HTTP_ACCEPT'] === '*/*') {
            return;
        }

        $key = 'ILPCounter_' . $page['id'];
        $counter = get_option($key, 0);
        $counter++;
        update_option($key, $counter, false);
    }

    public static function renderInfusionsoftLandingPage($page) {
        $request = wp_parse_url($_SERVER['REQUEST_URI']);
        $url = $page['url'];

        header('HTTP/1.1 200 OK');

        if ($page['thankyou']) {
            $url .= '/thank-you.html';
        }
        if (!empty($request['query'])) {
            $url .= '?' . $request['query'];
        }

        self::recordView($page);

        if ($page['mode'] === 'embed') {
            if (isset($page['embedCode']) && strlen($page['embedCode'])) {
                ?>
<html>
    <head>
        <meta name="viewport" content="width=device-width,initial-scale=1">

        <style>
            body {
                margin: 0;
                padding: 0;
            }
        </style>
    </head>
    <body> 
        <?= $page['embedCode'] ?>
    </body>
</html>
            <?php
                die();
            }

            // everything below this is legacy
            // but legacy entries may not have an embedCode
            $content = Core::getLandingPage($url);
            if (!empty($content)) {
                echo do_shortcode($content);
                die();
            }
            $page['mode'] = 'iframe';
        }

        if ($page['mode'] === 'iframe') {
    ?><html>
<head>
    <title><?= esc_html($page['title']) ?></title>
</head>
<body>
<iframe style="border:none; width:100%; height:100%; overflow:hidden;" src="<?= esc_url($url) ?>"><?= $page['body'] ?></iframe>
</body>
    </html><?php
            die();
        } elseif ($page['mode'] === 'redirect') {
            wp_redirect($url);
            die();
        }
    }
}

new Core();
