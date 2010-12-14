<?php
/**
 * @author Qiong Wu <papa0924@gmail.com> 2010-11-3
 * @link http://www.phpwind.com
 * @copyright Copyright &copy; 2003-2110 phpwind.com
 * @license 
 */

L::import('WIND:component.router.base.WindRouter');
L::import('WIND:component.exception.WindException');
/**
 * 基于URL的路由解析器.
 * 该解析器通过访问一个Http请求的Request对象来获得URL的参数信息
 * 并将其参数根据已定义的路由规则进行解析.
 * 通过该方法的getActionHandle方法返回一个，操作处理的句柄信息
 * 
 * the last known user to change this file in the repository  <$LastChangedBy$>
 * @author Qiong Wu <papa0924@gmail.com>
 * @version $Id$ 
 * @link WRouteParser
 * @package 
 */
class WindUrlBasedRouter extends WindRouter {
	private $request = null;
	private $response = null;
	private $rule;
	/**
	 * 调用该方法实现路由解析
	 * 获得到 request 的静态对象，得到request的URL信息
	 * 获得 config 的静态对象，得到URL的格式信息
	 * 解析URL，并声称RouterContext对象
	 * @param WindHttpRequest $request
	 * @param WindHttpResponse $response
	 */
	public function doParser($request, $response) {
		$this->_setValues($request, $response);
	}
	
	/**
	 * @param string $action
	 * @param string $controller
	 * @param string $module
	 * @param string $args 
	 */
	public function buildUrl($action = '', $controller = '', $module = '') {
		$keys = array_keys($this->rule);
		$baseUrl = $this->request->getBaseUrl(true);
		$script = $this->request->getScript();
		$url = '';
		if ($action && $action !== $this->rule[$keys[0]]) $url .= '&' . $keys[0] . '=' . $action;
		if ($controller && $controller !== $this->rule[$keys[1]]) $url .= '&' . $keys[1] . '=' . $controller;
		if ($module && $module !== $this->rule[$keys[2]]) $url .= '&' . $keys[2] . '=' . $module;
		$url = $baseUrl . '/' . $script . '?' . trim($url, '&');
		return $url;
	}
	
	/**
	 * @param WindHttpRequest $request
	 * @param WindHttpResponse $response
	 */
	private function _setValues($request, $response) {
		$this->rule = $this->routerConfig[IWindConfig::ROUTER_PARSERS_RULE];
		$keys = array_keys($this->rule);
		$this->action = $request->getAttribute($keys[0], $this->rule[$keys[0]]);
		$this->controller = $request->getAttribute($keys[1], $this->rule[$keys[1]]);
		$this->module = $request->getAttribute($keys[2], $this->rule[$keys[2]]);
		$response->setDispatcher($this);
		$this->request = $request;
		$this->response = $response;
	}
}