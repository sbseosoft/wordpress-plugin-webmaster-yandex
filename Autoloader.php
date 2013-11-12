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

class Autoloader {
    public static function loadClass($class) {
        set_include_path(plugin_dir_path(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . PATH_SEPARATOR . get_include_path());
        $files = array(
            $class . '.php',
            str_replace('_', '/', $class) . '.php',
        );
        foreach (explode(PATH_SEPARATOR, ini_get('include_path')) as $base_path) {
            foreach ($files as $file) {
                $path = "$base_path/$file";
                if (file_exists($path) && is_readable($path)) {
                    include_once $path;
                    return;
                }
            }
        }
    }
}