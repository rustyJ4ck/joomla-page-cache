<?php
/**
 * @project: JPCACHE
 * @author: Golovkin Vladimir <rustyj4ck@gmail.com>
 * @created: 18.11.2015 19:38
 */

defined('_JEXEC') or die;

/**
 * Joomla! Page Cache Plugin.
 *
 * @since  1.5
 */
class PlgSystemCache extends JPlugin
{
    var $_cache_version;

    /** @var \JCache $_cache */
    var $_cache = null;

    var $_cache_key = null;
    private $_recache = false;

    /**
     * Constructor.
     *
     * @param   object &$subject The object to observe.
     * @param   array $config An optional associative array of configuration settings.
     *
     * @since   1.5
     */
    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);

        $app  = JFactory::getApplication();

        // Set the language in the class.
        $options = array(
            'defaultgroup' => 'page-cache-v2',
            'browsercache' => $this->params->get('browsercache', false),
            'caching'      => true,
            'lifetime'     => 3600 * 24 * 7,
        );

        // $this->_cache =  JCache::getInstance('page', $options);
        $this->_cache     = new JCache($options);
        $this->_cache_key = JUri::getInstance()->toString();

        $this->_cache_version = $app->get('page_cache_version', 1);

        if (strpos($this->_cache_key, '?nocache') !== false) {
            $this->_cache_key = str_replace('?nocache', '', $this->_cache_key);
            $this->_recache   = true;
        }

        // Logger::d('%s %s - %s', __METHOD__, get_class($this->_cache), $this->_cache_key);

        $this->checkCache();

        $this->_cache->setCaching(false);
    }

    function checkCache()
    {
        $app  = JFactory::getApplication();
        $user = JFactory::getUser();

        if ($this->_recache) { 
            $this->_cache->remove($this->_cache_key);
            return;
        }

        if ($app->isAdmin() || $this->isDisabled() || count($app->getMessageQueue()) || $user->get('admin') || $app->input->getMethod() !== 'GET') {
            return;
        }

        $data = $this->_cache->get($this->_cache_key);

        if (false === strpos($data, sprintf('cached:%d', $this->_cache_version))) {
            // Logger::d('ERROR: Cache version mismatch');
            return;
        }

        if ($data !== false) {
            // Set cached body.
            $app->setBody($data);

            echo $app->toString($app->get('gzip'));

            // Logger::d(__METHOD__.' from cache');

            $app->close();

            exit;
        }
    }

    function isDisabled()
    {
        //if (Logger::isDebug()) return true;
        return false;
    }

    /**
     * Converting the site URL to fit to the HTTP request.
     *
     * @return  void
     *
     * @since   1.5
     */
    public function onAfterInitialise()
    {
        // Logger::d(__METHOD__);

        global $_PROFILER;

        $app  = JFactory::getApplication();
        $user = JFactory::getUser();

        if ($app->isAdmin()) {
            return;
        }

        if (count($app->getMessageQueue())) {
            return;
        }

        if ($this->isDisabled()) {
            return;
        }


        if ($user->get('guest') && $app->input->getMethod() == 'GET') {
            $this->_cache->setCaching(true);
        }
    }

    /**
     * After render.
     *
     * @return   void
     *
     * @since   1.5
     */
    public function onAfterRender()
    {
        $app = JFactory::getApplication();

        if ($app->isAdmin()) {
            return;
        }

        if (count($app->getMessageQueue())) {
            return;
        }

        if ($this->isDisabled()) {
            return;
        }

        $user = JFactory::getUser();

        if ($user->get('guest') && $app->input->getMethod() == 'GET') {
            $this->_cache->store($this->optimize($app->getBody()), $this->_cache_key);
        }
    }

    private function removeWhitespaces(&$buffer) {
        $buffer = preg_replace('@(\s)\s{1,}@', '\\1', $buffer);
    }

    /**
     * @param $content
     * @return string content
     */
    function optimize($content)
    {

        // Logger::d(__METHOD__);

        $config = 	__DIR__ . '/min/config.php';
        extract($config);

        $styles = explode("\n", $styles);

        foreach ($styles as $style) {
            $style = trim($style);
            if ($style && strpos($content, $style) !== false) {
                // Logger::d('style|opt %s', $style);
                $content = str_replace($style, '', $content);
            }
        }

        $scripts = explode("\n", $scripts);

        foreach ($scripts as $script) {
            $script = trim($script);
            if ($script && strpos($content, $script) !== false) {
                // Logger::d('script|opt %s', $script);
                $content = str_replace($script, '', $content);
            }
        }

        $head =
        sprintf('<link rel="stylesheet" href="/assets/_/%s.css" type="text/css" />', $assetID);

        $head .=
        sprintf('<script src="/assets/_/%s.js" type="text/javascript"></script>', $assetID);

        $content = preg_replace('@<base href=".*" />@U', '<base href="/" />', $content);

        $content = str_replace('</title>', '</title>' . $head, $content);

        $this->removeWhitespaces($content);

        $content .= PHP_EOL;
        $content .= PHP_EOL;
        $content .= sprintf('<!--cached:%d %s | %s-->', $this->_cache_version, $this->_cache_key, date('d.m.Y H:i:s'));

        return $content;

    }
}
