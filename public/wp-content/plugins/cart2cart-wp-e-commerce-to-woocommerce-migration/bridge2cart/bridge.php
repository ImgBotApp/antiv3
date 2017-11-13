<?php
/*------------------------------------------------------------------------------+
| MagneticOne                                                                   |
| Copyright (c) 2013 MagneticOne.com <contact@magneticone.com>                  |
| All rights reserved                                                           |
+-------------------------------------------------------------------------------+
| PLEASE READ  THE FULL TEXT OF SOFTWARE LICENSE AGREEMENT IN THE "license.txt" |
| FILE PROVIDED WITH THIS DISTRIBUTION. THE AGREEMENT TEXT IS ALSO AVAILABLE AT |
| THE FOLLOWING URL: http://www.shopping-cart-migration.com/license-agreement   |
|                                                                               |
| THIS  AGREEMENT  EXPRESSES  THE  TERMS  AND CONDITIONS  ON WHICH YOU MAY USE  |
| THIS SOFTWARE   PROGRAM   AND  ASSOCIATED  DOCUMENTATION   THAT  MAGNETICONE  |
| (hereinafter  referred to as "THE AUTHOR") IS FURNISHING  OR MAKING AVAILABLE |
| AVAILABLE  TO YOU WITH  THIS  AGREEMENT  (COLLECTIVELY,  THE  "SOFTWARE").    |
| PLEASE   REVIEW   THE  TERMS   AND   CONDITIONS  OF  THIS  LICENSE AGREEMENT  |
| CAREFULLY   BEFORE   INSTALLING   OR   USING  THE  SOFTWARE.  BY INSTALLING,  |
| COPYING   OR   OTHERWISE   USING   THE   SOFTWARE,   YOU  AND  YOUR  COMPANY  |
| (COLLECTIVELY,  "YOU")  ARE  ACCEPTING  AND  AGREEING  TO  THE TERMS OF THIS  |
| LICENSE   AGREEMENT.   IF  YOU    ARE  NOT  WILLING   TO  BE  BOUND  BY THIS  |
| AGREEMENT, DO  NOT  INSTALL OR USE THE SOFTWARE.  VARIOUS   COPYRIGHTS   AND  |
| OTHER   INTELLECTUAL   PROPERTY   RIGHTS    PROTECT   THE   SOFTWARE.   THIS  |
| AGREEMENT IS A LICENSE AGREEMENT THAT GIVES  YOU  LIMITED  RIGHTS   TO   USE  |
| THE  SOFTWARE   AND   NOT  AN  AGREEMENT  FOR SALE OR FOR  TRANSFER OF TITLE. |
| THE AUTHOR RETAINS ALL RIGHTS NOT EXPRESSLY GRANTED BY THIS AGREEMENT.        |
|                                                                               |
| The Developer of the Code is MagneticOne,                                     |
| Copyright (C) 2006 - 2013 All Rights Reserved.                                |
+-------------------------------------------------------------------------------+
|                                                                               |
|                            ATTENTION!                                         |
+-------------------------------------------------------------------------------+
| By our Terms of Use you agreed not to change, modify, add, or remove portions |
| of Bridge Script source code as it is owned by MagneticOne company.           |
| You agreed not to use, reproduce, modify, adapt, publish, translate           |
| the Bridge Script source code into any form, medium, or technology            |
| now known or later developed throughout the universe.                         |
|                                                                               |
| Full text of our TOS located at                                               |
|                     http://www.shopping-cart-migration.com/terms-of-service   |
+------------------------------------------------------------------------------*/


class M1_Bridge
{
  var $_link = null; //mysql connection link
  var $_res = null; // mysql query result
  var $_tblPrefix = ''; // table prefix
  var $config = null; // config adapter

  /**
   * Bridge constructor
   *
   * @param M1_Config_Adapter $config
   * @return M1_Bridge
   */
  function M1_Bridge($config)
  {
    $this->config = $config;

    if ($this->getAction() != 'savefile' && $this->getAction() != 'update') {
      $this->_link = $this->config->connect();
    }
  }

  function getTablesPrefix()
  {
    return $this->_tblPrefix;
  }

  function getLink()
  {
    return $this->_link;
  }

  function query($sql, $fetchMode)
  {
    $result = array(
      'result'  => null,
      'message' => '',
    );

    if (!$this->_link) {
      $result['message'] = '[ERROR] MySQL Query Error: Can not connect to DB';
      return $result;
    }

    if (isset($_GET['disable_checks'])) {
      mysql_query('SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0', $this->_link);
      mysql_query("SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO'", $this->_link);
    }

    if (isset($_REQUEST['set_names'])) {
      @mysql_query("SET NAMES " . @mysql_real_escape_string($_REQUEST['set_names']), $this->_link);
      @mysql_query("SET CHARACTER SET " . @mysql_real_escape_string($_REQUEST['set_names']), $this->_link);
      @mysql_query("SET CHARACTER_SET_CONNECTION=" . @mysql_real_escape_string($_REQUEST['set_names']), $this->_link);
    }

    $fetch_mode = MYSQL_ASSOC;
    switch ($fetchMode) {
      case 3:
        $fetch_mode = MYSQL_BOTH;
        break;

      case 2:
        $fetch_mode = MYSQL_NUM;
        break;

      case 1:
        $fetch_mode = MYSQL_ASSOC;
        break;

      default:
        break;
    }

    $this->_res = mysql_query($sql, $this->_link);

    $triesCount = 10;
    while (mysql_errno($this->_link) == 2013) {
      if (!$triesCount--) {
        break;
      }
      // reconnect
      $this->_link = $this->config->connect();
      if ($this->_link) {

        if (isset($_REQUEST['set_names'])) {
          @mysql_query("SET NAMES " . @mysql_real_escape_string($_REQUEST['set_names']), $this->_link);
          @mysql_query("SET CHARACTER SET " . @mysql_real_escape_string($_REQUEST['set_names']), $this->_link);
          @mysql_query("SET CHARACTER_SET_CONNECTION=" . @mysql_real_escape_string($_REQUEST['set_names']), $this->_link);
        }

        // execute query once again
        $this->_res = mysql_query($sql, $this->_link);
      }
    }

    if (($errno = mysql_errno($this->_link)) != 0) {
      $result['message'] = '[ERROR] Mysql Query Error: ' . $errno . ', ' . mysql_error($this->_link);
      return $result;
    }

    if (!is_resource($this->_res)) {
      $result['result'] = $this->_res;
      return $result;
    }

    $fetchedFields = array();
    while (($field = mysql_fetch_field($this->_res)) !== false) {
      $fetchedFields[] = $field;
    }

    $rows = array();
    while (($row = mysql_fetch_array($this->_res, $fetch_mode)) !== false) {
      $rows[] = $row;
    }

    mysql_free_result($this->_res);

    if (isset($_GET['disable_checks'])) {
      mysql_query("SET SQL_MODE=IFNULL(@OLD_SQL_MODE,'')", $this->_link);
      mysql_query("SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS,0)", $this->_link);
    }

    $result['result']        = $rows;
    $result['fetchedFields'] = $fetchedFields;

    return $result;
  }

  function getAction()
  {
    if (isset($_GET['action'])) {
      return str_replace('.', '', $_GET['action']);
    } else {
      return '';
    }
  }

  function run()
  {
    $action = $this->getAction();

    if ($action != 'update') {
      $this->_selfTest();
    }

    if ($action == 'checkbridge') {
      echo 'BRIDGE_OK';
      return;
    }

    if ($action == 'update') {
      $this->_checkPossibilityUpdate();
    }

    $class_name = 'M1_Bridge_Action_' . ucfirst($action);

    if (!class_exists($class_name)) {
      echo 'ACTION_DO_NOT EXIST' . PHP_EOL;
      die;
    }

    $action_obj = new $class_name();
    @$action_obj->cartType = @$this->config->cartType;
    $action_obj->perform($this);
    $this->_destroy();
  }

  function isWritable($dir)
  {
    if (!@is_dir($dir)) {
      return false;
    }

    $dh = @opendir($dir);

    if ($dh === false) {
      return false;
    }

    while (($entry = readdir($dh)) !== false) {
      if ($entry == '.' || $entry == '..' || !@is_dir($dir . DIRECTORY_SEPARATOR . $entry)) {
        continue;
      }

      if (!$this->isWritable($dir . DIRECTORY_SEPARATOR . $entry)) {
        return false;
      }
    }

    if (!is_writable($dir)) {
      return false;
    }

    return true;
  }

  function _destroy()
  {
    if ($this->getAction() != 'savefile') {
      mysql_close($this->_link);
    }
  }

  function _checkPossibilityUpdate()
  {
    if (!is_writable(M1_STORE_BASE_DIR . '/' . M1_BRIDGE_DIRECTORY_NAME . '/')) {
      die('ERROR_TRIED_TO_PERMISSION_CART2CART' . M1_STORE_BASE_DIR . '/' . M1_BRIDGE_DIRECTORY_NAME . '/');
    }

    if (!is_writable(M1_STORE_BASE_DIR . '/' . M1_BRIDGE_DIRECTORY_NAME . '/bridge.php')) {
      die('ERROR_TRIED_TO_PERMISSION_BRIDGE_FILE' . M1_STORE_BASE_DIR . '/' . M1_BRIDGE_DIRECTORY_NAME . '/bridge.php');
    }
  }

  function _selfTest()
  {
    if (!isset($_GET['ver']) && !isset($_GET['token'])) {
      die('BRIDGE_INSTALLED');
    }

    if (!isset($_GET['ver']) || (isset($_GET['ver']) && $_GET['ver'] != M1_BRIDGE_VERSION)) {
      die('ERROR_BRIDGE_VERSION_NOT_SUPPORTED');
    }

    if (isset($_GET['token']) && $_GET['token'] == M1_TOKEN) {
      // good :)
    } else {
      die('ERROR_INVALID_TOKEN');
    }

    if ((!isset($_GET['storetype']) || $_GET['storetype'] == 'target') && $this->getAction() == 'checkbridge') {

      if (trim($this->config->imagesDir) != '') {
        if (!file_exists(M1_STORE_BASE_DIR . $this->config->imagesDir) && is_writable(M1_STORE_BASE_DIR)) {
          if (!@mkdir(M1_STORE_BASE_DIR . $this->config->imagesDir, 0777, true)) {
            die('ERROR_TRIED_TO_CREATE_IMAGE_DIR' . M1_STORE_BASE_DIR . $this->config->imagesDir);
          }
        }

        if (!$this->isWritable(M1_STORE_BASE_DIR . $this->config->imagesDir)) {
          die('ERROR_NO_IMAGES_DIR ' . M1_STORE_BASE_DIR . $this->config->imagesDir);
        }
      }

      if (trim($this->config->categoriesImagesDir) != '') {
        if (!file_exists(M1_STORE_BASE_DIR . $this->config->categoriesImagesDir) && is_writable(M1_STORE_BASE_DIR)) {
          if (!@mkdir(M1_STORE_BASE_DIR . $this->config->categoriesImagesDir, 0777, true)) {
            die('ERROR_TRIED_TO_CREATE_IMAGE_DIR' . M1_STORE_BASE_DIR . $this->config->categoriesImagesDir);
          }
        }

        if (!$this->isWritable(M1_STORE_BASE_DIR . $this->config->categoriesImagesDir)) {
          die('ERROR_NO_IMAGES_DIR ' . M1_STORE_BASE_DIR . $this->config->categoriesImagesDir);
        }
      }

      if (trim($this->config->productsImagesDir) != '') {
        if (!file_exists(M1_STORE_BASE_DIR . $this->config->productsImagesDir) && is_writable(M1_STORE_BASE_DIR)) {
          if (!@mkdir(M1_STORE_BASE_DIR . $this->config->productsImagesDir, 0777, true)) {
            die('ERROR_TRIED_TO_CREATE_IMAGE_DIR' . M1_STORE_BASE_DIR . $this->config->productsImagesDir);
          }
        }

        if (!$this->isWritable(M1_STORE_BASE_DIR . $this->config->productsImagesDir)) {
          die('ERROR_NO_IMAGES_DIR ' . M1_STORE_BASE_DIR . $this->config->productsImagesDir);
        }
      }

      if (trim($this->config->manufacturersImagesDir) != '') {
        if (!file_exists(M1_STORE_BASE_DIR . $this->config->manufacturersImagesDir) && is_writable(M1_STORE_BASE_DIR)) {
          if (!@mkdir(M1_STORE_BASE_DIR . $this->config->manufacturersImagesDir, 0777, true)) {
            die('ERROR_TRIED_TO_CREATE_IMAGE_DIR' . M1_STORE_BASE_DIR . $this->config->manufacturersImagesDir);
          }
        }

        if (!$this->isWritable(M1_STORE_BASE_DIR . $this->config->manufacturersImagesDir)) {
          die('ERROR_NO_IMAGES_DIR ' . M1_STORE_BASE_DIR . $this->config->manufacturersImagesDir);
        }
      }
    }
  }
}


class M1_Config_Adapter_Oxid extends M1_Config_Adapter
{
  function M1_Config_Adapter_Oxid()
  {
    //@include_once M1_STORE_BASE_DIR . "config.inc.php";
    $config = file_get_contents(M1_STORE_BASE_DIR . 'config.inc.php');
    preg_match("/dbName(.+)?=(.+)?\'(.+)\';/", $config, $match);
    $this->Dbname = $match[3];
    preg_match("/dbUser(.+)?=(.+)?\'(.+)\';/", $config, $match);
    $this->Username = $match[3];
    preg_match("/dbPwd(.+)?=(.+)?\'(.+)\';/", $config, $match);
    $this->Password = isset($match[3])?$match[3]:'';
    preg_match("/dbHost(.+)?=(.+)?\'(.*)\';/", $config, $match);
    $this->setHostPort($match[3]);

    //check about last slash
    $this->imagesDir = 'out/pictures/';
    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;

    //add key for decoding config values in oxid db
    //check slash
    $key_config_file = file_get_contents(M1_STORE_BASE_DIR . '/core/oxconfk.php');
    preg_match("/sConfigKey(.+)?=(.+)?\"(.+)?\";/", $key_config_file, $match);
    $this->cartVars['sConfigKey'] = $match[3];
    $version = $this->getCartVersionFromDb('OXVERSION', 'oxshops', 'OXACTIVE=1 LIMIT 1');
    if ($version != '') {
      $this->cartVars['dbVersion'] = $version;
    } 
  }
}



class M1_Config_Adapter_Virtuemart113 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Virtuemart113()
  {
    require_once M1_STORE_BASE_DIR . '/configuration.php';

    if (class_exists('JConfig')) {

      $jconfig = new JConfig();

      $this->setHostPort($jconfig->host);
      $this->Dbname   = $jconfig->db;
      $this->Username = $jconfig->user;
      $this->Password = $jconfig->password;

    } else {

      $this->setHostPort($mosConfig_host);
      $this->Dbname   = $mosConfig_db;
      $this->Username = $mosConfig_user;
      $this->Password = $mosConfig_password;
    }

    if (file_exists(M1_STORE_BASE_DIR . '/administrator/components/com_virtuemart/version.php')) {
      $ver = file_get_contents(M1_STORE_BASE_DIR . '/administrator/components/com_virtuemart/version.php');
      if (preg_match('/\$RELEASE.+\'(.+)\'/', $ver, $match) != 0) {
        $this->cartVars['dbVersion'] = $match[1];
        unset($match);
      }
    }

    $this->imagesDir = 'components/com_virtuemart/shop_image/';
    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;

    if (is_dir( M1_STORE_BASE_DIR . 'images/stories/virtuemart/product')) {
      $this->imagesDir = 'images/stories/virtuemart/';
      $this->productsImagesDir      = $this->imagesDir . 'product/';
      $this->categoriesImagesDir    = $this->imagesDir . 'category/';
      $this->manufacturersImagesDir  = $this->imagesDir . 'manufacturer/';
    }
  }
}


class M1_Config_Adapter_Litecommerce extends M1_Config_Adapter
{
  function M1_Config_Adapter_Litecommerce()
  {
    if ((file_exists(M1_STORE_BASE_DIR .'/etc/config.php'))){
      $file = M1_STORE_BASE_DIR .'/etc/config.php';
      $this->imagesDir = '/images';
      $this->categoriesImagesDir    = $this->imagesDir . '/category';
      $this->productsImagesDir      = $this->imagesDir . '/product';
      $this->manufacturersImagesDir = $this->imagesDir;
    } elseif (file_exists(M1_STORE_BASE_DIR .'/modules/lc_connector/litecommerce/etc/config.php')) {
      $file = M1_STORE_BASE_DIR .'/modules/lc_connector/litecommerce/etc/config.php';
      $this->imagesDir = '/modules/lc_connector/litecommerce/images';
      $this->categoriesImagesDir    = $this->imagesDir . '/category';
      $this->productsImagesDir      = $this->imagesDir . '/product';
      $this->manufacturersImagesDir = $this->imagesDir;
    }

    $settings = parse_ini_file($file);
    $this->Host      = $settings['hostspec'];
    $this->setHostPort($settings['hostspec']);
    $this->Username  = $settings['username'];
    $this->Password  = $settings['password'];
    $this->Dbname    = $settings['database'];
    $this->TblPrefix = $settings['table_prefix'];

    $version = $this->getCartVersionFromDb('value', 'config', "name = 'version'");
    if ($version != '') {
      $this->cartVars['dbVersion'] = $version;
    }
  }
}



class M1_Config_Adapter_Oscommerce3 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Oscommerce3()
  {
    $file = M1_STORE_BASE_DIR . '/osCommerce/OM/Config/settings.ini';
    $settings=parse_ini_file($file);
    $this->imagesDir = '/public/';
    $this->categoriesImagesDir    = $this->imagesDir . '/categories';
    $this->productsImagesDir      = $this->imagesDir . '/products';
    $this->manufacturersImagesDir = $this->imagesDir;

    $this->Host      = $settings['db_server'];
    $this->setHostPort($settings['db_server_port']);
    $this->Username  = $settings['db_server_username'];
    $this->Password  = $settings['db_server_password'];
    $this->Dbname    = $settings['db_database'];
  }
}


/**
 * Created by JetBrains PhpStorm.
 * User: nazar.k
 * Date: 27.07.13
 * Time: 17:27
 * To change this template use File | Settings | File Templates.
 */

class M1_Config_Adapter_Loaded7 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Loaded7()
  {
    include_once( M1_STORE_BASE_DIR . '/includes/config.php');

    $this->setHostPort(DB_SERVER);
    $this->Username = DB_SERVER_USERNAME;
    $this->Password = DB_SERVER_PASSWORD;
    $this->Dbname   = DB_DATABASE;

    $this->imagesDir              = '/' . DIR_WS_IMAGES;
    $this->categoriesImagesDir    = $this->imagesDir . 'categories/';
    $this->productsImagesDir      = $this->imagesDir . 'products/';
    $this->manufacturersImagesDir = $this->imagesDir . 'manufacturers/';
  }
}

class M1_Config_Adapter_MijoShop extends M1_Config_Adapter
{
  function M1_Config_Adapter_MijoShop()
  {
    require_once M1_STORE_BASE_DIR . '/configuration.php';

    if (class_exists('JConfig')) {

      $jconfig = new JConfig();

      $this->setHostPort($jconfig->host);
      $this->Dbname   = $jconfig->db;
      $this->Username = $jconfig->user;
      $this->Password = $jconfig->password;

    } else {

      $this->setHostPort($mosConfig_host);
      $this->Dbname   = $mosConfig_db;
      $this->Username = $mosConfig_user;
      $this->Password = $mosConfig_password;
    }

    $this->imagesDir = 'components/com_mijoshop/opencart/image/';
    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;
  }
}


class M1_Config_Adapter_Shopscriptfree extends M1_Config_Adapter
{
  function M1_Config_Adapter_Shopscriptfree()
  {
    $config = file_get_contents(M1_STORE_BASE_DIR . 'cfg/connect.inc.php');
    preg_match("/define\(\'DB_NAME\', \'(.+)\'\);/", $config, $match);
    $this->Dbname = $match[1];
    preg_match("/define\(\'DB_USER\', \'(.+)\'\);/", $config, $match);
    $this->Username = $match[1];
    preg_match("/define\(\'DB_PASS\', \'(.*)\'\);/", $config, $match);
    $this->Password = $match[1];
    preg_match("/define\(\'DB_HOST\', \'(.+)\'\);/", $config, $match);
    $this->setHostPort( $match[1] );

    $this->imagesDir = 'products_pictures/';
    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;

    $generalInc = file_get_contents(M1_STORE_BASE_DIR . 'cfg/general.inc.php');

    preg_match("/define\(\'CONF_CURRENCY_ISO3\', \'(.+)\'\);/", $generalInc, $match);
    if (count($match) != 0) {
      $this->cartVars['iso3Currency'] = $match[1];
    }

    preg_match("/define\(\'CONF_CURRENCY_ID_LEFT\', \'(.+)\'\);/", $generalInc, $match);
    if (count($match) != 0) {
      $this->cartVars['currencySymbolLeft'] = $match[1];
    }

    preg_match("/define\(\'CONF_CURRENCY_ID_RIGHT\', \'(.+)\'\);/", $generalInc, $match);
    if (count($match) != 0) {
      $this->cartVars['currencySymbolRight'] = $match[1];
    }
  }
}

class M1_Config_Adapter_XtcommerceVeyton extends M1_Config_Adapter
{
  function M1_Config_Adapter_XtcommerceVeyton()
  {
    define('_VALID_CALL', 'TRUE');
    define('_SRV_WEBROOT', 'TRUE');
    require_once M1_STORE_BASE_DIR . 'conf' . DIRECTORY_SEPARATOR . 'config.php';
    require_once M1_STORE_BASE_DIR . 'conf' . DIRECTORY_SEPARATOR . 'paths.php';

    $this->setHostPort(_SYSTEM_DATABASE_HOST);
    $this->Dbname = _SYSTEM_DATABASE_DATABASE;
    $this->Username = _SYSTEM_DATABASE_USER;
    $this->Password = _SYSTEM_DATABASE_PWD;
    $this->imagesDir = _SRV_WEB_IMAGES;
    $this->TblPrefix = DB_PREFIX . '_';

    $version = $this->getCartVersionFromDb('config_value', 'config', "config_key = '_SYSTEM_VERSION'");
    if ($version != '') {
      $this->cartVars['dbVersion'] = $version;
    }

    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;
  }
}


class M1_Config_Adapter_XCart extends M1_Config_Adapter
{
  function M1_Config_Adapter_XCart()
  {
    define('XCART_START', 1);

    $config = file_get_contents(M1_STORE_BASE_DIR . 'config.php');

    preg_match('/\$sql_host.+\'(.+)\';/', $config, $match);
    $this->setHostPort($match[1]);
    preg_match('/\$sql_user.+\'(.+)\';/', $config, $match);
    $this->Username = $match[1];
    preg_match('/\$sql_db.+\'(.+)\';/', $config, $match);
    $this->Dbname = $match[1];
    preg_match('/\$sql_password.+\'(.*)\';/', $config, $match);
    $this->Password = $match[1];

    $this->imagesDir = 'images/'; // xcart starting from 4.1.x hardcodes images location
    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;

    if (file_exists(M1_STORE_BASE_DIR . 'VERSION')) {
      $version = file_get_contents(M1_STORE_BASE_DIR . 'VERSION');
      $this->cartVars['dbVersion'] = preg_replace('/(Version| |\\n)/', '', $version);
    }
  }
}

class M1_Config_Adapter_Hhgmultistore extends M1_Config_Adapter
{
  function M1_Config_Adapter_Hhgmultistore()
  {
    define('SITE_PATH', '');
    define('WEB_PATH', '');
    require_once M1_STORE_BASE_DIR . 'core/config/configure.php';
    require_once M1_STORE_BASE_DIR . 'core/config/paths.php';

    $baseDir = '/store_files/1/';
    $this->imagesDir = $baseDir . DIR_WS_IMAGES;

    $this->categoriesImagesDir = $baseDir . DIR_WS_CATEGORIE_IMAGES;
    $this->productsImagesDirs['info'] = $baseDir . DIR_WS_PRODUCT_INFO_IMAGES;
    $this->productsImagesDirs['org'] = $baseDir . DIR_WS_PRODUCT_ORG_IMAGES;
    $this->productsImagesDirs['thumb'] = $baseDir . DIR_WS_PRODUCT_THUMBNAIL_IMAGES;
    $this->productsImagesDirs['popup'] = $baseDir . DIR_WS_PRODUCT_POPUP_IMAGES;

    $this->manufacturersImagesDirs['img'] = $baseDir . DIR_WS_MANUFACTURERS_IMAGES;
    $this->manufacturersImagesDirs['org'] = $baseDir . DIR_WS_MANUFACTURERS_ORG_IMAGES;

    $this->Host     = DB_SERVER;
    $this->Username = DB_SERVER_USERNAME;
    $this->Password = DB_SERVER_PASSWORD;
    $this->Dbname   = DB_DATABASE;

    if (file_exists(M1_STORE_BASE_DIR . '/core/config/conf.hhg_startup.php')) {
      $ver = file_get_contents(M1_STORE_BASE_DIR . '/core/config/conf.hhg_startup.php');
      if (preg_match('/PROJECT_VERSION.+\((.+)\)\'\)/', $ver, $match) != 0) {
        $this->cartVars['dbVersion'] = $match[1];
        unset($match);
      }
    }
  }
}


class M1_Config_Adapter_Squirrelcart242 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Squirrelcart242()
  {
    include_once(M1_STORE_BASE_DIR . 'squirrelcart/config.php');

    $this->setHostPort($sql_host);
    $this->Dbname      = $db;
    $this->Username    = $sql_username;
    $this->Password    = $sql_password;

    $this->imagesDir                 = $img_path;
    $this->categoriesImagesDir       = $img_path . '/categories';
    $this->productsImagesDir         = $img_path . '/products';
    $this->manufacturersImagesDir    = $img_path;

    $version = $this->getCartVersionFromDb('DB_Version', 'Store_Information', 'record_number = 1');
    if ($version != '') {
      $this->cartVars['dbVersion'] = $version;
    }
  }
}

class M1_Config_Adapter_Zencart137 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Zencart137()
  {
    $cur_dir = getcwd();

    chdir(M1_STORE_BASE_DIR);

    @require_once M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php';

    chdir($cur_dir);

    $this->imagesDir = DIR_WS_IMAGES;

    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;

    if (defined('DIR_WS_PRODUCT_IMAGES')) {
      $this->productsImagesDir = DIR_WS_PRODUCT_IMAGES;
    }

    if (defined('DIR_WS_ORIGINAL_IMAGES')) {
      $this->productsImagesDir = DIR_WS_ORIGINAL_IMAGES;
    }

    $this->manufacturersImagesDir = $this->imagesDir;

    $this->setHostPort(DB_SERVER);
    $this->Username  = DB_SERVER_USERNAME;
    $this->Password  = DB_SERVER_PASSWORD;
    $this->Dbname    = DB_DATABASE;

    if (file_exists(M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'version.php')) {
       @require_once M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'version.php';
      $major = PROJECT_VERSION_MAJOR;
      $minor = PROJECT_VERSION_MINOR;
      if (defined('EXPECTED_DATABASE_VERSION_MAJOR') && EXPECTED_DATABASE_VERSION_MAJOR != '') {
        $major = EXPECTED_DATABASE_VERSION_MAJOR;
      }

      if (defined('EXPECTED_DATABASE_VERSION_MINOR') && EXPECTED_DATABASE_VERSION_MINOR != '') {
        $minor = EXPECTED_DATABASE_VERSION_MINOR;
      }

      if ($major != '' && $minor != '') {
        $this->cartVars['dbVersion'] = $major . '.' . $minor;
      }
    }
  }
}



class M1_Config_Adapter_Ubercart3 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Ubercart3()
  {
    @include_once M1_STORE_BASE_DIR . 'sites/default/settings.php';

    $url = $databases['default']['default']; 

    $url['username'] = urldecode($url['username']);
    $url['password'] = isset($url['password']) ? urldecode($url['password']) : '';
    $url['host'] = urldecode($url['host']);
    $url['database'] = urldecode($url['database']);
    if (isset($url['port'])) {
      $url['host'] = $url['host'] . ':' . $url['port'];
    }

    $this->setHostPort($url['host']);
    $this->Dbname   = ltrim($url['database'], '/');
    $this->Username = $url['username'];
    $this->Password = $url['password'];

    $this->imagesDir = '/sites/default/files/';
    if (!file_exists(M1_STORE_BASE_DIR . $this->imagesDir)) {
      $this->imagesDir = '/files';
    }

    $fileInfo = M1_STORE_BASE_DIR . '/sites/all/modules/ubercart/uc_cart/uc_cart.info';
    if (file_exists($fileInfo)) {
      $str = file_get_contents($fileInfo);
      if (preg_match('/version\s+=\s+".+-(.+)"/', $str, $match) != 0) {
        $this->cartVars['dbVersion'] = $match[1];
        unset($match);
      }
    }

    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;
  }
}


class M1_Config_Adapter_Interspire extends M1_Config_Adapter
{
  function M1_Config_Adapter_Interspire()
  {
    require_once M1_STORE_BASE_DIR . 'config/config.php';

    $this->setHostPort($GLOBALS['ISC_CFG']['dbServer']);
    $this->Username = $GLOBALS['ISC_CFG']['dbUser'];
    $this->Password = $GLOBALS['ISC_CFG']['dbPass'];
    $this->Dbname   = $GLOBALS['ISC_CFG']['dbDatabase'];

    $this->imagesDir = $GLOBALS['ISC_CFG']['ImageDirectory'];
    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;

    define('DEFAULT_LANGUAGE_ISO2',$GLOBALS['ISC_CFG']['Language']);

    $version = $this->getCartVersionFromDb('database_version', $GLOBALS['ISC_CFG']['tablePrefix'] . 'config', '1');
    if ($version != '') {
      $this->cartVars['dbVersion'] = $version;
    }
  }
}

class M1_Config_Adapter_Prestashop15 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Prestashop15()
  {
    $conf_file1 = file_get_contents(M1_STORE_BASE_DIR . '/config/settings.inc.php');
    $conf_file2 = file_get_contents(M1_STORE_BASE_DIR . '/config/config.inc.php');

    $files_lines = array_merge(explode("\n", $conf_file1), explode("\n", $conf_file2));

    $execute = '$currentDir = \'\';';

    foreach ($files_lines as $line) {
      if (preg_match("/^(\s*)define\(/i",$line)) {
        if ((strpos($line, '_DB_') !== false)
            || (strpos($line, '_PS_IMG_DIR_') !== false)
            || (strpos($line, '_PS_VERSION_') !== false)
        ) {
          $execute .= ' ' . $line;
        }
      }
    }

    define('_PS_ROOT_DIR_', M1_STORE_BASE_DIR);
    eval($execute);

    $this->setHostPort(_DB_SERVER_);
    $this->Dbname   = _DB_NAME_;
    $this->Username = _DB_USER_;
    $this->Password = _DB_PASSWD_;

    if (defined('_PS_IMG_DIR_') && defined('_PS_ROOT_DIR_')) {

      preg_match("/(\/\w+\/)$/i",_PS_IMG_DIR_,$m);
      $this->imagesDir = $m[1];

    } else {
      $this->imagesDir = '/img/';
    }

    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;

    if (defined('_PS_VERSION_')) {
      $this->cartVars['dbVersion'] = _PS_VERSION_;
    }
  }
}



class M1_Config_Adapter_Pinnacle361 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Pinnacle361()
  {
    include_once M1_STORE_BASE_DIR . 'content/engine/engine_config.php';

    $this->imagesDir = 'images/';
    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;

    //$this->Host = DB_HOST;
    $this->setHostPort(DB_HOST);
    $this->Dbname = DB_NAME;
    $this->Username = DB_USER;
    $this->Password = DB_PASSWORD;

    $version = $this->getCartVersionFromDb('value', (defined('DB_PREFIX') ? DB_PREFIX : '') . 'settings', "name = 'AppVer'");
    if ($version != '') {
      $this->cartVars['dbVersion'] = $version;
    }
  }
}


class M1_Config_Adapter_Cscart203 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Cscart203()
  {
    define('IN_CSCART', 1);
    define('CSCART_DIR', M1_STORE_BASE_DIR);
    define('AREA', 1);
    define('BOOTSTRAP', 1);
    define('DIR_ROOT', M1_STORE_BASE_DIR);
    define('DIR_CSCART', M1_STORE_BASE_DIR);
    define('DS', DIRECTORY_SEPARATOR);
    require_once M1_STORE_BASE_DIR . 'config.php';

    //For CS CART 1.3.x
    if (isset($db_host) && isset($db_name) && isset($db_user) && isset($db_password)) {
      $this->setHostPort($db_host);
      $this->Dbname = $db_name;
      $this->Username = $db_user;
      $this->Password = $db_password;
      $this->imagesDir = str_replace(M1_STORE_BASE_DIR, '', IMAGES_STORAGE_DIR);
    } else {
      $this->setHostPort($config['db_host']);
      $this->Dbname = $config['db_name'];
      $this->Username = $config['db_user'];
      $this->Password = $config['db_password'];

      if (defined('DIR_IMAGES')) {
        $imagesDir = DIR_IMAGES;
      } else {
        $imagesDir = $config['storage']['images']['dir'] . '/' . $config['storage']['images']['prefix'];
      }

      $this->imagesDir = str_replace(M1_STORE_BASE_DIR, '', $imagesDir);
    }

    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;

    if (defined('MAX_FILES_IN_DIR')) {
      $this->cartVars['cs_max_files_in_dir'] = MAX_FILES_IN_DIR;
    }

    if (defined('PRODUCT_VERSION')) {
      $this->cartVars['dbVersion'] = PRODUCT_VERSION;
    }
  }
}


class M1_Config_Adapter_Shopscript282 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Shopscript282()
  {
    $config = simplexml_load_file(M1_STORE_BASE_DIR . 'kernel/wbs.xml');

    $dbKey = (string)$config->FRONTEND['dbkey'];

    $config = simplexml_load_file(M1_STORE_BASE_DIR . 'dblist'. '/' . strtoupper($dbKey) . '.xml');

    $host = (string)$config->DBSETTINGS['SQLSERVER'];

    $this->setHostPort($host);
    $this->Dbname = (string)$config->DBSETTINGS['DB_NAME'];
    $this->Username = (string)$config->DBSETTINGS['DB_USER'];
    $this->Password = (string)$config->DBSETTINGS['DB_PASSWORD'];

    $this->imagesDir = 'published/publicdata/' . strtoupper($dbKey) . '/attachments/SC/products_pictures';
    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;

    if (isset($config->VERSIONS['SYSTEM'])) {
      $this->cartVars['dbVersion'] = (string)$config->VERSIONS['SYSTEM'];
    }
  }
}

class M1_Config_Adapter_WPecommerce extends M1_Config_Adapter
{
  function M1_Config_Adapter_WPecommerce()
  {
    //@include_once M1_STORE_BASE_DIR . "wp-config.php";
    $config = file_get_contents(M1_STORE_BASE_DIR . 'wp-config.php');
    preg_match("/define\(\'DB_NAME\', \'(.+)\'\);/", $config, $match);
    $this->Dbname   = $match[1];
    preg_match("/define\(\'DB_USER\', \'(.+)\'\);/", $config, $match);
    $this->Username = $match[1];
    preg_match("/define\(\'DB_PASSWORD\', \'(.*)\'\);/", $config, $match);
    $this->Password = $match[1];
    preg_match("/define\(\'DB_HOST\', \'(.+)\'\);/", $config, $match);
    $this->setHostPort( $match[1] );
    preg_match("/(table_prefix)(.*)(')(.*)(')(.*)/", $config, $match);
    $this->TblPrefix = $match[4];

    $version = $this->getCartVersionFromDb('option_value', 'options', "option_name = 'wpsc_version'");
    if ($version != '') {
      $this->cartVars['dbVersion'] = $version;
    } else {
      if (file_exists(M1_STORE_BASE_DIR . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wp-shopping-cart' . DIRECTORY_SEPARATOR . 'wp-shopping-cart.php')) {
        $conf = file_get_contents(M1_STORE_BASE_DIR . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wp-shopping-cart' . DIRECTORY_SEPARATOR . 'wp-shopping-cart.php');
        preg_match("/define\('WPSC_VERSION.*/", $conf, $match);
        if (isset($match[0]) && !empty($match[0])) {
          preg_match("/\d.*/", $match[0], $project);
          if (isset($project[0]) && !empty($project[0])) {
            $version = $project[0];
            $version = str_replace(array(' ','-','_',"'",');',')',';'), '', $version);
            if ($version != '') {
              $this->cartVars['dbVersion'] = strtolower($version);
            }
          }
        }
      }
    }

    if (file_exists(M1_STORE_BASE_DIR . 'wp-content/plugins/shopp/Shopp.php')
        || file_exists(M1_STORE_BASE_DIR . 'wp-content/plugins/wp-e-commerce/editor.php')
    ) {
      $this->imagesDir = 'wp-content/uploads/wpsc/';
      $this->categoriesImagesDir    = $this->imagesDir . 'category_images/';
      $this->productsImagesDir      = $this->imagesDir . 'product_images/';
      $this->manufacturersImagesDir = $this->imagesDir;
    } elseif (file_exists(M1_STORE_BASE_DIR . 'wp-content/plugins/wp-e-commerce/wp-shopping-cart.php')) {
      $this->imagesDir = 'wp-content/uploads/';
      $this->categoriesImagesDir    = $this->imagesDir . 'wpsc/category_images/';
      $this->productsImagesDir      = $this->imagesDir;
      $this->manufacturersImagesDir = $this->imagesDir;
    } else {
      $this->imagesDir = 'images/';
      $this->categoriesImagesDir    = $this->imagesDir;
      $this->productsImagesDir      = $this->imagesDir;
      $this->manufacturersImagesDir = $this->imagesDir;
    }
  }
}



class M1_Config_Adapter_Opencart14 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Opencart14()
  {
    include_once( M1_STORE_BASE_DIR . '/config.php');

    if (defined('DB_HOST')) {
      $this->setHostPort(DB_HOST);
    } else {
      $this->setHostPort(DB_HOSTNAME);
    }

    if (defined('DB_USER')) {
      $this->Username = DB_USER;
    } else {
      $this->Username = DB_USERNAME;
    }

    $this->Password = DB_PASSWORD;

    if (defined('DB_NAME')) {
      $this->Dbname   = DB_NAME;
    } else {
      $this->Dbname   = DB_DATABASE;
    }

    $indexFileContent = '';
    $startupFileContent = '';

    if (file_exists(M1_STORE_BASE_DIR . '/index.php')) {
      $indexFileContent = file_get_contents(M1_STORE_BASE_DIR . '/index.php');
    }

    if (file_exists(M1_STORE_BASE_DIR . '/system/startup.php')) {
      $startupFileContent = file_get_contents(M1_STORE_BASE_DIR . '/system/startup.php');
    }

    if (preg_match("/define\('\VERSION\'\, \'(.+)\'\)/", $indexFileContent, $match) == 0) {
      preg_match("/define\('\VERSION\'\, \'(.+)\'\)/", $startupFileContent, $match);
    }

    if (count($match) > 0) {
      $this->cartVars['dbVersion'] = $match[1];
      unset($match);
    }

    $this->imagesDir              = '/image/';
    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;
  }
}



class M1_Config_Adapter_AceShop extends M1_Config_Adapter
{
  function M1_Config_Adapter_AceShop()
  {
    require_once M1_STORE_BASE_DIR . '/configuration.php';

    if (class_exists('JConfig')) {

      $jconfig = new JConfig();

      $this->setHostPort($jconfig->host);
      $this->Dbname   = $jconfig->db;
      $this->Username = $jconfig->user;
      $this->Password = $jconfig->password;

    } else {

      $this->setHostPort($mosConfig_host);
      $this->Dbname   = $mosConfig_db;
      $this->Username = $mosConfig_user;
      $this->Password = $mosConfig_password;
    }

    $this->imagesDir = 'components/com_aceshop/opencart/image/';
    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;
  }
}


class M1_Config_Adapter_Oscommerce22ms2 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Oscommerce22ms2()
  {
    $cur_dir = getcwd();

    chdir(M1_STORE_BASE_DIR);

    @require_once M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php';

    chdir($cur_dir);

    $this->imagesDir = DIR_WS_IMAGES;

    $this->categoriesImagesDir = $this->imagesDir;
    $this->productsImagesDir   = $this->imagesDir;

    if (defined('DIR_WS_PRODUCT_IMAGES')) {
      $this->productsImagesDir = DIR_WS_PRODUCT_IMAGES;
    }

    if (defined('DIR_WS_ORIGINAL_IMAGES')) {
      $this->productsImagesDir = DIR_WS_ORIGINAL_IMAGES;
    }

    $this->manufacturersImagesDir = $this->imagesDir;

    $this->setHostPort(DB_SERVER);
    $this->Username  = DB_SERVER_USERNAME;
    $this->Password  = DB_SERVER_PASSWORD;
    $this->Dbname    = DB_DATABASE;
    chdir(M1_STORE_BASE_DIR);

    if (file_exists(M1_STORE_BASE_DIR  . 'includes' . DIRECTORY_SEPARATOR . 'application_top.php')) {
      $conf = file_get_contents(M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'application_top.php');
      preg_match("/define\('PROJECT_VERSION.*/", $conf, $match);
      if (isset($match[0]) && !empty($match[0])) {
        preg_match("/\d.*/", $match[0], $project);
        if (isset($project[0]) && !empty($project[0])) {
          $version = $project[0];
          $version = str_replace(array(' ','-','_',"'",');'), '', $version);
          if ($version != '') {
            $this->cartVars['dbVersion'] = strtolower($version);
          }
        }
      } else {
        //if another oscommerce based cart
        if (file_exists(M1_STORE_BASE_DIR  . 'includes' . DIRECTORY_SEPARATOR . 'version.php')) {
          @require_once M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'version.php';
          if (defined('PROJECT_VERSION') && PROJECT_VERSION != '') {
            $version = PROJECT_VERSION;
            preg_match("/\d.*/", $version, $vers);
            if (isset($vers[0]) && !empty($vers[0])) {
              $version = $vers[0];
              $version = str_replace(array(' ','-','_'), '', $version);
              if ($version != '') {
                $this->cartVars['dbVersion'] = strtolower($version);
              }
            }
            //if zen_cart
          } else {
            if (defined('PROJECT_VERSION_MAJOR') && PROJECT_VERSION_MAJOR != '') {
              $this->cartVars['dbVersion'] = PROJECT_VERSION_MAJOR;
            }

            if (defined('PROJECT_VERSION_MINOR') && PROJECT_VERSION_MINOR != '') {
              $this->cartVars['dbVersion'] .= '.' . PROJECT_VERSION_MINOR;
            }
          }
        }
      }
    }

    chdir($cur_dir);
  }
}



class M1_Config_Adapter_Prostores extends M1_Config_Adapter
{
  function M1_Config_Adapter_Prostores()
  {
    $config = file_get_contents(M1_STORE_BASE_DIR . 'ps_config.php');

    preg_match('/\$db_host.+\'(.+)\';/', $config, $matches);
    $this->setHostPort($matches[1]);

    preg_match('/\$db_user.+\'(.+)\';/', $config, $matches);
    $this->Username = $matches[1];

    preg_match('/\$db_name.+\'(.+)\';/', $config, $matches);
    $this->Dbname   = $matches[1];

    preg_match('/\$db_password.+\'(.*)\';/', $config, $matches);
    $this->Password = $matches[1];

    unset($matches);

    $this->imagesDir = 'catalog/';
    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;

    if (($version = $this->getCartVersionFromDb('SchemaVersion', 'DatabaseShard', 'Status = 1')) != '') {
      $this->cartVars['dbVersion'] = $version;
    }
  }
}

class M1_Config_Adapter_Cubecart extends M1_Config_Adapter
{
  function M1_Config_Adapter_Cubecart()
  {
    include_once(M1_STORE_BASE_DIR . 'includes/global.inc.php');

    $this->setHostPort($glob['dbhost']);
    $this->Dbname = $glob['dbdatabase'];
    $this->Username = $glob['dbusername'];
    $this->Password = $glob['dbpassword'];

    $this->imagesDir = 'images';
    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;
    $dirHandle = opendir(M1_STORE_BASE_DIR . 'language/');

    //settings for cube 5
    $languages = array();
    while ($dirEntry = readdir($dirHandle)) {
      $info = pathinfo($dirEntry);
      $xmlflag = false;

      if (isset($info['extension'])) {
        $xmlflag = strtoupper($info['extension']) != 'XML' ? true : false;
      }

      if (is_dir(M1_STORE_BASE_DIR . 'language/' . $dirEntry)
          || $dirEntry == '.'
          || $dirEntry == '..'
          || strpos($dirEntry, '_') !== false
          || $xmlflag
      ) {
        continue;
      }

      $configXml = simplexml_load_file(M1_STORE_BASE_DIR . 'language/' . $dirEntry);
      if ($configXml->info->title) {
        $lang['name'] = (string)$configXml->info->title;
        $lang['code'] = substr((string)$configXml->info->code, 0, 2);
        $lang['locale'] = substr((string)$configXml->info->code, 0, 2);
        $lang['currency'] = (string)$configXml->info->default_currency;
        $lang['fileName'] = str_replace('.xml', '', $dirEntry);
        $languages[] = $lang;
      }
    }

    if (!empty($languages)) {
      $this->cartVars['languages'] = $languages;
    }

    if (file_exists(M1_STORE_BASE_DIR  . 'ini.inc.php')) {
      $conf = file_get_contents (M1_STORE_BASE_DIR . 'ini.inc.php');
      preg_match("/ini\['ver'\].*/", $conf, $match);
      if (isset($match[0]) && !empty($match[0])) {
        preg_match("/\d.*/", $match[0], $project);
        if (isset($project[0]) && !empty($project[0])) {
          $version = $project[0];
          $version = str_replace(array(' ','-','_',"'",');',';',')'), '', $version);
          if ($version != '') {
            $this->cartVars['dbVersion'] = strtolower($version);
          }
        }
      } else {
        preg_match("/define\('CC_VERSION.*/", $conf, $match);
        if (isset($match[0]) && !empty($match[0])) {
          preg_match("/\d.*/", $match[0], $project);
          if (isset($project[0]) && !empty($project[0])) {
            $version = $project[0];
            $version = str_replace(array(' ','-','_',"'",');',';',')'), '', $version);
            if ($version != '') {
              $this->cartVars['dbVersion'] = strtolower($version);
            }
          }
        }
      }
    } elseif (file_exists(M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'ini.inc.php')) {
      $conf = file_get_contents (M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'ini.inc.php');
      preg_match("/ini\['ver'\].*/", $conf, $match);
      if (isset($match[0]) && !empty($match[0])) {
        preg_match("/\d.*/", $match[0], $project);
        if (isset($project[0]) && !empty($project[0])) {
          $version = $project[0];
          $version = str_replace(array(' ','-','_',"'",');',';',')'), '', $version);
          if ($version != '') {
            $this->cartVars['dbVersion'] = strtolower($version);
          }
        }
      } else {
        preg_match("/define\('CC_VERSION.*/", $conf, $match);
        if (isset($match[0]) && !empty($match[0])) {
          preg_match("/\d.*/", $match[0], $project);
          if (isset($project[0]) && !empty($project[0])) {
            $version = $project[0];
            $version = str_replace(array(' ','-','_',"'",');',';',')'), '', $version);
            if ($version != '') {
              $this->cartVars['dbVersion'] = strtolower($version);
            }
          }
        }
      }
    }
  }
}

class M1_Config_Adapter_Tomatocart extends M1_Config_Adapter
{
  function M1_Config_Adapter_Tomatocart()
  {
    $config = file_get_contents(M1_STORE_BASE_DIR . 'includes/configure.php');
    preg_match("/define\(\'DB_DATABASE\', \'(.+)\'\);/", $config, $match);
    $this->Dbname   = $match[1];
    preg_match("/define\(\'DB_SERVER_USERNAME\', \'(.+)\'\);/", $config, $match);
    $this->Username = $match[1];
    preg_match("/define\(\'DB_SERVER_PASSWORD\', \'(.*)\'\);/", $config, $match);
    $this->Password = $match[1];
    preg_match("/define\(\'DB_SERVER\', \'(.+)\'\);/", $config, $match);
    $this->setHostPort( $match[1] );

    preg_match("/define\(\'DIR_WS_IMAGES\', \'(.+)\'\);/", $config, $match);
    $this->imagesDir = $match[1];

    $this->categoriesImagesDir    = $this->imagesDir . 'categories/';
    $this->productsImagesDir      = $this->imagesDir . 'products/';
    $this->manufacturersImagesDir = $this->imagesDir . 'manufacturers/';
    if (file_exists(M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'application_top.php')) {
      $conf = file_get_contents(M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'application_top.php');
      preg_match("/define\('PROJECT_VERSION.*/", $conf, $match);

      if (isset($match[0]) && !empty($match[0])) {
        preg_match("/\d.*/", $match[0], $project);
        if (isset($project[0]) && !empty($project[0])) {
          $version = $project[0];
          $version = str_replace(array(' ','-','_',"'",');'), '', $version);
          if ($version != '') {
            $this->cartVars['dbVersion'] = strtolower($version);
          }
        }
      } else {
        //if another version
        if (file_exists(M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'version.php')) {
          @require_once M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'version.php';
          if (defined('PROJECT_VERSION') && PROJECT_VERSION != '') {
            $version = PROJECT_VERSION;
            preg_match("/\d.*/", $version, $vers);
            if (isset($vers[0]) && !empty($vers[0])) {
              $version = $vers[0];
              $version = str_replace(array(' ','-','_'), '', $version);
              if ($version != '') {
                $this->cartVars['dbVersion'] = strtolower($version);
              }
            }
          }
        }
      }
    }
  }
}



class M1_Config_Adapter_DrupalCommerce extends M1_Config_Adapter
{

  function M1_Config_Adapter_DrupalCommerce()
  {
    @include_once M1_STORE_BASE_DIR . "sites/default/settings.php";

    $url = $databases['default']['default'];

    $url['username'] = urldecode($url['username']);
    $url['password'] = isset($url['password']) ? urldecode($url['password']) : '';
    $url['host'] = urldecode($url['host']);
    $url['database'] = urldecode($url['database']);
    if (isset($url['port'])) {
      $url['host'] = $url['host'] .':'. $url['port'];
    }

    $this->setHostPort($url['host']);
    $this->Dbname   = ltrim($url['database'], '/');
    $this->Username = $url['username'];
    $this->Password = $url['password'];

    $this->imagesDir = '/sites/default/files/';
    if (!file_exists(M1_STORE_BASE_DIR . $this->imagesDir)) {
      $this->imagesDir = '/files';
    }

    $fileInfo = M1_STORE_BASE_DIR . '/sites/all/modules/commerce/commerce.info';
    if (file_exists($fileInfo)) {
      $str = file_get_contents($fileInfo);
      if (preg_match('/version\s+=\s+".+-(.+)"/', $str, $match) != 0) {
        $this->cartVars['dbVersion'] = $match[1];
        unset($match);
      }
    }

    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;
  }
}

class M1_Config_Adapter_JooCart extends M1_Config_Adapter
{
  function M1_Config_Adapter_JooCart()
  {
    require_once M1_STORE_BASE_DIR . '/configuration.php';

    if (class_exists('JConfig')) {

      $jconfig = new JConfig();

      $this->setHostPort($jconfig->host);
      $this->Dbname   = $jconfig->db;
      $this->Username = $jconfig->user;
      $this->Password = $jconfig->password;

    } else {

      $this->setHostPort($mosConfig_host);
      $this->Dbname   = $mosConfig_db;
      $this->Username = $mosConfig_user;
      $this->Password = $mosConfig_password;
    }

    $this->imagesDir              = 'components/com_opencart/image/';
    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;
  }
}


class M1_Config_Adapter_Prestashop11 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Prestashop11()
  {
    $conf_file1 = file_get_contents(M1_STORE_BASE_DIR . '/config/settings.inc.php');
    $conf_file2 = file_get_contents(M1_STORE_BASE_DIR . '/config/config.inc.php');

    $files_lines = array_merge(explode("\n", $conf_file1), explode("\n", $conf_file2));

    $execute = '$currentDir = \'\';';

    foreach ($files_lines as $line) {
      if (preg_match("/^(\s*)define\(/i",$line)) {
        if ((strpos($line, '_DB_') !== false)
            || (strpos($line, '_PS_IMG_DIR_') !== false)
            || (strpos($line, '_PS_VERSION_') !== false)
        ) {
          $execute .= ' ' . $line;
        }
      }
    }

    define('_PS_ROOT_DIR_', M1_STORE_BASE_DIR);
    eval($execute);

    $this->setHostPort(_DB_SERVER_);
    $this->Dbname   = _DB_NAME_;
    $this->Username = _DB_USER_;
    $this->Password = _DB_PASSWD_;

    if (defined('_PS_IMG_DIR_') && defined('_PS_ROOT_DIR_')) {

      preg_match("/(\/\w+\/)$/i",_PS_IMG_DIR_,$m);
      $this->imagesDir = $m[1];

    } else {
      $this->imagesDir = '/img/';
    }

    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;

    if (defined('_PS_VERSION_')) {
      $this->cartVars['dbVersion'] = _PS_VERSION_;
    }
  }
}



class miSettings {
  var $arr;

  function singleton()
  {
    static $instance = null;
    if ($instance == null) {
      $instance = new miSettings();
    }

    return $instance;
  }

  function setArray($arr)
  {
    $this->arr[] = $arr;
  }

  function getArray()
  {
    return $this->arr;
  }
}

class M1_Config_Adapter_Summercart3 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Summercart3()
  {
    @include_once M1_STORE_BASE_DIR . 'include/miphpf/Config.php';

    $instance = miSettings::singleton();

    $data = $instance->getArray();

    $this->setHostPort($data[0]['MI_DEFAULT_DB_HOST']);
    $this->Dbname   = $data[0]['MI_DEFAULT_DB_NAME'];
    $this->Username = $data[0]['MI_DEFAULT_DB_USER'];
    $this->Password = $data[0]['MI_DEFAULT_DB_PASS'];
    $this->imagesDir = '/userfiles/';

    $this->categoriesImagesDir    = $this->imagesDir . 'categoryimages';
    $this->productsImagesDir      = $this->imagesDir . 'productimages';
    $this->manufacturersImagesDir = $this->imagesDir . 'manufacturer';

    if (file_exists(M1_STORE_BASE_DIR . '/include/VERSION')) {
      $indexFileContent = file_get_contents(M1_STORE_BASE_DIR . '/include/VERSION');
      $this->cartVars['dbVersion'] = trim($indexFileContent);
    }
  }
}



class M1_Config_Adapter_Ubercart extends M1_Config_Adapter
{
  function M1_Config_Adapter_Ubercart()
  {
    @include_once M1_STORE_BASE_DIR . 'sites/default/settings.php';

    $url = parse_url($db_url);

    $url['user'] = urldecode($url['user']);
    // Test if database url has a password.
    $url['pass'] = isset($url['pass']) ? urldecode($url['pass']) : '';
    $url['host'] = urldecode($url['host']);
    $url['path'] = urldecode($url['path']);
    // Allow for non-standard MySQL port.
    if (isset($url['port'])) {
      $url['host'] = $url['host'] . ':' . $url['port'];
    }

    $this->setHostPort($url['host']);
    $this->Dbname   = ltrim($url['path'], '/');
    $this->Username = $url['user'];
    $this->Password = $url['pass'];

    $this->imagesDir = '/sites/default/files/';
    if (!file_exists(M1_STORE_BASE_DIR . $this->imagesDir)) {
      $this->imagesDir = '/files';
    }

    if (file_exists(M1_STORE_BASE_DIR . '/modules/ubercart/uc_cart/uc_cart.info')) {
      $str = file_get_contents(M1_STORE_BASE_DIR . '/modules/ubercart/uc_cart/uc_cart.info');
      if (preg_match('/version\s+=\s+".+-(.+)"/', $str, $match) != 0) {
        $this->cartVars['dbVersion'] = $match[1];
        unset($match);
      }
    }

    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;
  }
}



class M1_Config_Adapter_Woocommerce extends M1_Config_Adapter
{
  function M1_Config_Adapter_Woocommerce()
  {
    //@include_once M1_STORE_BASE_DIR . "wp-config.php";
    $config = file_get_contents(M1_STORE_BASE_DIR . 'wp-config.php');
    preg_match('/define\s*\(\s*\'DB_NAME\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
    $this->Dbname   = $match[1];
    preg_match('/define\s*\(\s*\'DB_USER\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
    $this->Username = $match[1];
    preg_match('/define\s*\(\s*\'DB_PASSWORD\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
    $this->Password = $match[1];
    preg_match('/define\s*\(\s*\'DB_HOST\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
    $this->setHostPort($match[1]);
    preg_match('/\$table_prefix\s*=\s*\'(.*)\'\s*;/', $config, $match);
    $this->TblPrefix = $match[1];
    $version = $this->getCartVersionFromDb('option_value', 'options', "option_name = 'woocommerce_db_version'");

    if ($version != '') {
      $this->cartVars['dbVersion'] = $version;
    }

    $this->cartVars['categoriesDirRelative'] = 'images/categories/';
    $this->cartVars['productsDirRelative'] = 'images/products/';    
    $this->imagesDir = "wp-content/uploads/images/";
    $this->categoriesImagesDir    = $this->imagesDir . 'categories/';
    $this->productsImagesDir      = $this->imagesDir . 'products/';
    $this->manufacturersImagesDir = $this->imagesDir;
  }
}



class M1_Config_Adapter_Cubecart3 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Cubecart3()
  {
    include_once(M1_STORE_BASE_DIR . 'includes/global.inc.php');

    $this->setHostPort($glob['dbhost']);
    $this->Dbname = $glob['dbdatabase'];
    $this->Username = $glob['dbusername'];
    $this->Password = $glob['dbpassword'];

    $this->imagesDir = 'images/uploads';
    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;
  }
}

class M1_Config_Adapter_Magento1212 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Magento1212()
  {
    /**
     * @var SimpleXMLElement
     */
    $config = simplexml_load_file(M1_STORE_BASE_DIR . 'app/etc/local.xml');
    $statuses = simplexml_load_file(M1_STORE_BASE_DIR . 'app/code/core/Mage/Sales/etc/config.xml');

    $version =  $statuses->modules->Mage_Sales->version;

    $result = array();

    if (version_compare($version, '1.4.0.25') < 0) {
      $statuses = $statuses->global->sales->order->statuses;
      foreach ($statuses->children() as $status) {
        $result[$status->getName()] = (string)$status->label;
      }
    }

    if (file_exists(M1_STORE_BASE_DIR . 'app/Mage.php')) {
      $ver = file_get_contents(M1_STORE_BASE_DIR . 'app/Mage.php');
      if (preg_match("/getVersionInfo[^}]+\'major\' *=> *\'(\d+)\'[^}]+\'minor\' *=> *\'(\d+)\'[^}]+\'revision\' *=> *\'(\d+)\'[^}]+\'patch\' *=> *\'(\d+)\'[^}]+}/s", $ver, $match) == 1) {
        $mageVersion = $match[1] . '.' . $match[2] . '.' . $match[3] . '.' . $match[4];
        $this->cartVars['dbVersion'] = $mageVersion;
        unset($match);
      }
    }

    $this->cartVars['orderStatus'] = $result;
    $this->cartVars['AdminUrl']    = (string)$config->admin->routers->adminhtml->args->frontName;

    $this->setHostPort((string)$config->global->resources->default_setup->connection->host);
    $this->Username = (string)$config->global->resources->default_setup->connection->username;
    $this->Dbname   = (string)$config->global->resources->default_setup->connection->dbname;
    $this->Password = (string)$config->global->resources->default_setup->connection->password;

    $this->imagesDir              = 'media/';
    $this->categoriesImagesDir    = $this->imagesDir . 'catalog/category/';
    $this->productsImagesDir      = $this->imagesDir . 'catalog/product/';
    $this->manufacturersImagesDir = $this->imagesDir;
    @unlink(M1_STORE_BASE_DIR . 'app/etc/use_cache.ser');
  }
}

class M1_Config_Adapter_LemonStand extends M1_Config_Adapter
{
  function M1_Config_Adapter_LemonStand()
  {
    include (M1_STORE_BASE_DIR . 'phproad/system/phpr.php');
    include (M1_STORE_BASE_DIR . 'phproad/modules/phpr/classes/phpr_securityframework.php');

    define('PATH_APP','');

    if (phpversion() > 5) {
      eval ('Phpr::$config = new MockConfig();
      Phpr::$config->set("SECURE_CONFIG_PATH", M1_STORE_BASE_DIR . "config/config.dat");
      $framework = Phpr_SecurityFramework::create();');
    }

    $config_content = $framework->get_config_content();

    $this->setHostPort($config_content['mysql_params']['host']);
    $this->Dbname   = $config_content['mysql_params']['database'];
    $this->Username = $config_content['mysql_params']['user'];
    $this->Password = $config_content['mysql_params']['password'];

    $this->categoriesImagesDir    = '/uploaded/thumbnails/';
    $this->productsImagesDir      = '/uploaded/';
    $this->manufacturersImagesDir = '/uploaded/thumbnails/';

    $version = $this->getCartVersionFromDb('version_str', 'core_versions', "moduleId = 'shop'");
    $this->cartVars['dbVersion'] = $version;
  }
}

class MockConfig {
  var $_data = array();
  function set($key, $value)
  {
    $this->_data[$key] = $value;
  }
  
  function get($key, $default = 'default')
  {
    return isset($this->_data[$key]) ? $this->_data[$key] : $default;
  }
}

class M1_Config_Adapter_Sunshop4 extends M1_Config_Adapter
{
  function M1_Config_Adapter_Sunshop4()
  {
    @require_once M1_STORE_BASE_DIR . 'include' . DIRECTORY_SEPARATOR . 'config.php';

    $this->imagesDir = 'images/products/';

    $this->categoriesImagesDir    = $this->imagesDir;
    $this->productsImagesDir      = $this->imagesDir;
    $this->manufacturersImagesDir = $this->imagesDir;

    if (defined('ADMIN_DIR')) {
      $this->cartVars['AdminUrl'] = ADMIN_DIR;
    }

    $this->setHostPort($servername);
    $this->Username  = $dbusername;
    $this->Password  = $dbpassword;
    $this->Dbname    = $dbname;

    if (isset($dbprefix)) {
      $this->TblPrefix = $dbprefix;
    }

    $version = $this->getCartVersionFromDb('value', 'settings', "name = 'version'");
    if ($version != '') {
      $this->cartVars['dbVersion'] = $version;
    }
  }
}



class M1_Config_Adapter
{
  var $Host                = 'localhost';
  var $Port                = '3306';
  var $Username            = 'root';
  var $Password            = '';
  var $Dbname              = '';
  var $TblPrefix           = '';

  var $cartType                = 'Oscommerce22ms2';
  var $imagesDir               = '';
  var $categoriesImagesDir     = '';
  var $productsImagesDir       = '';
  var $manufacturersImagesDir  = '';
  var $categoriesImagesDirs    = '';
  var $productsImagesDirs      = '';
  var $manufacturersImagesDirs = '';

  var $languages   = array();
  var $cartVars    = array();

  function create()
  {
    if (isset($_GET['action']) && $_GET['action'] == 'update') {
      return null;
    }

    $cartType = M1_Config_Adapter::detectCartType();
    $className =  'M1_Config_Adapter_' . $cartType;
    $obj = new $className();
    $obj->cartType = $cartType;
    return $obj;
  }

  function detectCartType()
  {
    // Zencart137
    if (file_exists(M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php')
        && file_exists(M1_STORE_BASE_DIR . 'ipn_main_handler.php')
    ) {
      return 'Zencart137';
    }

    //osCommerce
    if (file_exists(M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php')
        && !file_exists(M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'toc_constants.php')/* is if not tomatocart */
    ) {
      return 'Oscommerce22ms2';
    }

    //JooCart
    if (file_exists(M1_STORE_BASE_DIR . '/components/com_opencart/opencart.php')) {
      return 'JooCart';
    }

    //ACEShop
    if (file_exists(M1_STORE_BASE_DIR . '/components/com_aceshop/aceshop.php')) {
      return 'AceShop';
    }

    //MijoShop
    if (file_exists(M1_STORE_BASE_DIR . '/components/com_mijoshop/mijoshop.php')) {
      return 'MijoShop';
    }

    //Litecommerce
    if ((file_exists(M1_STORE_BASE_DIR . '/etc/config.php'))
        || (file_exists(M1_STORE_BASE_DIR .'/modules/lc_connector/litecommerce/etc/config.php'))
    ) {
      return 'Litecommerce';
    }

    //Prestashop11
    if (file_exists(M1_STORE_BASE_DIR . 'config/config.inc.php')) {
      if (file_exists(M1_STORE_BASE_DIR . 'cache/class_index.php')) {
        return 'Prestashop15';
      }

      return 'Prestashop11';
    }

    //Virtuemart113
    if ((file_exists(M1_STORE_BASE_DIR . 'configuration.php'))
        && (!file_exists(M1_STORE_BASE_DIR . '/components/com_mijoshop/mijoshop.php'))
    ) {
      return 'Virtuemart113';
    }

    //Pinnacle361
    if (file_exists(M1_STORE_BASE_DIR . 'content/engine/engine_config.php')) {
      return 'Pinnacle361';
    }

    //Magento1212, we can be sure that PHP is >= 5.2.0
    if (file_exists(M1_STORE_BASE_DIR . 'app/etc/local.xml')) {
      return 'Magento1212';
    }

    //Cubecart3
    if (file_exists(M1_STORE_BASE_DIR . 'includes/global.inc.php')) {
      return 'Cubecart';
    }

    //Cscart203 - 3
    if (file_exists(M1_STORE_BASE_DIR . 'config.local.php')
        || file_exists(M1_STORE_BASE_DIR . 'partner.php')
    ) {
      return 'Cscart203';
    }

    //Opencart14
    if ((file_exists(M1_STORE_BASE_DIR . 'system/startup.php')
        || (file_exists(M1_STORE_BASE_DIR . 'common.php'))
        || (file_exists(M1_STORE_BASE_DIR . 'library/locator.php')))
        && file_exists(M1_STORE_BASE_DIR . 'config.php')
    ) {
      return 'Opencart14';
    }

    //XCart
    if (file_exists(M1_STORE_BASE_DIR . 'config.php')) {
      return 'XCart';
    }

    //LemonStand
    if (file_exists(M1_STORE_BASE_DIR . 'boot.php')) {
      return 'LemonStand';
    }

    //Interspire
    if (file_exists(M1_STORE_BASE_DIR . 'config/config.php')) {
      return 'Interspire';
    }

    //Squirrelcart242
    if (file_exists(M1_STORE_BASE_DIR . 'squirrelcart/config.php')) {
      return 'Squirrelcart242';
    }

    //Shopscript282
    if (file_exists(M1_STORE_BASE_DIR . 'kernel/wbs.xml')) {
      return 'Shopscript282';
    }

    //Shopscriptfree
    if (file_exists(M1_STORE_BASE_DIR . 'cfg/connect.inc.php')) {
      return 'Shopscriptfree';
    }

    //Summercart3
    if (file_exists(M1_STORE_BASE_DIR . 'sclic.lic')
        && file_exists(M1_STORE_BASE_DIR . 'include/miphpf/Config.php')
    ) {
      return 'Summercart3';
    }

    //XtcommerceVeyton
    if (file_exists(M1_STORE_BASE_DIR . 'conf/config.php')) {
      return 'XtcommerceVeyton';
    }

    //Ubercart
    if (file_exists(M1_STORE_BASE_DIR . 'sites/default/settings.php')) {
      if (file_exists(M1_STORE_BASE_DIR . 'sites/all/modules/ubercart/uc_store/includes/coder_review_uc3x.inc')) {
        return 'Ubercart3';
      } elseif (file_exists(M1_STORE_BASE_DIR . 'sites/all/modules/commerce/includes/commerce.controller.inc')) {
        return 'DrupalCommerce';
      }

      return 'Ubercart';
    }

    //WPecommerce
    if (file_exists(M1_STORE_BASE_DIR . 'wp-config.php')
        && file_exists(M1_STORE_BASE_DIR . 'wp-content/plugins/woocommerce/woocommerce.php')) {
      return 'Woocommerce';
    }

    //WPecommerce
    if (file_exists(M1_STORE_BASE_DIR . 'wp-config.php')) {
      return 'WPecommerce';
    }

    //OXID e-shop
    if (file_exists(M1_STORE_BASE_DIR . 'oxid.php')
        || file_exists(M1_STORE_BASE_DIR . '/core/oxid.php')) {
      return 'Oxid';
    }

    //HHGMultistore
    if (file_exists(M1_STORE_BASE_DIR . 'core/config/configure.php')) {
      return 'Hhgmultistore';
    }

    //SunShop
    if (file_exists(M1_STORE_BASE_DIR . 'include' . DIRECTORY_SEPARATOR . 'config.php')
        || file_exists(M1_STORE_BASE_DIR . 'include' . DIRECTORY_SEPARATOR . 'db_mysql.php')
    ) {
      return 'Sunshop4';
    }

    //Tomatocart
    if (file_exists(M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php')
        && file_exists(M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'toc_constants.php')
    ) {
      return 'Tomatocart';
    }

    //Loaded7
    if (file_exists(M1_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'config.php')) {
      return 'Loaded7';
    }

    //PS
    if (file_exists(M1_STORE_BASE_DIR . 'ps_config.php')) {
      return 'Prostores';
    }

    die ('BRIDGE_ERROR_CONFIGURATION_NOT_FOUND');
  }

  function getAdapterPath($cartType)
  {
    return M1_STORE_BASE_DIR .
                   M1_BRIDGE_DIRECTORY_NAME . DIRECTORY_SEPARATOR .
                   'app' . DIRECTORY_SEPARATOR .
                   'class' . DIRECTORY_SEPARATOR .
                   'config_adapter' . DIRECTORY_SEPARATOR . $cartType . '.php';
  }

  function setHostPort($source)
  {
    $source = trim($source);

    if ($source == '') {
      $this->Host = 'localhost';
      return;
    }

    $conf = explode(':', $source);
    if (isset($conf[0]) && isset($conf[1])) {
      $this->Host = $conf[0];
      $this->Port  = $conf[1];
    } elseif ($source[0] == '/') {
      $this->Host = 'localhost';
      $this->Port = $source;
    } else {
      $this->Host = $source;
    }
  }

  function connect()
  {
    $triesCount = 10;
    $link = null;

    $host = $this->Host.($this->Port? ':'.$this->Port : '');
    while (!$link) {
      if (!$triesCount--) {
        break;
      }

      $link = @mysql_connect($host, $this->Username, $this->Password);
      if (!$link) {
        sleep(5);
      }
    }

    if ($link) {
      mysql_select_db($this->Dbname, $link);
    }

    return $link;
  }

  function getCartVersionFromDb($field, $tableName, $where)
  {
    $_link = null;
    $version = '';

    $_link = $this->connect();
    if (!$_link) {
      return '[ERROR] MySQL Query Error: Can not connect to DB';
    }

    $sql = 'SELECT ' . $field . ' AS version FROM ' . $this->TblPrefix . $tableName . ' WHERE ' . $where;

    $query = mysql_query($sql, $_link);

    if ($query !== false) {
      $row = mysql_fetch_assoc($query);

      $version = $row['version'];
    }

    return $version;
  }
}

class M1_Bridge_Action_Clearcache
{
  function perform($bridge)
  {
    switch ($bridge->config->cartType) {
      case 'Cubecart':
        $this->_CubecartClearCache();
      break;
      case 'Prestashop11':
        $this->_PrestashopClearCache();
      break;
      case 'Interspire':
        $this->_InterspireClearCache();
      break;
      case 'Opencart14' :
        $this->_OpencartClearCache();
      break;
      case 'XtcommerceVeyton' :
        $this->_Xtcommerce4ClearCache();
      break;
      case 'Ubercart' :
        $this->_ubercartClearCache();
      break;
      case 'Tomatocart' :
        $this->_tomatocartClearCache();
      break;
      case 'Virtuemart113' :
        $this->_virtuemartClearCache();
      break;
      case 'Magento1212' :
        //$this->_magentoClearCache();
      break;
      case 'Oscommerce3':
        $this->_Oscommerce3ClearCache();
      break;
      case 'Oxid':
        $this->_OxidClearCache();
      break;
      case 'XCart':
        $this->_XcartClearCache();
      break;
      case 'Cscart203':
        $this->_CscartClearCache();
      break;
      case 'Prestashop15':
        $this->_Prestashop15ClearCache();
      break;
    }
  }

  function _removeGarbage($dirs = array(), $fileExclude = '')
  {
    $result = true;

    foreach ($dirs as $dir) {

      if (!file_exists($dir)) {
        continue;
      }

      $dirHandle = opendir($dir);

      while (false !== ($file = readdir($dirHandle))) {
        if ($file == '.' || $file == '..') {
          continue;
        }

        if ((trim($fileExclude) != '') && preg_match('/^' . $fileExclude . '?$/', $file)) {
          continue;
        }

        if (is_dir($dir . $file)) {
          continue;
        }

        if (!unlink($dir . $file)) {
          $result = false;
        }
      }

      closedir($dirHandle);
    }

    if ($result) {
      print 'OK';
    } else {
      print 'ERROR';
    }

    return $result;
  }

  function _magentoClearCache()
  {
    chdir('../');

    $indexes = array(
      'catalog_product_attribute',
      'catalog_product_price',
      'catalog_url',
      'catalog_product_flat',
      'catalog_category_flat',
      'catalog_category_product',
      'catalogsearch_fulltext',
      'cataloginventory_stock',
      'tag_summary'
    );

    $phpExecutable = getPHPExecutable();
    if ($phpExecutable) {
      foreach ($indexes as $index) {
        exec($phpExecutable . " shell/indexer.php --reindex $index", $out);
      }
      print "OK";
    } else {
      echo 'Error: can not find PHP executable file.';
    }

    print 'OK';
  }
  
  function _InterspireClearCache()
  {
    $res = true;
    $file = M1_STORE_BASE_DIR . 'cache' . DIRECTORY_SEPARATOR . 'datastore' . DIRECTORY_SEPARATOR . 'RootCategories.php';
    if (file_exists($file)) {
      if (!unlink($file)) {
        $res = false;
      }
    }

    if ($res === true) {
      echo 'OK';
    } else {
      echo 'ERROR';
    }
  }

  function _CubecartClearCache()
  {
    $ok = true;
    
    if (file_exists(M1_STORE_BASE_DIR . 'cache')) {
      $dirHandle = opendir(M1_STORE_BASE_DIR . 'cache/');

      while (false !== ($file = readdir($dirHandle))) {
        if ($file != '.' && $file != '..' && !preg_match("/^index\.html?$/",$file) && !preg_match("/^\.htaccess?$/",$file) ) {
          if (is_file(M1_STORE_BASE_DIR . 'cache/' . $file)) {
            if (!unlink(M1_STORE_BASE_DIR . 'cache/' . $file)) {
              $ok = false;
            }
          }
        }
      }

      closedir($dirHandle);
    }

    if (file_exists(M1_STORE_BASE_DIR . 'includes/extra/admin_cat_cache.txt')) {
      unlink(M1_STORE_BASE_DIR . 'includes/extra/admin_cat_cache.txt');
    }

    if ($ok) {
      print 'OK';
    } else {
      print 'ERROR';
    }
  }

  function _PrestashopClearCache()
  {
    $dirs = array(
      M1_STORE_BASE_DIR . 'tools/smarty/compile/',
      M1_STORE_BASE_DIR . 'tools/smarty/cache/',
      M1_STORE_BASE_DIR . 'img/tmp/'
    );

    $this->_removeGarbage($dirs, 'index\.php');
  }

  function _OpencartClearCache()
  {
    $dirs = array(
      M1_STORE_BASE_DIR . 'system/cache/',
    );

    $this->_removeGarbage($dirs, 'index\.html');
  }

  function _Xtcommerce4ClearCache()
  {
    $dirs = array(
      M1_STORE_BASE_DIR . 'cache/',
    );

    $this->_removeGarbage($dirs, 'index\.html');
  }

  function _ubercartClearCache()
  {
    $dirs = array(
      M1_STORE_BASE_DIR . 'sites/default/files/imagecache/product/',
      M1_STORE_BASE_DIR . 'sites/default/files/imagecache/product_full/',
      M1_STORE_BASE_DIR . 'sites/default/files/imagecache/product_list/',
      M1_STORE_BASE_DIR . 'sites/default/files/imagecache/uc_category/',
      M1_STORE_BASE_DIR . 'sites/default/files/imagecache/uc_thumbnail/',
    );

    $this->_removeGarbage($dirs);
  }

  function _tomatocartClearCache()
  {
    $dirs = array(
      M1_STORE_BASE_DIR . 'includes/work/',
    );

    $this->_removeGarbage($dirs, '\.htaccess');
  }

  /**
   * Try to change permissions actually :)
   */
  function _virtuemartClearCache()
  {
    $pathToImages = 'components/com_virtuemart/shop_image';

    $dir_parts = explode('/', $pathToImages);
    $path = M1_STORE_BASE_DIR;
    foreach ($dir_parts as $item) {
      if ($item == '') {
        continue;
      }

      $path .= $item . DIRECTORY_SEPARATOR;
      @chmod($path, 0755);
    }
  }

  function _Oscommerce3ClearCache()
  {
    $dirs = array(
      M1_STORE_BASE_DIR . 'osCommerce/OM/Work/Cache/',
    );

    $this->_removeGarbage($dirs, '\.htaccess');
  }

  function _OxidClearCache()
  {
    $dirs = array(
      M1_STORE_BASE_DIR . 'tmp/',
    );

    $this->_removeGarbage($dirs, '\.htaccess');
  }

  function _XcartClearCache()
  {
    $dirs = array(
      M1_STORE_BASE_DIR . 'var/cache/',
    );

    $this->_removeGarbage($dirs, '\.htaccess');
  }
  
  function _CscartClearCache()
  {
    $dir = M1_STORE_BASE_DIR . 'var/cache/';
    $res = $this->removeDirRec($dir);

    if ($res) {
      print "OK\n";
    } else {
      print "ERROR\n";
    }
  }

  function _Prestashop15ClearCache()
  {
    $dirs = array(
      M1_STORE_BASE_DIR . 'cache/smarty/compile/',
      M1_STORE_BASE_DIR . 'cache/smarty/cache/',
      M1_STORE_BASE_DIR . 'img/tmp/'
    );

    $this->_removeGarbage($dirs, 'index\.php');
  }

  function removeDirRec($dir)
  {
    $result = true;
    if ($objs = glob($dir . '/*')) {
      foreach ($objs as $obj) {
        if (is_dir($obj)) {
          $this->removeDirRec($obj);
        } else {
          if(!unlink($obj)){
            $result = false;
          }
        }
      }
    }

    if (!rmdir($dir)) {
      $result = false;
    }

    return $result;
  }
}

class M1_Bridge_Action_Getmagentolog
{
  var $dir = 'var/log';
  var $files = array(
    'exception.log',
    'system.log'
  );
  var $maxReadLines = 200;

  function perform($bridge)
  {
    if (!file_exists(M1_STORE_BASE_DIR . $this->dir)) {
      die('ERROR_LOG_DIR_NOT_EXIST');
    }

    foreach ($this->files as $file) {
      $filename = M1_STORE_BASE_DIR . $this->dir . '/' . $file;

      echo '<h2>Read file "' . $filename . '": </h2>';

      if (!is_file($filename)) {
        echo 'ERROR_FILE_NOT_EXISTS<br />';
        continue;
      }

      if (filesize($filename) == 0) {
        echo 'ERROR_FILE_IS_EMPTY<br />';
        continue;
      }

      $lines = file($filename);

      if (count($lines) > $this->maxReadLines) {
        $lines = array_slice($lines, count($lines) - $this->maxReadLines, $this->maxReadLines);
      }

      echo nl2br(implode('', $lines));
    }
  }
}

class M1_Bridge_Action_Savefile
{
  var $_imageType = null;

  function perform($bridge)
  {
    $source      = $_POST['src'];
    $destination = $_POST['dst'];
    $width       = (int)$_POST['width'];
    $height      = (int)$_POST['height'];
    $local       = $_POST['local_source'];

    echo $this->_saveFile($source, $destination, $width, $height, $local);
  }

  function _saveFile($source, $destination, $width, $height, $local = '')
  {
    if (trim($local) != '') {

      if ($this->_copyLocal($local, $destination, $width, $height)) {
        return 'OK';
      }
    }

    if ($this->_isSameHost($source)) {
      $result = $this->_saveFileLocal($source, $destination);
    } else {
      $result = $this->_saveFileCurl($source, $destination);
    }

    if ($result != 'OK') {
      return $result;
    }

    $destination = M1_STORE_BASE_DIR . $destination;

    if ($width != 0 && $height != 0) {
      $this->_scaled2($destination, $width, $height);
    }
    
    if ($this->cartType == 'Prestashop11' || $this->cartType == 'Prestashop15') {
      // convert destination.gif(png) to destination.jpg
      $imageGd = $this->_loadImage($destination);

      if ($imageGd === false) {
        return $result;
      }

      if (!$this->_convert($imageGd, $destination, IMAGETYPE_JPEG, 'jpg')) {
        return 'CONVERT FAILED';
      }
    }

    return $result;
  }

  function _copyLocal($source, $destination, $width, $height)
  {
    $source = M1_STORE_BASE_DIR . $source;
    $destination = M1_STORE_BASE_DIR . $destination;

    if (!@copy($source, $destination)) {
      return false;
    }

    if ($width != 0 && $height != 0) {
      $this->_scaled2($destination, $width, $height);
    }

    return true;
  }

  function _loadImage($filename, $skipJpg = true)
  {
    $image_info = @getimagesize($filename);

    if ($image_info === false) {
      return false;
    }

    $this->_imageType = $image_info[2];

    switch ($this->_imageType) {
      case IMAGETYPE_JPEG :
        $image = imagecreatefromjpeg($filename);
        break;
      case IMAGETYPE_GIF  :
        $image = imagecreatefromgif($filename);
        break;
      case IMAGETYPE_PNG  :
        $image = imagecreatefrompng($filename);
        break;
      default:
        return false;
    }

    if ($skipJpg && ($this->_imageType == IMAGETYPE_JPEG)) {
      return false;
    }

    return $image;
  }

  function _saveImage($image, $filename, $image_type = IMAGETYPE_JPEG, $compression = 85, $permissions = null)
  {    
    $result = true;
    if ($image_type == IMAGETYPE_JPEG) {
      $result = imagejpeg($image, $filename, $compression);
    } elseif ($image_type == IMAGETYPE_GIF) {
      $result = imagegif($image, $filename);
    } elseif ($image_type == IMAGETYPE_PNG) {
      $result = imagepng($image, $filename);
    }

    if ($permissions != null) {
      chmod($filename, $permissions);
    }

    imagedestroy($image);

    return $result;
  }

  function _saveFileLocal($source, $destination)
  {
    $srcInfo = parse_url($source);
    $src = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $srcInfo['path'];

    if ($this->_create_dir(dirname($destination)) !== false) {
      $dst = M1_STORE_BASE_DIR . $destination;

      if (!@copy($src, $dst)) {
        return $this->_saveFileCurl($source, $destination);
      }
    } else {
      return '[BRIDGE ERROR] Deirectory creation failed!';
    }

    return 'OK';
  }

  function _saveFileCurl($source, $destination)
  {
    $source = $this->_escapeSource($source);
    if ($this->_create_dir(dirname($destination)) !== false) {
      $destination = M1_STORE_BASE_DIR . $destination;

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $source);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_TIMEOUT, 60);
      curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
      curl_setopt($ch, CURLOPT_NOBODY, true);
      curl_exec($ch);
      $httpResponseCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

      if ($httpResponseCode != 200) {
         curl_close($ch);
        return '[BRIDGE ERROR] Bad response received from source, HTTP code ' . $httpResponseCode . '!';
      }

      $dst = @fopen($destination, 'wb');
      if ($dst === false) {
        return '[BRIDGE ERROR] Can\'t create ' . $destination . '!';
      }

      curl_setopt($ch, CURLOPT_NOBODY, false);
      curl_setopt($ch, CURLOPT_FILE, $dst);
      curl_setopt($ch, CURLOPT_HTTPGET, true);
      curl_exec($ch);

      if (($error_no = curl_errno($ch)) != CURLE_OK) {
        return '[BRIDGE ERROR] ' . $error_no . ': ' . curl_error($ch);
      }

      curl_close($ch);
      @chmod($destination, 0777);

      return 'OK';
    } else {
      return '[BRIDGE ERROR] Directory creation failed!';
    }
  }

  function _escapeSource($source)
  {
    return str_replace(' ', '%20', $source);
  }

  function _create_dir($dir)
  {
    $dir_parts = explode('/', $dir);
    $path = M1_STORE_BASE_DIR;
    foreach ($dir_parts as $item) {
      if ($item == '') {
        continue;
      }

      $path .= $item . DIRECTORY_SEPARATOR;
      if (!is_dir($path)) {
        $res = @mkdir($path);
        if (!$res) {
          return false;
        }
      }

      @chmod($path, 0777);
    }

    return true;
  }

  function _isSameHost($source)
  {
    $srcInfo = parse_url($source);

    if (preg_match('/\.php$/', $srcInfo['path'])) {
      return false;
    }

    $hostInfo = parse_url('http://' . $_SERVER['HTTP_HOST']);
    if (@$srcInfo['host'] == $hostInfo['host']) {
      return true;
    }

    return false;
  }

  /**
   * @param $image     - GD image object
   * @param $filename  - store sorce pathfile ex. M1_STORE_BASE_DIR . '/img/c/2.gif';
   * @param $type      - IMAGETYPE_JPEG, IMAGETYPE_GIF or IMAGETYPE_PNG
   * @param $extension - file extension, this use for jpg or jpeg extension in prestashop
   *
   * @return true if success or false if no
   */
  function _convert($image, $filename, $type = IMAGETYPE_JPEG, $extension = '')
  {
    $end = pathinfo($filename, PATHINFO_EXTENSION);

    if ($extension == '') {
      $extension = image_type_to_extension($type, false);
    }

    if ($end == $extension) {
      return true;
    }

    $width  = imagesx($image);
    $height = imagesy($image);

    $newImage = imagecreatetruecolor($width, $height);

    /* Allow to keep nice look even if resized */
    $white = imagecolorallocate($newImage, 255, 255, 255);
    imagefill($newImage, 0, 0, $white);
    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $width, $height);
    imagecolortransparent($newImage, $white);

    $pathSave = rtrim($filename, $end);

    $pathSave .= $extension;

    return $this->_saveImage($newImage, $pathSave, $type);
  }

  function _scaled($destination, $width, $height)
  {
    $image = $this->_loadImage($destination, false);

    if ($image === false) {
      return 'IMAGE NOT SUPPORTED';
    }

    $originWidth  = imagesx($image);
    $originHeight = imagesy($image);
    
    $rw = (int)$height * (int)$originWidth / (int)$originHeight;
    $useHeight = ($rw <= $width);

    if ($useHeight) {
      $width = (int)$rw;
    } else {
      $height = (int)((int)($width) * (int)($originHeight) / (int)($originWidth));
    }

    $new_image = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($new_image, 255, 255, 255);
    imagefill($new_image, 0, 0, $white);
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $width, $height, $originWidth, $originHeight);
    imagecolortransparent($new_image, $white);
    
    return $this->_saveImage($new_image, $destination, $this->_imageType, 100) ? 'OK' : 'CAN\'T SCALE IMAGE';
  }

  //scaled2 method optimizet for prestashop
  function _scaled2($destination, $destWidth, $destHeight)
  {
    $method = 0;

    $sourceImage = $this->_loadImage($destination, false);

    if ($sourceImage === false) {
      return 'IMAGE NOT SUPPORTED';
    }

    $sourceWidth  = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);

    $widthDiff = $destWidth / $sourceWidth;
    $heightDiff = $destHeight / $sourceHeight;

    if ($widthDiff > 1 && $heightDiff > 1) {
      $nextWidth = $sourceWidth;
      $nextHeight = $sourceHeight;
    } else {
      if (intval($method) == 2 || (intval($method) == 0 AND $widthDiff > $heightDiff)) {
        $nextHeight = $destHeight;
        $nextWidth = intval(($sourceWidth * $nextHeight) / $sourceHeight);
        $destWidth = ((intval($method) == 0) ? $destWidth : $nextWidth);
      } else {
        $nextWidth = $destWidth;
        $nextHeight = intval($sourceHeight * $destWidth / $sourceWidth);
        $destHeight = (intval($method) == 0 ? $destHeight : $nextHeight);
      }
    }

    $borderWidth = intval(($destWidth - $nextWidth) / 2);
    $borderHeight = intval(($destHeight - $nextHeight) / 2);

    $destImage = imagecreatetruecolor($destWidth, $destHeight);

    $white = imagecolorallocate($destImage, 255, 255, 255);
    imagefill($destImage, 0, 0, $white);

    imagecopyresampled($destImage, $sourceImage, $borderWidth, $borderHeight, 0, 0, $nextWidth, $nextHeight, $sourceWidth, $sourceHeight);
    imagecolortransparent($destImage, $white);

    return $this->_saveImage($destImage, $destination, $this->_imageType, 100) ? 'OK' : 'CAN\'T SCALE IMAGE';
  }
}

class M1_Bridge_Action_Mysqlver
{
  function perform($bridge)
  {
    $m = array();
    preg_match('/^(\d+)\.(\d+)\.(\d+)/', mysql_get_server_info($bridge->getLink()), $m);
    echo sprintf("%d%02d%02d", $m[1], $m[2], $m[3]);
  }
}

define('MSG_INFO',0);
define('MSG_WARN',1);
define('MSG_ERROR',2);

class M1_Bridge_Action_Dump
{
  var $_backupFolder = 'backup';

  var $_dbname = null;
  var $_dbhost = null;
  var $_dbuser = null;
  var $_dbpass = null;

  var $_link   = null;
  var $_maxAllowedPacket = null;
  var $_file = null;

  function perform($bridge)
  {
    $this->_dbname = $bridge->config->Dbname;
    $this->_dbhost = $bridge->config->Host;
    $this->_dbuser = $bridge->config->Username;
    $this->_dbpass = $bridge->config->Password;

    $method = isset($_GET['method']) ? $_GET['method'] : 'none';

    switch ($method) {
      case 'backup':
        $this->_checkFolder($bridge);
        $this->backup();
        break;
      case 'restore':
        $this->_checkFolder($bridge);
        $backup = isset($_GET['file']) ? str_replace('/','',$_GET['file']) : '';
        $this->restore($backup);
        break;
      case 'list':
        $this->_checkFolder($bridge,false);
        $this->listdb();
        break;
      default:
        $this->log('Backup not created!','',MSG_ERROR);
        break;
    }
  }

  /**
   * Backup db store
   */
  function backup()
  {
    $this->_connect_mysql();
    $this->setNames();

    $result = $this->query('SHOW TABLES');

    $listTables = array();

    $nameFile = $this->_backupFolder .'/' . $this->_dbname . '_' . date('Y_m_d_H_i') . '.sql';
    $this->open($nameFile,'w');

    while (($res = mysql_fetch_row($result)) !== FALSE) {
      $listTables[] = $res[0];
    }

    $backup = "/*  Backup Database " . $this->_dbname . "  */\n"
              . "/*  Date: " . date('Y-m-d H:i'). "  */";
    fwrite($this->_file,$backup);

    $this->log('Start create backup database');
    foreach ($listTables as $table) {
      $backup = $this->createTable($table);
      fwrite($this->_file,$backup);

      $this->insertsTable($table);
    }

    $this->log('Write backup in file',$nameFile);
    fclose($this->_file);

    $this->log('End backup');
  }

  /**
   * @param M1_Bridge $bridge
   * @param bool $viewsError
   */
  function _checkFolder($bridge, $viewsError = true)
  {
    $message = '';
    $type = 0;
    if (!is_dir($this->_backupFolder)) {
      if (!@mkdir($this->_backupFolder)) {
        $message = 'Failed to create a folder for backup!';
        $type = MSG_ERROR;

        $this->_backupFolder = M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'BACKUP_DB';

        if (!is_dir($this->_backupFolder)) {
          if (!@mkdir($this->_backupFolder)) {
            $message = 'Failed to create a folder for backup in the IMAGES folder!';
            $type = MSG_ERROR;
            return;
          } else {
            $message = 'Created a folder for backup in the IMAGES folder!';
            $type = MSG_WARN;
          }
        } else {
          $message = 'Created a folder for backup in the IMAGES folder!';
          $type = MSG_WARN;
        }
      }
    }

    if ($viewsError) {
      $this->log($message,'',$type);
    }
  }
  /**
   * Restore db store
   *
   * @param $file
   * @return bool
   */
  function restore($file)
  {
    $this->_connect_mysql();

    $this->log('Open backup');

    $nameFile = $this->_backupFolder . '/' . $file . '.sql';
    if (!file_exists($this->_backupFolder . '/' . $file . '.sql')) {
      $this->log('Backup not exists ',$file,2);
      return false;
    }

    $this->open($nameFile,'r');

    $this->log('Start restore backup database');

    $sql = null;
    $currentTable = '';

    $this->setNames();
    $this->setForeignKeyChecks();

    while (($row = fgets($this->_file)) !== false) {
      if (preg_match('/DROP TABLE IF EXISTS/',$row)) {

        if ($sql !== NULL) {
          $this->query($sql);
          $sql = null;
        }

        $this->query($row);

        $row = str_replace('DROP TABLE IF EXISTS','',$row);
        $row = str_replace(';','',$row);
        $currentTable = trim($row);
        $this->log('&nbsp;&nbsp;Drop table ',$currentTable);

        continue;
      }

      if (preg_match('/CREATE TABLE/',$row)) {

        if ($sql !== NULL) {
          $this->query($sql);
        }

        $this->log('&nbsp;&nbsp;Create table ',$currentTable);
        $sql = $row;
        continue;
      }

      if (preg_match('/INSERT INTO/',$row)) {

        if ($sql !== NULL) {
          $this->query($sql);
        }

        $this->log('&nbsp;&nbsp;Insert table ',$currentTable);
        $sql = $row;
        continue;
      }

      $sql .= $row;
    }

    $this->setForeignKeyChecks(1);

    fclose($this->_file);
    $this->log('End restore');
  }

  /**
   * Open backup in UTF-8
   *
   * @param $nameFile
   * @param $mode
   */
  function open($nameFile,$mode)
  {
    iconv_set_encoding('input_encoding', 'UTF-8');
    iconv_set_encoding('output_encoding', 'UTF-8');

    $this->_file = fopen($nameFile,$mode);
  }

  /**
   * Set names
   *
   * @param string $charset
   */
  function setNames($charset = 'utf8')
  {
    $this->log('Set names',$charset);
    $this->query('SET NAMES ' . $charset);
  }

  /**
   * Set foreign key checks
   *
   * @param int $check
   */
  function setForeignKeyChecks($check = 0)
  {
    $this->log('Set foreign key checks',$check);
    $this->query('SET FOREIGN_KEY_CHECKS = ' . $check);
  }

  /**
   * Echo list backup
   */
  function listdb()
  {
    $list = glob($this->_backupFolder . '/*.sql');

    foreach ($list as $id => $file) {
      $res = str_replace($this->_backupFolder . '/','',$file);
      $list[$id] = str_replace('.sql','',$res);
    }

    $result = implode('|',$list);
    echo $result;
  }

  /**
   * Connect to Mysql Server
   */
  function _connect_mysql()
  {
    $this->log('Connect to host',$this->_dbhost);
    $this->_link = @mysql_connect($this->_dbhost,$this->_dbuser,$this->_dbpass);
    if (!$this->_link) {
      $this->log('Can not connect to host ',$this->_dbhost,MSG_ERROR);
      exit;
    }

    $this->log('Select database',$this->_dbname);
    if (!@mysql_select_db($this->_dbname)) {
      $this->log('Error #' . mysql_errno($this->_link) . ' ' . mysql_error($this->_link),'',MSG_ERROR);
      exit;
    }
  }

  /**
   * Return structure create table
   *
   * @param $table
   * @return string
   */
  function createTable($table)
  {
    $result = $this->query('SHOW CREATE TABLE ' . $table);

    $this->log('&nbsp;&nbsp;Backup table',$table);
    $res = mysql_fetch_array($result);

    $comment = "\n\n/*  Table `{$table}`  */\n";
    $drop = "DROP TABLE IF EXISTS `{$table}`;\n";

    $create = $res['Create Table'];

    return $comment . $drop . $create . ';';
  }

  /**
   * Return inserts data form table
   *
   * @param $table
   * @return string
   */
  function insertsTable($table)
  {
    $start = 0;
    $limit = 5000;
    $count = $this->countRows("SELECT count(*) FROM {$table}");

    if ($count == 0) {
      return;
    }

    $insertQuery = $this->getInsertHeader($table);
    fwrite($this->_file,$insertQuery);

    $counter = (int)($count / $limit) + 1;

    for ($i = 1; $i <= $counter; $i++) {
      $result = $this->query("SELECT * FROM {$table} LIMIT {$start},{$limit}");
      $temp = '';

      while (($res = mysql_fetch_assoc($result)) !== false) {

        $value = array();
        foreach ($res as $val) {
          $value[] = "'" . mysql_real_escape_string($val) . "'";
        }

        $comma = ',';

        $count--;
        if ($count == 0) {
          $comma = ';';
        }

        $value = "\n(" . implode(',',$value) . ")";

        if(serialize(strlen($temp) + strlen($value)) >= $this->getMaxAllowedPacket()) {
          $temp = substr($temp, 0,strlen($temp)-1) . ';';

          fwrite($this->_file,$temp);
          $temp = $this->getInsertHeader($table);
        }

        $temp .= $value."{$comma}";
      }

      fwrite($this->_file,$temp);
      $start += $limit;
    }
  }

  /**
   * Return INSERT Header
   *
   * @param $table
   * @return string
   */
  function getInsertHeader($table)
  {
    $insert = "\n\nINSERT INTO `{$table}` (";

    $result = $this->query("SHOW COLUMNS FROM {$table}");

    $fields = array();
    while (($res = mysql_fetch_assoc($result)) !== FALSE) {
      $fields[] = " `{$res["Field"]}`";
    }
    $insert .= implode(',',$fields);
    $insert .= ') VALUES';

    return $insert;
  }

  /**
   * Run query
   *
   * @param $sql
   * @return resource
   */
  function query($sql)
  {
    if (!$result = @mysql_query($sql,$this->_link)) {
      $this->log('Error #' . mysql_errno() . ' ' . mysql_error(),"({$sql})",MSG_ERROR);
      exit;
    }

    return $result;
  }

  /**
   * Input and output log
   * 0 - Message Info
   * 1 - Message Warning
   * 2 - Message Error
   * @param $msg
   * @param string $additionalValue
   * @param int $type
   */
  function log($msg, $additionalValue = '', $type = 0)
  {
    $font = 'font-family: Verdana;font-size: 12px;';
    $color = '#000000';
    switch ($type) {
      case 0:
        $color = '#000000';
        break;
      case 1:
        $color = '#FFAB00';
        break;
      case 2;
        $color = '#FF0000';
        break;
      default:
        $color = '#000000';
        break;
    }

    echo "<div style='color:{$color};{$font}'><b>" . date('Y-m-d H:i:s') . ":</b> " . $msg . " <i>" . $additionalValue . "</i>.</div>";
  }

  /**
   * Return Mysql max_allowed_packet
   *
   * @return int
   */
  function getMaxAllowedPacket()
  {
    if ($this->_maxAllowedPacket === null) {
      $result = $this->query("SELECT @@max_allowed_packet");
      $result = mysql_fetch_array($result);
      $this->_maxAllowedPacket = (int)$result[0];
    }

    return (int)$this->_maxAllowedPacket;
  }

  /**
   * @param $sql
   * @return int
   */
  function countRows($sql)
  {
    $result = $this->query($sql);
    $result = mysql_fetch_array($result);

    return (int)$result[0];
  }
}

class M1_Bridge_Action_Batchsavefile extends M1_Bridge_Action_Savefile
{
  function perform($bridge)
  {
    $result = array();
    foreach ($_POST['files'] as $fileInfo) {
      $result[$fileInfo['id']] = $this->_saveFile($fileInfo['source'], $fileInfo['target'], (int)$fileInfo['width'], (int)$fileInfo['height'], $fileInfo['local_source']);
    }

    echo serialize($result);
  }
}

class M1_Bridge_Action_Update
{
  var $uri = 'BRIDGE_DOWNLOAD_LINK';
  var $pathToTmpDir;
  var $pathToFile = __FILE__;

  function M1_Bridge_Action_Update()
  {
    $this->pathToTmpDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'temp_c2c';
  }

  function perform($bridge)
  {
    $response = new stdClass();
    if (!($this->_checkBridgeDirPermission() && $this->_checkBridgeFilePermission())) {
      $response->is_error = true;
      $response->message = "Bridge Update couldn't be performed. Please change permission for bridge folder to 777 and bridge.php file inside it to 666";
      echo serialize($response);
      die;
    }

    if (($data = $this->_downloadFile()) === false) {
      $response->is_error = true;
      $response->message = "Bridge Version is outdated. Files couldn't be updated automatically. Please set write permission or re-upload files manually.";
      echo serialize($response);
      die;
    }

    if (!$this->_writeToFile($data, $this->pathToFile)) {
      $response->is_error = true;
      $response->message = "Couln't create file in temporary folder or file is write protected.";
      echo serialize($response);
      die;
    }

    $response->is_error = false;
    $response->message = 'Bridge successfully updated to latest version.';
    echo serialize($response);
    die;
  }

  function _fetch($uri)
  {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $response = new stdClass();

    $response->body           = curl_exec($ch);
    $response->http_code      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $response->content_type   = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $response->content_length = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

    curl_close($ch);

    return $response;
  }

  function _checkBridgeDirPermission()
  {
    if (!is_writeable(dirname(__FILE__))) {
      @chmod(dirname(__FILE__), 0777);
    }

    return is_writeable(dirname(__FILE__));
  }

  function _checkBridgeFilePermission()
  {
    $pathToFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bridge.php';
    if (!is_writeable($pathToFile)) {
      @chmod($pathToFile, 0666);
    }

    return is_writeable($pathToFile);
  }

  function _createTempDir()
  {
    @mkdir($this->pathToTmpDir, 0777);
    return file_exists($this->pathToTmpDir);
  }

  function _removeTempDir()
  {
    @unlink($this->pathToTmpDir . DIRECTORY_SEPARATOR . 'bridge.php_c2c');
    @rmdir($this->pathToTmpDir);
    return !file_exists($this->pathToTmpDir);
  }

  function _downloadFile()
  {
    $file = $this->_fetch($this->uri);
    if ($file->http_code == 200) {
      return $file;
    }
    return false;
  }

  function _writeToFile($data, $file)
  {
    if (function_exists('file_put_contents')) {
      $bytes = file_put_contents($file, $data->body);
      return $bytes == $data->content_length;
    }

    $handle = @fopen($file, 'w+');
    $bytes = fwrite($handle, $data->body);
    @fclose($handle);

    return $bytes == $data->content_length;
  }
}

class M1_Bridge_Action_Query
{
  function perform($bridge)
  {
    if (isset($_POST['query']) && isset($_POST['fetchMode'])) {

      $query = base64_decode($_POST['query']);

      $res = $bridge->query($query, (int)$_POST['fetchMode']);

      if (is_array($res['result']) || is_bool($res['result'])) {
        $result = serialize(array(
          'res'           => $res['result'],
          'fetchedFields' => @$res['fetchedFields'],
          'insertId'      => mysql_insert_id($bridge->getLink()),
          'affectedRows'  => mysql_affected_rows($bridge->getLink()),
        ));

        echo base64_encode($result);
      } else {
        echo base64_encode($res['message']);
      }
    } else {
      return false;
    }
  }
}

class M1_Bridge_Action_Getfsfile
{
  function perform($bridge)
  {
    $products_image = (string)$_POST['product_image'];

    $images_array = array();

    if ($products_image != '') {

      $products_image_extension = substr($products_image, strrpos($products_image, '.'));
      $products_image_base = str_replace($products_image_extension, '', $products_image);

      if (strrpos($products_image, '/')) {
        $products_image_match = substr($products_image, strrpos($products_image, '/') + 1);
        $products_image_match = str_replace($products_image_extension, '', $products_image_match) . '_';
        $products_image_base = $products_image_match;
      }

      $products_image_directory = str_replace($products_image, '', substr($products_image, strrpos($products_image, '/')));

      if ($products_image_directory != '') {
        $products_image_directory = DIR_WS_IMAGES . str_replace($products_image_directory, '', $products_image) . '/';
      } else {
        $products_image_directory = DIR_WS_IMAGES;
      }

      $file_extension = $products_image_extension;

      if ($dir = dir(M1_STORE_BASE_DIR . $products_image_directory)) {
        while ($file = $dir->read()) {
          if (!is_dir($products_image_directory . $file)) {
            if (substr($file, strrpos($file, '.')) == $file_extension) {
              if (preg_match('/' . $products_image_base . '/i', $file) == '1') {
                if ($file != $products_image) {
                  if ($products_image_base . str_replace($products_image_base, '', $file) == $file) {
                    $images_array[] = $products_image_directory . $file;
                  }
                }
              }
            }
          }
        }

        if (sizeof($images_array)) {
          sort($images_array);
        }

        $dir->close();
      }
    }

    echo serialize($images_array);
  }
}

class M1_Bridge_Action_Basedirfs
{
  function perform($bridge)
  {
    echo M1_STORE_BASE_DIR;
  }
}

class M1_Bridge_Action_Deleteimages
{
  function perform($bridge)
  {
    switch ($bridge->config->cartType) {
      case 'Pinnacle361':
        $this->_PinnacleDeleteImages($bridge);
      break;
      case 'Prestashop11':
        $this->_PrestaShopDeleteImages($bridge);
      break;
      case 'Summercart3' :
        $this->_SummercartDeleteImages($bridge);
      break;
    }
  }

  function _PinnacleDeleteImages($bridge)
  {
    $dirs = array(
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'catalog/',
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'manufacturers/',
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'products/',
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'products/thumbs/',
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'products/secondary/',
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'products/preview/',
    );

    $ok = true;

    foreach ($dirs as $dir) {

      if (!file_exists($dir)) {
        continue;
      }

      $dirHandle = opendir($dir);

      while (false !== ($file = readdir($dirHandle))) {
        if ($file != '.' && $file != '..' && !preg_match("/^readme\.txt?$/", $file) && !preg_match("/\.bak$/i", $file)) {
          $file_path = $dir . $file;
          if (is_file($file_path)) {
            if (!rename($file_path, $file_path . '.bak')) {
              $ok = false;
            }
          }
        }
      }

      closedir($dirHandle);
    }

    if ($ok) {
      print 'OK';
    } else {
      print 'ERROR';
    }
  }

  function _PrestaShopDeleteImages($bridge)
  {
    $dirs = array(
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'c/',
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'p/',
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'm/',
    );

    $ok = true;

    foreach ($dirs as $dir) {

      if (!file_exists($dir)) {
        continue;
      }

      $dirHandle = opendir($dir);

      while (false !== ($file = readdir($dirHandle))) {
        if ($file != '.' && $file != '..' && preg_match("/(\d+).*\.jpg?$/",$file)) {
          $file_path = $dir . $file;
          if (is_file($file_path)) {
            if (!rename($file_path, $file_path . '.bak')) {
              $ok = false;
            }
          }
        }
      }

      closedir($dirHandle);
    }

    if ($ok) {
      print 'OK';
    } else {
      print 'ERROR';
    }
  }

  function _SummercartDeleteImages($bridge)
  {
    $dirs = array(
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'categoryimages/',
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'manufacturer/',
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'productimages/',
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'productthumbs/',
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'productboximages/',
      M1_STORE_BASE_DIR . $bridge->config->imagesDir . 'productlargeimages/',
    );

    $ok = true;

    foreach ($dirs as $dir) {

      if (!file_exists($dir)) {
        continue;
      }

      $dirHandle = opendir($dir);

      while (false !== ($file = readdir($dirHandle))) {
        if (($file != '.') && ($file != '..') && !preg_match("/\.bak$/i", $file)) {
          $file_path = $dir . $file;
          if (is_file($file_path)) {
            if (!rename($file_path, $file_path . '.bak')) {
              $ok = false;
            }
          }
        }
      }

      closedir($dirHandle);
    }

    if ($ok) {
      print 'OK';
    } else {
      print 'ERROR';
    }
  }
}

class M1_Bridge_Action_Cubecart
{
  function perform($bridge)
  {
    $dirHandle = opendir(M1_STORE_BASE_DIR . 'language/');

    $languages = array();

    while ($dirEntry = readdir($dirHandle)) {
      if (!is_dir(M1_STORE_BASE_DIR . 'language/' . $dirEntry)
          || $dirEntry == '.'
          || $dirEntry == '..'
          || strpos($dirEntry, '_') !== false
      ) {
        continue;
      }

      $lang['id'] = $dirEntry;
      $lang['iso2'] = $dirEntry;

      $cnfile = 'config.inc.php';

      if (!file_exists(M1_STORE_BASE_DIR . 'language/' . $dirEntry . '/'. $cnfile)) {
        $cnfile = 'config.php';
      }

      if (!file_exists( M1_STORE_BASE_DIR . 'language/' . $dirEntry . '/'. $cnfile)) {
        continue;
      }

      $str = file_get_contents(M1_STORE_BASE_DIR . 'language/' . $dirEntry . '/' . $cnfile);
      preg_match('/' . preg_quote('$langName') . "[\s]*=[\s]*[\"\'](.*)[\"\'];/", $str, $match);

      if (isset($match[1])) {
        $lang['name'] = $match[1];
        $languages[] = $lang;
      }
    }

    echo serialize($languages);
  }
}

class M1_Bridge_Action_Phpinfo
{
  function perform($bridge)
  {
    phpinfo();
  }
}


class M1_Bridge_Action_Carttype
{
  function perform($bridge)
  {
    echo $bridge->config->cartType;
  }
}

class M1_Bridge_Action_Getconfig
{
  function parseMemoryLimit($val)
  {
    $last = strtolower($val[strlen($val)-1]);
    switch ($last) {
      case 'g':
        $val *= 1024;
        break;
      case 'm':
        $val *= 1024;
        break;
      case 'k':
        $val *= 1024;
        break;
    }

    return $val;
  }

  function getMemoryLimit()
  {
    $memoryLimit = trim(@ini_get('memory_limit'));
    if (strlen($memoryLimit) === 0) {
      $memoryLimit = '0';
    }

    $memoryLimit = $this->parseMemoryLimit($memoryLimit);

    $maxPostSize = trim(@ini_get('post_max_size'));
    if (strlen($maxPostSize) === 0) {
      $maxPostSize = '0';
    }

    $maxPostSize = $this->parseMemoryLimit($maxPostSize);

    $suhosinMaxPostSize = trim(@ini_get('suhosin.post.max_value_length'));
    if (strlen($suhosinMaxPostSize) === 0) {
      $suhosinMaxPostSize = '0';
    }

    $suhosinMaxPostSize = $this->parseMemoryLimit($suhosinMaxPostSize);

    if ($suhosinMaxPostSize == 0) {
      $suhosinMaxPostSize = $maxPostSize;
    }

    if ($maxPostSize == 0) {
      $suhosinMaxPostSize = $maxPostSize = $memoryLimit;
    }

    return min($suhosinMaxPostSize, $maxPostSize, $memoryLimit);
  }

  function isZlibSupported()
  {
    return function_exists('gzdecode');
  }

  function perform($bridge)
  {
    if (!defined('DEFAULT_LANGUAGE_ISO2')) {
      define('DEFAULT_LANGUAGE_ISO2', ''); //variable for Interspire cart
    }

    $result = array(
      'images' => array(
        'imagesPath'               => $bridge->config->imagesDir, // path to images folder - relative to store root
        'categoriesImagesPath'     => $bridge->config->categoriesImagesDir,
        'categoriesImagesPaths'    => $bridge->config->categoriesImagesDirs,
        'productsImagesPath'       => $bridge->config->productsImagesDir,
        'productsImagesPaths'      => $bridge->config->productsImagesDirs,
        'manufacturersImagesPath'  => $bridge->config->manufacturersImagesDir,
        'manufacturersImagesPaths' => $bridge->config->manufacturersImagesDirs,
      ),
      'languages'           => $bridge->config->languages,
      'baseDirFs'           => M1_STORE_BASE_DIR, // filesystem path to store root
      'defaultLanguageIso2' => DEFAULT_LANGUAGE_ISO2,
      'databaseName'        => $bridge->config->Dbname,
      'memoryLimit'         => $this->getMemoryLimit(),
      'zlibSupported'       => $this->isZlibSupported(),
      //'orderStatus'         => $bridge->config->orderStatus,
      'cartVars'            => $bridge->config->cartVars,
    );

    echo serialize($result);
  }
}

define('M1_BRIDGE_VERSION', '21');

define('M1_BRIDGE_DIRECTORY_NAME', basename(getcwd()));

ini_set('display_errors', 1);
if (substr(phpversion(), 0, 1) == 5) {
  error_reporting(E_ALL & ~E_STRICT);
} else {
  error_reporting(E_ALL);
}

require_once 'config.php';

function stripslashes_array($array)
{
  return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
}

function getPHPExecutable() {
  $paths = explode(PATH_SEPARATOR, getenv('PATH'));
  $paths[] = PHP_BINDIR;
  foreach ($paths as $path) {
    // we need this for XAMPP (Windows)
    if (isset($_SERVER["WINDIR"]) && strstr($path, 'php.exe') && file_exists($path) && is_file($path)) {
      return $path;
    }
    else {
      $phpExecutable = $path . DIRECTORY_SEPARATOR . "php" . (isset($_SERVER["WINDIR"]) ? ".exe" : "");
      if (file_exists($phpExecutable) && is_file($phpExecutable)) {
        return $phpExecutable;
      }
    }
  }
  return FALSE;
}

if (!isset($_SERVER)) {
  $_GET     = &$HTTP_GET_VARS;
  $_POST    = &$HTTP_POST_VARS;
  $_ENV     = &$HTTP_ENV_VARS;
  $_SERVER  = &$HTTP_SERVER_VARS;
  $_COOKIE  = &$HTTP_COOKIE_VARS;
  $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
}

if (get_magic_quotes_gpc()) {
  $_COOKIE  = stripslashes_array($_COOKIE);
  $_FILES   = stripslashes_array($_FILES);
  $_GET     = stripslashes_array($_GET);
  $_POST    = stripslashes_array($_POST);
  $_REQUEST = stripslashes_array($_REQUEST);
}

define('M1_STORE_BASE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

$bridge = new M1_Bridge(M1_Config_Adapter::create());

if (!$bridge->getLink()) {
  die('ERROR_BRIDGE_CANT_CONNECT_DB');
}

$bridge->run();
?>