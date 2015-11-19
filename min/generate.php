<?php
/**
 * @project: JPCACHE
 * @author: Golovkin Vladimir <rustyj4ck@gmail.com>
 * @created: 18.11.2015 19:38
 */

extract(require "config.php");

preg_match_all('@href="(.*)"@U', $styles, $matches);

$styles = $matches[1];
// $styles []= '/templates/your_theme/css/overrides.css';

array_walk(
    $styles,
    'fixUrl'
);


$styles = join(", \n", $styles);

//

preg_match_all('@src="(.*)"@U', $scripts, $matches);

$scripts = $matches[1];
// $scripts []= '/templates/your_theme/js/overrides.js';

array_walk(
    $scripts,
    'fixUrl'
);


$scripts = join(", \n", $scripts);

//

$template = file_get_contents('gulpfile.template.js');

$template = str_replace(
    ['{%id%}', '{%js%}', '{%css%}'],
    [$assetID, $scripts, $styles],
    $template
);

file_put_contents(__DIR__.'/../../gulpfile.js', $template);

echo "done", PHP_EOL;

function fixUrl(&$url) {
    if (strpos($url, '//') === 0) $url = 'http:' . $url;
    if (strpos($url, '/') === 0) $url = '.' . $url;
    $url = sprintf('"%s"', str_replace('&amp;', '&', $url));
}
