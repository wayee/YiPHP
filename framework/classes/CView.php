<?php defined('SYSPATH') or die('No direct script access.');

/**
 * 视图类
 * 
 * @author Andy Cai (huayicai@gmail.com)
 * 
 */
class CView implements IView
{
	const VIEW_EXT = '.html';
	
	protected static $_globalData = array();

	public static function factory($file = NULL, array $data = NULL)
	{
		return new CView($file, $data);
	}

	protected static function capture($viewFilename, array $viewData)
	{
// 		print_r($viewData);die();
		// Import the view variables to local namespace
		extract($viewData, EXTR_SKIP);

		if (self::$_globalData) {
			// Import the global view variables to local namespace
			extract(self::$_globalData, EXTR_SKIP);
		}

		// Capture the view output
		ob_start();

		try {
			// Load the view within the current scope
			include($viewFilename);
		} catch (Exception $e) {
			// Delete the output buffer
			ob_end_clean();

			// Re-throw the exception
			throw $e;
		}

		// Get the captured output and close the buffer
		return ob_get_clean();
	}

	public static function set_global($key, $value = NULL)
	{
		if (is_array($key)) {
			foreach ($key as $key2 => $value) {
				self::$_globalData[$key2] = $value;
			}
		}
		else
		{
			self::$_globalData[$key] = $value;
		}
	}
	public static function bind_global($key, & $value)
	{
		self::$_globalData[$key] =& $value;
	}
	
	// End 静态方法

	protected $_file;
	protected $_data = array();

	public function __construct($file = NULL, array $data = NULL)
	{
		if ($file !== NULL) {
			$this->setFilename($file);
		}

		if ($data !== NULL) {
			// Add the values to the current data
			$this->_data = $data + $this->_data;
		}
	}

	public function & __get($key)
	{
		if (array_key_exists($key, $this->_data)) {
			return $this->_data[$key];
		} elseif (array_key_exists($key, self::$_globalData)) {
			return self::$_globalData[$key];
		} else {
			throw new ErrorException('View variable is not set: :var',
				array(':var' => $key));
		}
	}

	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	public function __isset($key)
	{
		return (isset($this->_data[$key]) OR isset(self::$_globalData[$key]));
	}

	public function __unset($key)
	{
		unset($this->_data[$key], self::$_globalData[$key]);
	}

	public function __toString()
	{
		try {
			return $this->render();
		} catch (Exception $e) {
			// Display the exception message
			Lib_CException::handler($e);

			return '';
		}
	}

	public function setFilename($file)
	{
		$path = Yi::$themePath . DS . 'themes' . DS . Yi::$theme . DS . $file . self::VIEW_EXT;
		
		if ( ! is_file($path) ) {
			throw new ErrorException('The requested view :file could not be found', array(
				':file' => $file,
			));
		}

		// Store the file path locally
		$this->_file = $path;

		return $this;
	}

	public function set($key, $value = NULL)
	{
		if (is_array($key)) {
			foreach ($key as $name => $value) {
				$this->_data[$name] = $value;
			}
		} else {
			$this->_data[$key] = $value;
		}

		return $this;
	}

	public function bind($key, & $value)
	{
		$this->_data[$key] =& $value;

		return $this;
	}

	public function render($file = NULL)
	{
		if ($file !== NULL) {
			$this->setFilename($file);
		}

		if (empty($this->_file)) {
			throw new ErrorException('You must set the file to use within your view before rendering');
		}

		// Combine local and global data and capture the output
		return self::capture($this->_file, $this->_data);
	}

} // End CView