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

/*
 * Plugin Name: Webmaster Yandex
 * Plugin URI: http://www.sbseosoft.com/development/web/wordpress-plugins/webmaster-yandex/
 * Description: Add your website to Yandex Webmaster service. Send new text content to Yandex, to prevent it from stealing by others.
 * Version: 0.1
 * Author: Sbseosoft
 * Author URI: http://www.sbseosoft.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

register_activation_hook(__FILE__, 'wm_ya_db_install');
register_uninstall_hook(__FILE__, 'wm_ya_db_uninstall');
require_once('Autoloader.php');
spl_autoload_register(array('Autoloader', 'loadClass'));
$wm = new WebmasterYandex();

function wm_ya_db_install() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $tableName = $wpdb->prefix . 'wm_ya_texts';
    $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
            `id` INT NOT NULL AUTO_INCREMENT ,
            `timestamp_added` INT NULL ,
            `post_id` INT NULL ,
            `yandex_text_id` VARCHAR(255) NULL ,
            `yandex_link` VARCHAR(255) NULL ,
            PRIMARY KEY (`id`) ,
            UNIQUE INDEX `post_id` (`post_id` DESC) ,
            INDEX `yandex_id` (`yandex_text_id` ASC) );
          ";
    dbDelta($sql);
    $tableName = $wpdb->prefix . 'wm_ya_stat_texts';
    $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
            `id` INT NOT NULL AUTO_INCREMENT ,
            `date` DATE NULL ,
            `texts_sent` INT NULL ,
            PRIMARY KEY (`id`) ,
            UNIQUE INDEX `date` (`date` DESC) );
          ";
    dbDelta($sql);
}

function wm_ya_db_uninstall() {
    global $wpdb;
    $sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'wm_ya_texts';
    $wpdb->query($sql);
    $sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'wm_ya_stat_texts';
    $wpdb->query($sql);
}