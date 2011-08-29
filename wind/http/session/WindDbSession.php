<?php
/**
 * @author Qian Su <aoxue.1988.su.qian@163.com> 2011-3-8
 * @link http://www.phpwind.com
 * @copyright Copyright &copy; 2003-2110 phpwind.com
 * @license 
 */
Wind::import('WIND:component.http.session.AbstractWindUserSession');
/**
 * 数据库会话存储机制,可以实现统一登陆
 * the last known user to change this file in the repository  <$LastChangedBy$>
 * @author Qian Su <aoxue.1988.su.qian@163.com>
 * @version $Id$ 
 * @package 
 */
class WindDbSession extends AbstractWindUserSession {
	
	public static  function open($savePath, $sessionName){
		return true;
	}
	
	public static  function close(){
		return true;
	}
	
	public static  function write($name,$value){
		
	}
	
	public static  function read($name){
		
	}
	
	public static  function gc($maxlifetime){
		
	}
	
	public static  function destroy($name){
		
	}
}
