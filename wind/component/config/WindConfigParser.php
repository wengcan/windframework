<?php
/**
 * @author xiaoxia xu <x_824@sina.com> 2010-11-22
 * @link http://www.phpwind.com
 * @copyright Copyright &copy; 2003-2110 phpwind.com
 * @license 
 */
L::import("WIND:core.base.IWindConfig");

/**
 * 配置文件解析类
 * 配置文件格式允许有3中格式：xml,properties,ini
 * 
 * 配置默认放在应用程序跟路径下面，解析生成的配置缓存文件默认放在‘COMPILE_PATH’下面
 * 如果‘$userAppConfig’文件中有定义了解析生成的配置文件存放路径则放置在该路径下面
 * 
 * the last known user to change this file in the repository  <$LastChangedBy$>
 * @author xiaoxia xu <x_824@sina.com>
 * @version $Id$ 
 * @package
 */
class WindConfigParser implements IWindConfig {
	private $defaultPath = WIND_PATH;
	private $defaultConfig = 'wind_config.xml';
	
	private $userAppConfig = 'config.xml';
	
	private $globalAppsPath = COMPILE_PATH;
	private $globalAppsConfig = 'config.php';
	
	private $parser = null;
	private $parserEngine = 'xml';
	private $configExt = array('xml', 'properpoties', 'ini');
	
	private $encoding = 'gbk';
	private $defaultGAM = array();
	private $userGAM = array();
	
	private $currentApp = '';
	
	/**
	 * 初始化
	 * @param String $outputEncoding	//编码信息
	 */
	public function __construct($outputEncoding = 'gbk') {
		$this->currentApp = W::getCurrentApp();
		if ($outputEncoding) $this->encoding = $outputEncoding;
	}
	
	/**
	 * @param WindHttpRequest $request  //请求信息
	 */
	public function parser($request) {
		$rootPath = dirname($request->getServer('SCRIPT_FILENAME'));
		if ($this->isCompiled()) {
			
		} else {
			$userConfigPath = $rootPath . D_S . $this->userAppConfig;
			$userConfig = $this->_parser($userConfigPath);
			
			$defaultConfigPath = $this->defaultPath . D_S . $this->defaultConfig;
			$defaultConfig = $this->_parser($defaultConfigPath);
		}
		
	}
	
	/**
	 * 接收一个配置文件路径，根据路径信息初始化配置解析器，并解析该配置
	 * 以数组格式返回配置解析结果
	 * 
	 * @param string $configFile
	 * @return array
	 */
	private function _parser($configFile) {
		list(, $fileName, $ext, $realPath) = L::getRealPath($configFile, true);
		if (!$realPath) return array();
		if ($this->parser === null) {
			$this->initParser($ext);
			$this->parser->setOutputEncoding($this->encoding);
		}
		$this->parser->setXMLFile($realPath);
		$this->parser->parser();
		return $this->parser->getResult();
	}
	
	/**
	 * 返回是否需要执行解析过程
	 * 如果Compile文件夹为被定义或不可写则返回false
	 * 如果config.php文件不存在则返回false
	 * 如果当期app信息不存在则返回false
	 * 如果当前app的配置文件不存在则返回false
	 */
	private function isCompiled() {

	}
	
	/**
	 * 处理配置文件
	 * 根据在IWindConfig中的设置对相关配置项进行合并/覆盖
	 * 如果应用配置中没有配置相关选项，则使用默认配置中的选项
	 * 如果是需要合并的项，则将缺省项和用户配置项进行合并
	 * 
	 * @param array $defaultConfig 默认的配置文件
	 * @param array $appConfig 应用的配置文件
	 * @return array 返回处理后的配置文件
	 */
	public function mergeConfig($defaultConfig, $appConfig) {
		if (count($appConfig) == 0) $appConfig = $defaultConfig;
		$app = $appConfig[IWindConfig::APP];
		(!isset($app[IWindConfig::APP_NAME]) || $app[IWindConfig::APP_NAME] == '') && $app[IWindConfig::APP_NAME] = $this->getAppName();
		(!isset($app[IWindConfig::APP_ROOTPATH]) || $app[IWindConfig::APP_ROOTPATH] == '') && $app[IWindConfig::APP_ROOTPATH] = realpath($this->userAppPath);
		$_file = '/' . $app[IWindConfig::APP_NAME] . '_config.php';
		if (!isset($app[IWindConfig::APP_CONFIG])) {
			$app[IWindConfig::APP_CONFIG] = $this->globalAppsPath . $_file;
		} else {
			$app[IWindConfig::APP_CONFIG] = $this->getRealPath($app[IWindConfig::APP_NAME], $app[IWindConfig::APP_ROOTPATH], $app[IWindConfig::APP_CONFIG]) . $_file;
		}
		$appConfig[IWindConfig::APP] = $app;
		
		$_merge = $this->getGAM(IWindConfig::MERGEATTR);
		$hasInDefaultConfigKeys = array();
		foreach ($appConfig as $key => $value) {
			if (in_array($key, $_merge) && isset($defaultConfig[$key])) {
				!is_array($value) && $value = array($value);
				!is_array($defaultConfig[$key]) && $defaultConfig[$key] = array($defaultConfig[$key]);
				$appConfig[$key] = array_merge($value, $defaultConfig[$key]);
			}
			(!isset($defaultConfig[$key])) && $hasInDefaultConfigKeys[] = $key;
		}
		//将应用配置中不缺省的项填充到应用配置中；
		$appConfigKeys = array_keys($appConfig);
		$_notInAppConfig = array_diff(array_keys($defaultConfig), $hasInDefaultConfigKeys);
		foreach ($_notInAppConfig as $key) {
			if (in_array($key, $appConfigKeys)) continue;
			$appConfig[$key] = $defaultConfig[$key];
		}
		$this->writeover($app[IWindConfig::APP_CONFIG], "<?php\r\n return " . $this->varExport($appConfig) . ";\r\n?>");
		$this->addGlobalArray($appConfig);
		return $appConfig;
	}
	
	private function getGAM($key) {
		$_tmp1 = isset($this->userGAM[$key]) ? $this->userGAM[$key] : array();
		$_tmp2 = isset($this->defaultGAM[$key]) ? $this->defaultGAM[$key] : array();
		if ($_tmp1 && $_tmp2) return array_merge($_tmp1, $_tmp2);
		if ($_tmp1) return $_tmp1;
		return $_tmp2;
	}
	
	/**
	 * 将全局内容从数组中找出，并添加到缓存文件中
	 * @param array $config
	 */
	private function addGlobalArray($config) {
		$_global = $this->getGAM(IWindConfig::GLOBALATTR);
		$_globalArray = array();
		foreach ($_global as $key) {
			isset($config[$key]) && $_globalArray[$key] = $config[$key];
		}
		$this->addAppsConfig($_globalArray);
		return true;
	}
	
	/**
	 * 将该应用的相关配置merge到全局应有配置中
	 * 当前应用：如果没有配置应用的名字，则将当前访问的最后一个位置设置为应用名称
	 * 否则使用配置中配置好的应用名字。
	 * 添加缓存
	 * 
	 * @param array $config  当前应用的应用配置信息
	 * @return array 返回修改后的应用配置信息
	 */
	public function addAppsConfig($config) {
		$sysConfig = array();
		if (is_file($this->globalAppsConfig)) {
			include ($this->globalAppsConfig);
		}
		//不存在，则创建
		$appName = isset($config[IWindConfig::APP_NAME]) ? $config[IWindConfig::APP_NAME] : $this->getAppName();
		$sysConfig = array($sysConfig, $config);
		$this->writeover($this->globalAppsConfig, "<?php\r\n return " . $this->varExport($sysConfig) . ";\r\n?>");
		return $sysConfig;
	}
	
	/**
	 * 初始化配置文件解析器
	 * @param string $parser
	 */
	private function initParser($parser = 'xml') {
		switch ($parser) {
			case 'XML':
				L::import("WIND:component.config.WindXMLConfig");
				$this->parser = new WindXMLConfig();
				break;
			default:
				throw new WindException('init config parser error.');
				break;
		}
	}
	
	/**
	 * 获得当前应用的名字，解析路径的最后一个文件夹
	 * 
	 * @return string 返回符合的项
	 */
	private function getAppName() {
		if ($this->currentApp != '') return $this->currentApp;
		$path = rtrim(rtrim($this->userAppPath, '\\'), '/');
		$pos = (strrpos($path, '\\') === false) ? strrpos($path, '/') : strrpos($path, '\\');
		return substr($path, -(strlen($path) - $pos - 1));
	}
	
	/**
	 * 变量导出为字符串
	 *
	 * @param mixed $input 变量
	 * @param string $indent 缩进
	 * @return string
	 */
	public function varExport($input, $indent = '') {
		switch (gettype($input)) {
			case 'string':
				return "'" . str_replace(array("\\", "'"), array("\\\\", "\'"), $input) . "'";
			case 'array':
				$output = "array(\r\n";
				foreach ($input as $key => $value) {
					$output .= $indent . "\t" . $this->varExport($key, $indent . "\t") . ' => ' . $this->varExport($value, $indent . "\t");
					$output .= ",\r\n";
				}
				$output .= $indent . ')';
				return $output;
			case 'boolean':
				return $input ? 'true' : 'false';
			case 'NULL':
				return 'NULL';
			case 'integer':
			case 'double':
			case 'float':
				return "'" . (string) $input . "'";
		}
		return 'NULL';
	}
	
	/**
	 * 写文件
	 *
	 * @param string $fileName 文件绝对路径
	 * @param string $data 数据
	 * @param string $method 读写模式
	 * @param bool $ifLock 是否锁文件
	 * @param bool $ifCheckPath 是否检查文件名中的“..”
	 * @param bool $ifChmod 是否将文件属性改为可读写
	 * @return bool 是否写入成功
	 */
	public function writeover($fileName, $data, $method = 'rb+', $ifLock = true, $ifCheckPath = true, $ifChmod = true) {
		$tmpname = strtolower($fileName);
		$tmparray = array(':\/\/', "\0");
		$tmparray[] = '..';
		if (str_replace($tmparray, '', $tmpname) != $tmpname) return false;
		
		@touch($fileName);
		if (!$handle = @fopen($fileName, $method)) return false;
		$ifLock && flock($handle, LOCK_EX);
		$writeCheck = fwrite($handle, $data);
		$method == 'rb+' && ftruncate($handle, strlen($data));
		fclose($handle);
		$ifChmod && @chmod($fileName, 0777);
		return $writeCheck;
	}
}