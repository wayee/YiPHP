<?php defined('SYSPATH') or die('No direct access');

/**
 * Http异常处理类
 * 
 * @author Andy Cai (huayicai@gmail.com)
 *
 */
class CHttpException extends Exception
{
	/**
	 * @var integer HTTP status code, such as 403, 404, 500, etc.
	 */
	public $statusCode;

	/**
	 * Constructor.
	 * @param integer $status HTTP status code, such as 404, 500, etc.
	 * @param string $message error message
	 * @param integer $code error code
	 */
	public function __construct($status, $message=null, $code=0)
	{
		$this->statusCode=$status;
		parent::__construct($message,$code);
	}
} // End CHttpException