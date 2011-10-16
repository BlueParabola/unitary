<?php
set_include_path(realpath(dirname(__FILE__) . '/../../../') . PATH_SEPARATOR . get_include_path());
/**
 * Point to a problem with the autodocumentation of servers which follows the specifications
 * in PHPCodeSniffer.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_RPC2
 * @author    Lars Olesen <lars@legestue.net>

 * @copyright 2007 Lars Olesen
 * @license   GPL http://www.opensource.org/licenses/gpl-license.php
 * @version   @package-version@
 * @link      http://pear.php.net/package/XML_RPC2
 */
require_once 'XML/RPC2/Server.php';

/**
 * The implementation
 *
 * @category  XML
 * @package   XML_RPC2
 * @author    Lars Olesen <lars@legestue.net>
 * @copyright 2007 Lars Olesen
 * @license   GPL http://www.opensource.org/licenses/gpl-license.php
 * @version   @package-version@
 * @link      http://pear.php.net/package/XML_RPC2
 */
class DocumentationServer {

    /**
     * returns something
     *
     * @param array   $something     A description
     * @param string  $another_thing A description of another thing
     * @param boolean $return        Whether to return nothing - server doesn't care though
     *
     * @return string An international string
     */
    public static function getSomething($something, $another_thing, $credentials) {
        return 'nothing interesting';
    }

}

$options = array(
    'prefix' => 'test.',
  //  'encoding' => 'ISO-8859-1'
  'encoding' => 'UTF-8'
);

$server = XML_RPC2_Server::create('DocumentationServer', $options);
$GLOBALS['HTTP_RAW_POST_DATA'] = '';
$server->handleCall();
?>
