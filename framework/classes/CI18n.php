<?php defined('SYSPATH') or die('No direct script access.');

/**
 * 多语言管理
 * 
 * @author Andy Cai (huayicai@gmail.com)
 * 
 */
class CI18n
{
	/**
	 * @var  string   target language: en-us, es-es, zh-cn, etc
	 */
	public static $lang = 'zh-cn';
	
	/**
	 * @var  string  source language: en-us, es-es, zh-cn, etc
	 */
	public static $source = 'zh-cn';

	/**
	 * @var  array  cache of loaded languages
	 */
	protected static $_cache = array();
	
	/**
	 * Get and set the target language.
	 *
	 *     // Get the current language
	 *     $lang = Lib_I18n::lang();
	 *
	 *     // Change the current language to Spanish
	 *     Lib_I18n::lang('es-es');
	 *
	 * @param   string   new language setting
	 * @return  string
	 */
	public static function lang($lang = NULL)
	{
		if ($lang) {
			// Normalize the language
			self::$lang = strtolower(str_replace(array(' ', '_'), '-', $lang));
		}

		return self::$lang;
	}
	
	public static function get($string, $lang = NULL)
	{
		if ( ! $lang) {
			// Use the global target language
			$lang = self::$lang;
		}

		// Load the translation table for this language
		$table = self::load($lang);

		// Return the translated string if it exists
		return isset($table[$string]) ? $table[$string] : $string;
	}
	
	public static function load($lang)
	{
		if (isset(self::$_cache[$lang])) {
			return self::$_cache[$lang];
		}

		// New translation table
		$table = array();

		$table = include APPPATH . 'i18n' . DS . $lang . EXT;

		// Cache the translation table locally
		return self::$_cache[$lang] = $table;
	}
} // End CI18n