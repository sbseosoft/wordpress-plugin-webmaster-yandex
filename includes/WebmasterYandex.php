<?php
/*  Copyright 2013  Sbseosoft  (email : contact@sbseosoft.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class WebmasterYandex
{
    /**
     * Parameter names for storage in Wordpress settings table
     */
    const PARAMETER_NAME_APP_ID         = 'webmaster_yandex_app_id';
    const PARAMETER_NAME_APP_PASSWORD   = 'webmaster_yandex_app_password';
    const PARAMETER_YANDEX_TOKEN_CODE   = 'webmaster_yandex_token_code';
    const PARAMETER_YANDEX_TOKEN        = 'webmaster_yandex_token';
    const PARAMETER_YANDEX_TOKEN_EXPIRE = 'webmaster_yandex_token_expire';
    const PARAMETER_WEBSITE_ID          = 'webmaster_yandex_website_id';
    
    const DB_TABLE_STAT_TEXTS_NAME      = 'wm_ya_stat_texts';
    const DB_TABLE_TEXTS_NAME           = 'wm_ya_texts';
    
    const YANDEX_WEBMASTER_HOST         = 'webmaster.yandex.ru';
    const YANDEX_API_REQUEST_TIMEOUT    = 5;
    const YANDEX_VERIFIED_STATUS_NAME   = 'VERIFIED';
    const YANDEX_TEXT_MIN_LENGTH        = 500;
    const YANDEX_TEXT_MAX_LENGTH        = 32000;
    const YANDEX_TEXT_MAX_PER_DAY       = 100;
    
    private $_applicationId;
    private $_applicationPassword;
    private $_yandexCodeForToken;
    private $_yandexToken;
    private $_yandexTokenExpire;
    private $_websiteId;
    private $_appIdOrPasswordEmpty = true;
    private $_yandexTokenNotSet = true;
    private $_websiteIdNotSet = true;
    private $_wesiteIsNotVerified = true;
    
    public $websiteVerificationStatuses = array(
        'IN_PROGRESS'           => array('short' => 'В процессе', 'long' => 'Производится проверка заявленных прав на управление сайтом.'),
        'NEVER_VERIFIED'        => array('short' => 'Не подтверждался', 'long' => 'Права на управление сайтом ранее никогда не подтверждались.'),
        'VERIFICATION_FAILED'   => array('short' => 'Ошибка подтверждения', 'long' => 'Ошибка при попытке подтверждения прав.'),
        'VERIFIED'              => array('short' => 'Подтвержден', 'long' => 'Права подтверждены.'),
        'WAITING'               => array('short' => 'Ожидает', 'long' => 'Ожидание в очереди на подтверждение.'),
    );

    public function __construct() {
        $this->initLocalization();
        $this->addActions();
        $this->loadSettings();        
    }
    
    /**
     * Set Yandex Application Id
     * 
     * @param string $appId
     * @return \WebmasterYandex
     */
    public function setAppId($appId) {
        $this->_applicationId = $appId;
        return $this;
    }
    
    /**
     * Get Yandex Application Id
     * 
     * @return string
     */
    public function getAppId() {
        return $this->_applicationId;
    }
    
    /**
     * Set Yandex code for token
     * 
     * @param string $code
     * @return \WebmasterYandex
     */
    public function setYandexCode($code) {
        $this->_yandexCodeForToken = $code;
        return $this;
    }
    
    /**
     * Get Yandex code for token
     * 
     * @return string
     */
    public function getYandexCode() {
        return $this->_yandexCodeForToken;
    }
    
    /**
     * Set Yandex Application password
     * 
     * @param string $appPassword
     * @return \WebmasterYandex
     */
    public function setAppPassword($appPassword) {
        $this->_applicationPassword = $appPassword;
        return $this;
    }
    
    /**
     * Get Yandex Application password
     * 
     * @return string
     */
    public function getAppPassword() {
        return $this->_applicationPassword;
    }
    
    /**
     * Set Yandex token
     * 
     * @param string $token
     * @return \WebmasterYandex
     */
    public function setYandexToken($token) {
        $this->_yandexToken = $token;
        return $this;
    }
    
    /**
     * Get Yandex Token
     * 
     * @return string
     */
    public function getYandexToken() {
        return $this->_yandexToken;
    }
    
    /**
     * Set Yandex token expiration date (unix timestamp)
     * 
     * @param int $expire
     * @return \WebmasterYandex
     */
    public function setYandexTokenExpire($expire) {
        $this->_yandexTokenExpire = $expire;
        return $this;
    }

    /**
     * Get Yandex token expiration date (unix timestamp)
     * 
     * @return int
     */
    public function getYandexTokenExpire() {
        return $this->_yandexTokenExpire;
    }
    
    /**
     * Set website Id
     * 
     * @param int $websiteId
     * @return \WebmasterYandex
     */
    public function setWebsiteId($websiteId) {
        $this->_websiteId = $websiteId;
        return $this;
    }

    /**
     * Get website Id
     * 
     * @return int
     */
    public function getWebsiteId() {
        return $this->_websiteId;
    }

    /**
     * Get Yandex Url to receive auth code
     * 
     * @return string
     */
    public function getYandexCodePopupUrl() {
        $url = "https://oauth.yandex.ru/authorize?response_type=code&client_id=" . $this->getAppId() . "&display=popup";
        return $url;
    }

    /**
     * Load setting from Wordpress database
     */
    public function loadSettings() {
        $this->setAppId(get_option(self::PARAMETER_NAME_APP_ID))
             ->setAppPassword(get_option(self::PARAMETER_NAME_APP_PASSWORD))
             ->setYandexToken(get_option(self::PARAMETER_YANDEX_TOKEN))
             ->setYandexTokenExpire(get_option(self::PARAMETER_YANDEX_TOKEN_EXPIRE))
             ->setWebsiteId(get_option(self::PARAMETER_WEBSITE_ID));
        if ($this->getAppId() == 'none' or $this->getAppId() == ''
            or $this->getAppPassword() == 'none' or $this->getAppPassword() == '') {
            $this->_appIdOrPasswordEmpty = true;
        } else {
            $this->_appIdOrPasswordEmpty = false;
        }
        $websiteId = $this->getWebsiteId();
        $yandexToken = $this->getYandexToken();
        $this->_websiteIdNotSet = ($websiteId == 'none' or empty($websiteId));        
        $this->_yandexTokenNotSet = ($yandexToken == 'none' or empty($yandexToken));
    }
    
    /**
     * Get information about text by post Id
     * 
     * @global mixed $wpdb
     * @param int $postId
     * @return mixed
     */
    public function getTextInfoFromDb($postId) {
        global $wpdb;
        $data = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . self::DB_TABLE_TEXTS_NAME . ' WHERE post_id = ' . $postId);
        return $data;
    }
    
    /**
     * Get quantity of text that was sent to Yandex Webmaster API today
     * 
     * @global type $wpdb
     * @return type
     */
    public function getTextsNumSentToday() {
        global $wpdb;
        $today = date('Y-m-d');
        $data = $wpdb->get_results('SELECT texts_sent FROM ' . $wpdb->prefix . self::DB_TABLE_STAT_TEXTS_NAME .
                                   " WHERE date = '{$today}'");               
        return $data;
    }
    
    public function activationHook() {
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function initLocalization() {
        $currentLocale = get_locale();
        if (!empty($currentLocale)) {
            $moFile = dirname(__FILE__) . "/../lang/" . $currentLocale . ".mo";
            if (file_exists($moFile) and is_readable($moFile)) {
                load_textdomain('wm_ya', $moFile);
            }
        }
    }

    public function addActions() {
        add_action('admin_menu', array($this, 'adminOptions'));
        add_action('admin_init', array($this, 'registerSettings'));   
        add_action('admin_head', array($this, 'addGoogleChartsJs'));
        add_action('wp_ajax_wm_ya_add_text', array($this, 'ajaxAddTextToYandex'));
        add_action('wp_ajax_wm_ya_dashboardChartData', array($this, 'getDataForDashboard'));
    }
    
    public function registerSettings() {
        register_setting('webmaster_yandex_app', 'webmaster_yandex_options_app');
        if (!get_option(self::PARAMETER_NAME_APP_ID)) {
            add_option(self::PARAMETER_NAME_APP_ID, 'none');
        }
        if (!get_option(self::PARAMETER_NAME_APP_PASSWORD)) {
            add_option(self::PARAMETER_NAME_APP_PASSWORD, 'none');
        }
        if (!get_option(self::PARAMETER_YANDEX_TOKEN)) {
            add_option(self::PARAMETER_YANDEX_TOKEN, 'none');
        }
        if (!get_option(self::PARAMETER_YANDEX_TOKEN_EXPIRE)) {
            add_option(self::PARAMETER_YANDEX_TOKEN_EXPIRE, 'none');
        }
        if (!get_option(self::PARAMETER_WEBSITE_ID)) {
            add_option(self::PARAMETER_WEBSITE_ID, 'none');
        }
        // Set application id and password
        add_settings_section(
            'wm_ya_settings_section_app',
            __('Application settings', 'wm_ya'),
            array($this, 'appSettingsAppCallback'),
            'webmaster_yandex_options_page'
        );  
        add_settings_field(
            self::PARAMETER_NAME_APP_ID,
            __('Application ID', 'wm_ya'),
            array($this, 'applicationIdCallback' ),
            'webmaster_yandex_options_page',
            'wm_ya_settings_section_app'
        );
        add_settings_field(
            self::PARAMETER_NAME_APP_PASSWORD,
            __('Application password', 'wm_ya'),
            array($this, 'applicationPasswordCallback'),
            'webmaster_yandex_options_page',
            'wm_ya_settings_section_app'
        );
        add_settings_field(
            self::PARAMETER_YANDEX_TOKEN_CODE,
            __('Token code', 'wm_ya'),
            array($this, 'yandexTokenFieldCallback'),
            'webmaster_yandex_options_page',
            'wm_ya_settings_section_app'
        );
    }
    
    public function adminOptions() {
        add_options_page(__('Webmaster Yandex settings', 'wm_ya'), 'Webmaster Yandex',
                         'manage_options', 'webmaster_yandex_options_page',
                         array($this, 'showSettingsPage'));
        // on next release
        add_dashboard_page('Webmaster Yandex', 'Webmaster Yandex', 'manage_options',
                           'webmaster_yandex_dashboard', array($this, 'showAdminDashboard'));
        add_meta_box('wm_ya_metabox_add_text', __('Send text to Yandex', 'wm_ya'),
                     array($this, 'metaboxSendTextsToYandexCallback'), 'post', 'advanced',
                     'high');
    }
    
    /**
     * Show metabox at the post add/edit/delete page
     * 
     * @return type
     */
    public function metaboxSendTextsToYandexCallback() {
        if ($this->_websiteIdNotSet) {
            print "<p>" . __('Set website Id in plugin settings page', 'wm_ya') . "</p>";
            return;
        }
        wp_register_script('webmaster_yandex_script', plugins_url('/../main.js', __FILE__), array('jquery'));
        wp_enqueue_script('webmaster_yandex_script');
        wp_enqueue_script('jquery');
        $postId = get_the_ID();
        $postDataDb = $this->getTextInfoFromDb($postId);
        
        if (!count($postDataDb)) {
            print "<p id='wmYaTextSendDate'>" . __("You haven't sent this text to Yandex yet", 'wm_ya') . "</p>";
        } else {
            $obj = $postDataDb[0];
            $textSentDatetime = date('Y-m-d H:i:s', $obj->timestamp_added);
            print "<p id='wmYaTextSendDate'>" . __('Text was sent at ', 'wm_ya') . "{$textSentDatetime}</p>";
        }
        
        $str = "<a class='button button-primary' onclick='jQuery(Main.wmAddText);'>" . __('Send', 'wm_ya') . "</a><div id='wmYaResultsTextSend'>";
        $str .= "</div><input type='hidden' id='wmYaCurrentPostId' value='{$postId}' />";
        $str .= "";
        print $str;
    }

    public function ajaxAddTextToYandex() {
        global $wpdb;
        $postId = intval($_REQUEST['postId']);
        $postData = get_post($postId);
        
        $responseObj = new stdClass();
        if (is_object($postData)) {
            $postContentFiltered = strip_tags($postData->post_content);
            $postLength = strlen($postContentFiltered);
            if ($postLength > self::YANDEX_TEXT_MAX_LENGTH) {
                $responseObj->error = 1;
                $responseObj->errorText = __('Text length is more than Yandex allows to send, your text length is: ',
                                             'wm_ya') . $postLength;
            }
            if ($postLength < self::YANDEX_TEXT_MIN_LENGTH) {
                $responseObj->error = 1;
                $responseObj->errorText = __('Text length is less than Yandex allows to send, your text length is: ',
                                             'wm_ya') . $postLength;
            }
            $textsSendData = $this->getTextsNumSentToday();
            if (count($textsSendData)) {
                $textsSendQuantity = $textsSendData['texts_sent'];
                if ($textsSendQuantity > self::YANDEX_TEXT_MAX_PER_DAY) {
                    $responseObj->error = 1;
                    $responseObj->errorText = __('Maximum number of sent texts were reached for today',
                                                 'wm_ya');
                }
            }
            if (property_exists($responseObj, 'error')) {
                print json_encode($responseObj);
                die;
            }
            $yandexResult = $this->sendTextToYandex($postData->post_content);
            if ($yandexResult['error']) {
                $responseObj->error = 1;
                $responseObj->errorText = $yandexResult['yandexError'];
            } else {
                $responseObj->error = 0;
                $responseObj->yandexId = $yandexResult['yandexId'];
                $responseObj->yandexLink = $yandexResult['yandexLink'];
                $dbTable = $wpdb->prefix . self::DB_TABLE_TEXTS_NAME;
                $sql = "INSERT INTO {$dbTable} (timestamp_added, post_id, yandex_text_id, yandex_link) VALUES (%d, %d, %d, %s)" . 
                       " ON DUPLICATE KEY UPDATE timestamp_added = %d, yandex_text_id = %d, yandex_link = %s";
                $timestamp = current_time('timestamp');
                $wpdb->query($wpdb->prepare($sql, $timestamp, $postId, 
                                            $responseObj->yandexId, $responseObj->yandexLink,
                                            $timestamp, $responseObj->yandexId, $responseObj->yandexLink));
                $dbTable = $wpdb->prefix . self::DB_TABLE_STAT_TEXTS_NAME;
                $today = date('Y-m-d', current_time('timestamp'));
                $sql = "INSERT INTO {$dbTable} (date, texts_sent) VALUES (%s, 1) ON DUPLICATE KEY UPDATE texts_sent = texts_sent + 1";
                $wpdb->query($wpdb->prepare($sql, $today));
                print json_encode($responseObj);
                die;
            }
        }
        $responseObj->error = 1;
        $responseObj->errorText = __('Unable to fetch data for post Id provided', 'wm_ya');
        print json_encode($responseObj);
        die;
    }

    public function appSettingsAppCallback() {
        if ($this->_appIdOrPasswordEmpty) {
            $screenShotURL = plugins_url('/../oauth-yandex-screenshot.png', __FILE__);
            $str =  "<p style='color:red;'>" 
                    . __('Application Id or application password not defined, create them at ' . 
                         '<a href="https://oauth.yandex.ru/client/new">oAuth Yandex</a>, or use <a href="https://oauth.yandex.ru/client/my">exising</a>, and set values below.', 'wm_ya')
                    . "</p>" .
                    "<p><a href='{$screenShotURL}' target='_blank'><img src='{$screenShotURL}' border='0' style='max-height: 200px;' /></a></p>" .
                    "<p style='color:red;'><b>Callback URI:</b> https://oauth.yandex.ru/verification_code</p>" .
                    '<p><iframe width="640" height="360" src="//www.youtube.com/embed/jJ15I23KCKo?rel=0" frameborder="0" allowfullscreen></iframe></p>';
        } else {
            $str = "<p>" . __('Edit application Id or application password in fields below', 'wm_ya') . "</p>";
        }
        print $str;
    }
    
    public function appSettingsTokenCallback() {
        print "<p>" . __('Get code from Yandex and paste it below to get new token', 'wm_ya') . "</p>";
    }
    
    public function applicationPasswordCallback() {
        printf(
            '<input type="text" id="wm_ya_app_password" name="webmaster_yandex_options_app[' . 
            self::PARAMETER_NAME_APP_PASSWORD . ']" value="%s" size="40" />',
            (strlen($this->getAppPassword()) and $this->getAppPassword() !== 'none')
                ? esc_attr($this->getAppPassword()) 
                : ''
        );
    }

    public function applicationIdCallback() {
        printf(
            '<input type="text" id="wm_ya_app_id" name="webmaster_yandex_options_app[' . self::PARAMETER_NAME_APP_ID . ']" value="%s" size="40" />',
            (strlen($this->getAppId()) and $this->getAppId() !== 'none')
                ? esc_attr($this->getAppId()) 
                : ''
        );
    }
    
    public function yandexTokenFieldCallback() {
        print(
            '<input type="text" id="wm_ya_token_code" name="webmaster_yandex_options_app['
            . self::PARAMETER_YANDEX_TOKEN_CODE . ']" value="" size="20" />'
        );
    }
    
    /**
     * Check if website is verified in Yandex Webmaster service
     * 
     * @param type $websiteId
     * @return mixed
     */
    public function checkWebsiteIsVerified($websiteId) {
        $existingWebsites = $this->getWebmasterWebsites();
        if (count($existingWebsites)) {
            foreach ($existingWebsites as $k => $wData) {
                if ($wData['website_id'] == $websiteId and $wData['state'] == self::YANDEX_VERIFIED_STATUS_NAME) {
                    return true;
                }           
            }
        } else {
            return null;
        }
        return false;
    }

    /**
     * 
     * Display settings page and validate post data
     * 
     */
    public function showSettingsPage() {
        wp_register_script('webmaster_yandex_script', plugins_url('/../main.js', __FILE__), array('jquery'));
        wp_enqueue_script('webmaster_yandex_script');
        wp_enqueue_script('jquery');
        
        $curlEnabled = in_array('curl', get_loaded_extensions());
        $options = get_option('webmaster_yandex_options_app');        
        if (isset($options['webmaster_yandex_app_id']) and isset($options['webmaster_yandex_app_password'])) {
            update_option(self::PARAMETER_NAME_APP_ID, sanitize_text_field($options['webmaster_yandex_app_id']));
            update_option(self::PARAMETER_NAME_APP_PASSWORD, sanitize_text_field($options['webmaster_yandex_app_password']));
            $this->loadSettings();
        }
        if (isset($options['webmaster_yandex_token_code']) and strlen(trim($options['webmaster_yandex_token_code'])) > 1) {
            $code = intval($options['webmaster_yandex_token_code']);
            $yandexTokenFetchResult = $this->getYandexTokenByCode($code);
            if ($yandexTokenFetchResult !== null and is_object($yandexTokenFetchResult)) {
                update_option(self::PARAMETER_YANDEX_TOKEN, $yandexTokenFetchResult->access_token);
                update_option(self::PARAMETER_YANDEX_TOKEN_EXPIRE, current_time('timestamp') + intval($yandexTokenFetchResult->expires_in));
                $this->loadSettings();
            }
        }
        if (isset($options['website_id']) and intval($options['website_id']) > 0) {
            $websiteId = intval($options['website_id']);
            update_option(self::PARAMETER_WEBSITE_ID, $websiteId);
            $this->loadSettings();
//            if ($this->checkWebsiteIsVerified($websiteId)) {
                
//            }
        }
        print "<div class='wrap'><h2>" . __('Yandex Webmaster settings', 'wm_ya') . "</h2>" . screen_icon();
        if (!$curlEnabled) {
            print "<div style='color: red'>" . __('Curl module is not enabled, plugin will not work', 'wm_ya') . "</div>";
        }
        print "<form method='post' action='options.php'>\n";
        settings_fields('webmaster_yandex_app');
        do_settings_sections('webmaster_yandex_options_page');
        print "<div class='wrap'>";
        if (!$this->_appIdOrPasswordEmpty) {
            $popupUrl = $this->getYandexCodePopupUrl();
            $popupTitle = "Yandex Code";
            print "<p><a href='#' onclick='jQuery(Main.showPopup(\"$popupUrl\", \"$popupTitle\"));'>" 
                  . __('Get token code', 'wm_ya') . "</a>";
            if (!$this->_yandexTokenNotSet and strlen($this->getYandexToken()) > 10) {
                print "<p>Yandex token:&nbsp;" . $this->getYandexToken() . "&nbsp;" .
                      __('expires', 'wm_ya') . "&nbsp;" . date('Y-m-d H:i:s', $this->getYandexTokenExpire()) . "</p>";
                // Website settings
                $existingWebsites = $this->getWebmasterWebsites();
                if (count($existingWebsites)) {
                    print __('Set your website', 'wm_ya') . ": <select name='webmaster_yandex_options_app[website_id]'>\n";
                    $selected = '';
                    foreach ($existingWebsites as $k => $wData) {
                        if ($wData['website_id'] == $this->getWebsiteId()) {
                            $selected = "selected='selected'";
                        }
                        $state = $this->websiteVerificationStatuses[$wData['state']]['short'];
                        print "<option value={$wData['website_id']} {$selected}>{$wData['name']} " . 
                              "(" . __('Status in Yandex', 'wm_ya') . " - {$state})</option>";
                        $selected = '';
                    }
                    print "</select>";
                }
            }
        }
        print "</div>";
        submit_button();
        print '</form></div>';
    }
    
    public function addGoogleChartsJs() {
        echo '<script type="text/javascript" src="https://www.google.com/jsapi"></script>' . 
             '<script type="text/javascript">google.load("visualization", "1", {packages:["corechart"]});</script>';
    }
    
    /**
     * Admin dashboard
     */
    public function showAdminDashboard() {
        $yandexLogoUrl = plugins_url('/../yandex-logo.png', __FILE__);
        print "<p><a href='http://company.yandex.ru/about/main/' target='_blank'>" . 
              "<img src='{$yandexLogoUrl}' border='0' style='max-width: 120px;'/></a></p>";
        if ($this->_websiteIdNotSet) {
            print "<div id='wrap'><p>" . 
                  __('Your website is not verified in Yandex webmaster service or not set in settings', 'wm_ya') .
                  "</p></div>";
            return;
        }
        print "<div id='wmYaDashboardChart'></div>";
        wp_register_script('webmaster_yandex_script', plugins_url('/../main.js', __FILE__), array('jquery'));
        wp_enqueue_script('webmaster_yandex_script');
        wp_enqueue_script('jquery');
        print '<script type="text/javascript">jQuery(window).load(function() {jQuery(Main.adminDashboardDrawChart())} )</script>';
    }
    
    public function getDataForDashboard() {
        if ($this->_websiteIdNotSet) {
            print json_encode(array('error' => 1, 'errorText' => 'Website is not set'));
            die;
        }
        $robotCrawledPages = $this->getRobotStatsHistory('crawled-urls');
        $incomingLinks = $this->getRobotStatsHistory('incoming-links');
        $indexedUrls = $this->getRobotStatsHistory('indexed-urls');
        $excludedUrls = $this->getRobotStatsHistory('excluded-urls');
        
        $finalResult = array('crawled' => $robotCrawledPages, 'incoming' => $incomingLinks,
                             'indexed' => $indexedUrls, 'excluded' => $excludedUrls ,'error' => 0);
        print json_encode($finalResult);
        die;
        
    }

    public function getPage($curlOptions = array()) {
        $ch = curl_init();
        curl_setopt_array($ch, $curlOptions);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        return array('result' => $result, 'info' => $info);
    }
    
    /**
     * Get Token from Yandex
     * 
     * @param string $code
     * @return mixed
     */
    public function getYandexTokenByCode($code) {
        $url = 'https://oauth.yandex.ru/token';
        $postData = "grant_type=authorization_code&code={$code}&client_id=" . $this->getAppId() .
                    "&client_secret=" . $this->getAppPassword();
        $headers = array(
            'POST /token HTTP/1.1',
            'Host: oauth.yandex.ru',
            'Content-type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen($postData),
        );
        $curlOptions = array(
            CURLOPT_POST            => 1,
            CURLOPT_HEADER          => 0,
            CURLOPT_URL             => $url,
            CURLOPT_CONNECTTIMEOUT  => 1,
            CURLOPT_FRESH_CONNECT   => 1,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_FORBID_REUSE    => 1,
            CURLOPT_TIMEOUT         => self::YANDEX_API_REQUEST_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_POSTFIELDS      => $postData,
            CURLOPT_HTTPHEADER      => $headers
        );
        $response = $this->getPage($curlOptions);
        if ($response['info']['http_code'] == 200) {
            return json_decode($response['result']);
        }
        return null;
    }
    
    public function performYandexWebmasterApiRequest($url, $requestType = 'GET',
                                                     $curlOptions = array(),
                                                     $additionalHeaders = array()) {
        $headers = array(
            "{$requestType} {$url} HTTP/1.1",
            'Host: webmaster.yandex.ru',
            'Authorization: OAuth ' . $this->getYandexToken()
        );

        $headers = array_merge($headers, $additionalHeaders);
        $requestOptions = array(
            CURLOPT_URL             => 'https://' . self::YANDEX_WEBMASTER_HOST . $url,
            CURLOPT_SSL_VERIFYPEER  => 0,
            CURLOPT_CONNECTTIMEOUT  => self::YANDEX_API_REQUEST_TIMEOUT,
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_RETURNTRANSFER  => 1
        );
        if (count($curlOptions)) {
            foreach ($curlOptions as $curlOption => $curlOptionValue) {
                $requestOptions[$curlOption] = $curlOptionValue;
            }
        }
        $response = $this->getPage($requestOptions);
        return $response;
    }

    /**
     * Get list of all websites added to Yandex Webmaster
     * 
     * @return mixed
     */
    public function getWebmasterWebsites() {
        $url = '/api/v2/hosts';
        $response = $this->performYandexWebmasterApiRequest($url);
        $result = array();
        if ($response['info']['http_code'] == 200 and strlen($response['result']) > 0) {
            $dom = new DOMDocument();
            if ($dom->loadXML($response['result'])) {
                foreach ($dom->getElementsByTagName('host') as $host) {
                    $hostHref = $host->getAttribute('href');
                    $hostHrefArr = explode('/', $hostHref);
                    $websiteId = array_pop($hostHrefArr);
                    $name = $host->getElementsByTagName('name')->item(0)->nodeValue;
                    $state = $host->getElementsByTagName('verification')->item(0)->getAttribute('state');
                    $result[] = array('name'=> $name, 'state' => $state, 'website_id' => $websiteId);
                }
            }
        }
        return $result;
    }
    
    public function sendTextToYandex($text) {
        $url = "/api/v2/hosts/" . $this->getWebsiteId() . "/original-texts/";
        $text = urlencode($text);
        $text = "<original-text><content>{$text}</content></original-text>";
        $additionalHeaders = array('Content-Length: ' . strlen($text));
        $curlOptions = array(CURLOPT_CONNECTTIMEOUT => 30, CURLOPT_POSTFIELDS => $text);
        $response = $this->performYandexWebmasterApiRequest($url, 'POST', $curlOptions, $additionalHeaders);
        $result = array();
        if ($response['info']['http_code'] == 403) {
            $dom = new DOMDocument();
            $yandexError = 'unknown';
            if ($dom->loadXML($response['result'])) {
                $yandexError = $dom->getElementsByTagName('message')->item(0)->nodeValue;
            }
            $result = array('error' => true, 'yandexError' => $yandexError);
            return $result;
        }
        if ($response['info']['http_code'] == 201) {
            $dom = new DOMDocument();
            $yandexLink = 'unknown';
            $yandexId = 'unknown';
            if ($dom->loadXML($response['result'])) {
                $yandexId = $dom->getElementsByTagName('id')->item(0)->nodeValue;
                $yandexLink = $dom->getElementsByTagName('link')->item(0)->getAttribute('href');
            }
            $result = array('error' => false, 'yandexId' => $yandexId, 'yandexLink' => $yandexLink);
            return $result;
        }
        return array('error' => true, 'yandexError' => 'unknown');
    }
    
    public function getRobotStatsHistory($type = 'crawled-urls') {
        $availableTypes = array('tic', 'crawled-urls', 'incoming-links', 'indexed-urls', 'excluded-urls');
        if (!in_array($type, $availableTypes)) {
            $type = 'crawled-urls';
        }
        $url = "/api/v2/hosts/" . $this->getWebsiteId() . "/history/{$type}/";
        $response = $this->performYandexWebmasterApiRequest($url);
        $result = array();
        if ($response['info']['http_code'] == 200 and strlen($response['result']) > 0) {
            $dom = new DOMDocument();
            if ($dom->loadXML($response['result'])) {
                foreach ($dom->getElementsByTagName('value') as $value) {
                    $date = $value->getAttribute('date');
                    $num = $value->nodeValue;
                    $result[] = array('date'=> $date, 'num' => $num);
                }
            }
        }
        return $result;
    }
}