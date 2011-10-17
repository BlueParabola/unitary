<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 4                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2003 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 2.02 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available at through the world-wide-web at                           |
  | http://www.php.net/license/2_02.txt.                                 |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: Alan Knowles <alan@akbkhome.com>                             |
  +----------------------------------------------------------------------+

  $Id: Gtk.php,v 1.20 2005/03/14 07:20:36 alan_k Exp $
*/

require_once "PEAR/Frontend.php";
require_once "PEAR/Frontend/Gtk/Packages.php";
require_once "PEAR/Frontend/Gtk/Summary.php";
require_once "PEAR/Frontend/Gtk/Install.php";
require_once "PEAR/Frontend/Gtk/Config.php";
require_once "PEAR/Frontend/Gtk/DirSelect.php";
require_once "PEAR/Frontend/Gtk/Info.php";
require_once "PEAR/Frontend/Gtk/Documentation.php";
/**
 * Core Gtk Frontend Class
 * All the real work is done in the child classes (Gtk/*.php)
 *
 * @author Alan Knowles <alan@akbkhome.com>
 */

class PEAR_Frontend_Gtk extends PEAR_Frontend
{
    // {{{ properties

    /**
     * What type of user interface this frontend is for.
     * @var string
     * @access public
     */
    var $type = 'Gtk';

    var $omode = 'plain';
    var $params = array();
    
    /**
    * master glade object 
    * @var object GladeXML
    * @access private
    */
    var $_glade;
    /**
    * current config? - is this really neccessay - just use Config::singleton?
    * @var object PEAR_Config
    * @access private
    */
    
    var $config; // object PEAR_Config
    // }}}

    // {{{ constructor
    /**
    * Gtk Frontend Constructor
    *
    * Makes calls to load the glade file, and connect signals.
    *
    * @access private
    */
    
    function PEAR_Frontend_Gtk()
    {
        parent::PEAR();
        if (!extension_loaded('php_gtk')) {
            dl('php_gtk.' . (OS_WINDOWS ? 'dll' : 'so'));
        }
        //echo "LOADED?";
    }
    // }}}
    
    function setConfig(&$config)
    {
        $this->config = &$config;
        $this->_summary      = &new PEAR_Frontend_Gtk_Summary($this);
        $this->_install      = &new PEAR_Frontend_Gtk_Install($this);
        $this->_packages     = &new PEAR_Frontend_Gtk_Packages($this);
        $this->_config       = &new PEAR_Frontend_Gtk_Config($this);
        $this->_dirselect    = &new PEAR_Frontend_Gtk_DirSelect($this);
        $this->_info         = &new PEAR_Frontend_Gtk_Info($this);
        $this->_documentation= &new PEAR_Frontend_Gtk_Documentation($this);
        $this->_loadGlade();
        $this->_initInterface();
    }

    /**
    * Load the Glade file (and automake widget vars and connect signals)
    *
    * Loads the glade file and also maps all widgets 'NAME' in glade file 
    * to _widget_NAME
    *
    * Straight maps all handles to 'this object'
    *
    * #TODO  - base the widgets on 'documented/defined widgets in this file' rather than 
    * grepping the glade file...
    *
    * @access private
    */ 
    
    function _loadGlade() {
        
        $file = dirname(__FILE__).'/Gtk/installer.glade';
        if (!class_exists('GladeXML')) 
            return PEAR::raiseError('Glade is currently required for the installer to work, please see php GTK install instructions for more information',null,PEAR_ERROR_DIE);
         
        $this->_glade = &new GladeXML($file);
        $data = implode('',file($file));
        preg_match_all('/\<name\>([^\<]+)\<\/name\>/',$data,$items);
        foreach ($items[1] as $widgetname) {   
            $args = array();
            if (preg_match('/^(_install|_packages|_summary|_config|_dirselect|_info|_documentation)_(.*)$/',$widgetname,$args)) {
                $obj = $args[1];
                $varname= $args[2];
                //echo "ASSIGN $obj $varname to $widgetname\n";
                $this->$obj->$varname = $this->_glade->get_widget($widgetname);
                continue;
            }
            $varname = "_widget_".$widgetname;
            $this->$varname = $this->_glade->get_widget($widgetname);
        }
        $items = array();
        preg_match_all('/\<handler\>([^\<]+)\<\/handler\>/',$data,$items); 
        //print_r($items[1]);
        foreach ($items[1] as $handler)  {
            $args = array();
            if (preg_match('/^(_install|_packages|_summary|_config|_dirselect|_info|_documentation)_(.*)$/',$handler,$args)) {
                $obj = $args[1];
                $method= $args[2];
                //echo "CONNECT $obj $method to $handler\n";
                if (!method_exists($this->$obj,$method)) 
                    exit; // programming error!
                $this->_glade->signal_connect($handler ,array(&$this->$obj,$method));
                continue;
            }
            if (method_exists($this,$handler))
                $this->_glade->signal_connect( $handler ,array(&$this,$handler));
        }

        
        
    }
    /**
     * the class that manages the class list
     * @var object PEAR_Frontend_Gtk_Packages
     * @access private
     */
    var $_packages; 
    
      /**
     * the class that manages the Package summary
     * @var object PEAR_Frontend_Gtk_Summray
     * @access private
     */
    var $_summary; 
      /**
     * the class manages package installation
     * @var object PEAR_Frontend_Gtk_Install
     * @access private
     */
    var $_install; 
 
    /**
     * the class manages directory selection
     * @var object PEAR_Frontend_Gtk_DirSelect
     * @access private
     */
    var $_dirselect; 
    
     /**
     * the class manages simple info prompts
     * @var object PEAR_Frontend_Gtk_Info
     * @access private
     */
    var $_info; 
    
    /**
    * Load and initialize the sub modules
    *
    * @access private
    */ 
    
    
    function _initInterface() {
        // must be a better way - still needs to read -c -C optss
        // initialize child objects
      
        $this->_widget_window->connect_after('realize',array(&$this,'_callbackWindowConfigure'));
        $this->_widget_window->connect_after('configure_event',array(&$this,'_callbackWindowConfigure'));

        $this->_widget_details_area->hide();
        $this->_widget_window->show();
    
    }
    /**
    * has the window been configured (eg. pixmaps loaded etc.)
    * @var boolean
    * @access private
    */
    var $_windowConfiguredFlag = FALSE;
      
    /**
    * Set up images, styles etc.
    *
    * @param object gtkwindow 
    * @access private
    */ 
    function _callbackWindowConfigure($window) {
        // must be a better way - still needs to read -c -C optss
        
        
        $this->_initPixmaps($window);
        
        if ($this->_windowConfiguredFlag) return;
        $this->_windowConfiguredFlag = TRUE;
        /* main package selection tab */
        $this->_setStyle('nav_bar','','#7b7d7a',FALSE);
    
        $this->_setStyle('pear_installer_button','#000000','#7b7d7a');
        $this->_setStyle('config_button','#000000','#7b7d7a');
        $this->_setStyle('documentation_button','#000000','#7b7d7a');
        
        /* package stuff */
         
        $this->_packages->loadPackageList();
        
        $this->_setStyle('black_bg1','#FFFFFF','#000000',FALSE);
        $this->_setStyle('black_bg2','#FFFFFF','#000000',FALSE);
        $this->_setStyle('black_bg3','#FFFFFF','#000000',FALSE);
        $this->_setStyle('black_bg4','#FFFFFF','#000000',TRUE);
        
        //$this->_setStyle('download_list','#000000','#FFFFFF',TRUE);
        
        //$this->_widget_close_details->set_style($newstyle);
        $this->_setStyle('close_details','#FFFFFF','#000000',FALSE);
        $this->_loadButton('close_details' ,        'black_close_icon.xpm',TRUE);
        
        
         
        
        // sort out the text.
        $this->_setStyle('summary'   ,'#FFFFFF','#000000',TRUE);
        $this->_setFont('summary','-*-helvetica-bold-r-normal-*-*-80-*-*-p-*-iso8859-1'); 
        
        $this->_setStyle('black_msg1','#FFFFFF','#000000',FALSE);
        
        $newstyle = &new GtkStyle();
        $this->_widget_packages_install->set_style($newstyle);
        
        $this->_loadButton('pear_installer_button' ,'nav_installer.xpm');
        $this->_loadButton('config_button' ,        'nav_configuration.xpm');
        $this->_loadButton('documentation_button' , 'nav_documentation.xpm');
        
        //$this->_setStyleWidget($pixmap,'#000000','#339900',FALSE);
        
        
        $this->_setStyle('package_logo','#000000','#339900',TRUE);
        $this->_setStyle('package_logo_text','#FFFFFF','#339900'); 
        $this->_setFont('package_logo_text','-*-helvetica-bold-r-normal-*-*-100-*-*-p-*-iso8859-1'); 
        
         
        
        $package_logo = &new GtkPixmap(
            $this->_pixmaps['pear.xpm'][0],
            $this->_pixmaps['pear.xpm'][1]);
        $this->_widget_package_logo->put($package_logo,0,0);
        $package_logo->show();
        
        
        /* downloding tab */
        $this->_setStyle('white_bg1','#000000','#FFFFFF',TRUE);
        $download_icon  = &new GtkPixmap(
            $this->_pixmaps['downloading_image.xpm'][0],
            $this->_pixmaps['downloading_image.xpm'][1]);
        $this->_widget_white_bg1->put($download_icon,0,0);
        $download_icon->show();
        $this->_setStyle('downloading_logo','#000000','#339900',TRUE);
        $this->_setStyle('downloading_logo_text','#FFFFFF','#339900'); 
        $this->_setFont('downloading_logo_text','-*-helvetica-bold-r-normal-*-*-100-*-*-p-*-iso8859-1'); 
        $this->_widget_downloading_logo_text->set_justify( GTK_JUSTIFY_LEFT );
        $installer_logo = &new GtkPixmap(
            $this->_pixmaps['pear.xpm'][0],
            $this->_pixmaps['pear.xpm'][1]);
        $this->_widget_downloading_logo->put($installer_logo,0,0);
        $installer_logo->show();
        
        /* configuration loading */
        
        $this->_config->loadConfig();
       
        $this->_setStyle('config_logo','#000000','#339900',TRUE);
        $this->_setStyle('config_logo_text','#FFFFFF','#339900'); 
        $this->_setFont('config_logo_text','-*-helvetica-bold-r-normal-*-*-100-*-*-p-*-iso8859-1'); 
        $config_logo = &new GtkPixmap(
            $this->_pixmaps['pear.xpm'][0],
            $this->_pixmaps['pear.xpm'][1]);
        
        $this->_widget_config_logo->put($config_logo,0,0);
        $config_logo->show();
        
        
        /* documentation loading */
        $this->_setStyle('documentation_logo','#000000','#339900',TRUE);
        $this->_setStyle('documentation_logo_text','#FFFFFF','#339900'); 
        $this->_setFont('documentation_logo_text','-*-helvetica-bold-r-normal-*-*-100-*-*-p-*-iso8859-1'); 
        $documentation_logo = &new GtkPixmap(
            $this->_pixmaps['pear.xpm'][0],
            $this->_pixmaps['pear.xpm'][1]);
        
        $this->_widget_documentation_logo->put($documentation_logo,0,0);
        $documentation_logo->show();
        $this->_documentation->init();
        $this->_setStyle('documentation_view_label'   ,'#FFFFFF','#000000',TRUE);
        $this->_setFont('documentation_view_label','-*-helvetica-bold-r-normal-*-*-80-*-*-p-*-iso8859-1'); 
        $this->_setStyle('documentation_package_label'   ,'#FFFFFF','#000000',TRUE);
        $this->_setFont('documentation_package_label','-*-helvetica-bold-r-normal-*-*-80-*-*-p-*-iso8859-1'); 
        
         
    }
   /**
    * Set up images, styles etc.
    * Load an image into a button as glade does not support this!
    *
    * @param string widget name 
    * @param string  icon name
    * @param boolean relief - the left menu is FALSE, other buttons are TRUE
    * @access private
    */ 
    
    function &_loadButton($widgetname, $icon, $relief=FALSE) {
        //echo $widgetname;
        $widget_fullname = "_widget_". $widgetname;
        $widget = &$this->$widget_fullname;
        
        $child = $widget->child;
        //if ($child)
        //    if (get_class($child) == "GtkVBox") return;
        if (!$relief) 
            $widget->set_relief(GTK_RELIEF_NONE);
          
        //$widget->set_usize(150,100);
        $vbox = new GtkVBox; 
        
      // this stuff only gets done once
        if ($child)
            $widget->remove($child);
        $pixmap = &new GtkPixmap($this->_pixmaps[$icon][0],$this->_pixmaps[$icon][1]);
        $vbox->pack_start( $pixmap, true  , true  , 2);
        if ($child)
            $vbox->pack_end($child,true,true,2);
        $widget->add($vbox);
        //$widget->set_usize(150,100);
        $vbox->show();
        $pixmap->show();
        return $vbox;
     
    }
    
    /**
    * Funky routine to set the style(colours) of Gtkwidgets
    * 
    *
    * @param string widget name 
    * @param string  foreground color
    * @param string  background color
    * @param booloean  Base style of (TRUE)existing or create a new style(FALSE)
    * @access private
    */ 
    function _setStyle($widgetname,$fgcolor='',$bgcolor='',$copy=FALSE) {
        //echo "SET: $widgetname: $fgcolor/$bgcolor ". ((int) $copy) . "\n";
        $widget_fullname = "_widget_". $widgetname;
      
        $this->_setStyleWidget($this->$widget_fullname,$fgcolor,$bgcolor,$copy) ;
    }
    function _setStyleWidget(&$widget,$fgcolor='',$bgcolor='',$copy=FALSE) {
        if ($copy) {
            $oldstyle = $widget->get_style();
            $newstyle = $oldstyle->copy();
        } else {
            $newstyle = &new GtkStyle();
        }
        if ($fgcolor) { // set foreground color
            $fg = &new GdkColor($fgcolor);
            $newstyle->fg[GTK_STATE_PRELIGHT] = $fg;
            $newstyle->fg[GTK_STATE_NORMAL] = $fg;
            $newstyle->fg[GTK_STATE_ACTIVE] = $fg;
            $newstyle->fg[GTK_STATE_SELECTED] = $fg;
            $newstyle->fg[GTK_STATE_INSENSITIVE] = $fg;
            //$newstyle->bg_pixmap=NULL;
        }
        if ($bgcolor) { // set background color

            $bg = &new GdkColor($bgcolor);
            $newstyle->bg[GTK_STATE_PRELIGHT] = $bg;
            $newstyle->bg[GTK_STATE_NORMAL] = $bg;
            $newstyle->bg[GTK_STATE_ACTIVE] = $bg;
            $newstyle->bg[GTK_STATE_SELECTED] = $bg;
            $newstyle->bg[GTK_STATE_INSENSITIVE] = $bg;
            //$newstyle->bg_pixmap=NULL;
        }
        $widget->set_style($newstyle);
    
    
    }
    
    function _setFont($widgetname,$fontname) {
        $font = gdk::font_load($fontname);
        $widget_fullname = "_widget_". $widgetname;
        $widget = &$this->$widget_fullname;
        $oldstyle = $widget->get_style();
        $newstyle = $oldstyle->copy();
        $newstyle->font = $font;
        $widget->set_style($newstyle);
    }

    
    /**
    * All the pixmaps from the xpm directory
    * @var boolean
    * @access private
    */
    var $_pixmaps = array(); // associative array of filename -> pixmaps|mask array objects used by application
    
    /*
    * initialize the pixmaps - load the into $this->_pixmaps[name][0|1]
    *  
    * @param object gtkwindow the window from the realize event.
    */
    function _initPixmaps(&$window) {
        
        if ($this->_pixmaps) return;
        $dir = dirname(__FILE__).'/Gtk/xpm';
        $dh = opendir($dir);
        if (!$dh) return;
        while (($file = readdir($dh)) !== FALSE) {
            if (@$file{0} == '.') continue;
            if (!preg_match('/\.xpm$/',$file)) continue;
            //echo "loading {$dir}/{$file}";
            $this->_pixmaps[$file] =  
                Gdk::pixmap_create_from_xpm($window->window, NULL, "{$dir}/{$file}");
                
        }
    }
     
  
    /*
    * Menu Callback - Exit/Quit
    */
    function _onQuit() {
        gtk::main_quit();
        exit;
    }
    /*
    * Left button callback - goto installer
    */
    function _callbackShowInstaller() {
        $this->_widget_pages->set_page(0);
    }
    /*
    * Left button callback - goto config page
    */
    
    function _callbackShowConfig() {
        $this->_widget_pages->set_page(2);
    }
    /*-------------------------------------Downloading --------------------------------*/
     /*
    * PEAR_Command Callback - used by downloader
    * @param string message type
    * @param string message data
    */
    
    function _downloadCallback($msg,  $params) {
        $this->_install->_downloadCallback($msg,  $params);
    }
    
     /*---------------- Dir Seleciton stuff -----------------------*/
    
   
    
    //-------------------------- BASE Installer methods --------------------------
     /**
    * Callback from command API, that sends data back from config-show
    *
    * @param   mixed data requeted by another part of this program
    * @param   string the command that was sent to result in this
    *
    */
    function outputData($data,$command=''){
        switch ($command) {
            case 'config-show':
                $this->_config->buildConfig($data['data']); 
                break;
            case 'uninstall':
                $this->_install->uninstallOutputData($data);
                return;
            default:
                $this->_info->show($data['data']);
                //echo "COMMAND : $command\n";
                //echo "DATA: ".print_r($data,true)."\n";
        }
    }

    function log($msg,$command='') {
        
        return $this->_info->show("LOG: $msg: $command");
    }

    // {{{ displayLine(text)

    function displayLine($msg,$args)
    {
       return $this->_info->show("DISPLAYLINE: $msg: $args");
    }

    function display($text,$command='')
    {
     return $this->_info->show("TEXT: $text: $command");
    }

    // }}}
    // {{{ displayError(eobj)

    function displayError($eobj)
    {
        //echo "ERROR: ".$eobj->getMessage();
        return $this->_info->show("ERROR: ". $eobj->getMessage()); 
        
    }

    // }}}
    // {{{ displayFatalError(eobj)

    function displayFatalError($eobj)
    {
          if (!$this->_info) {
	  	echo "FATAL ERROR: ". $eobj->getMessage();
		exit;
	  }
	  return $this->_info->show("FATAL ERROR: ". $eobj->getMessage()); 
        //exit(1);
    }

    // }}}
    // {{{ displayHeading(title)

    function displayHeading($title)
    {
    }

    // }}}
    // {{{ userDialog(prompt, [type], [default])

    function userDialog($prompt, $type = 'text', $default = '')
    {
        
        echo "Dialog?" . $prompt;
    }

    // }}}
    // {{{ userConfirm(prompt, [default])

    function userConfirm($prompt, $default = 'yes')
    {
          echo "\nConfirm?" . $prompt;
    }

    // }}}
    // {{{ startTable([params])

    function startTable($params = array())
    {
    }

    // }}}
    // {{{ tableRow(columns, [rowparams], [colparams])

    function tableRow($columns, $rowparams = array(), $colparams = array())
    {
    }

    // }}}
    // {{{ endTable()

    function endTable()
    {
    }

    // }}}
    // {{{ bold($text)

    function bold($text)
    {
    echo "\nBOLD?" . $prompt;
    }

    // }}}
}

?>