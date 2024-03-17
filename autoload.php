<?php
if (file_exists('lib/core/vendor/autoload.php')) {
	require 'lib/core/vendor/autoload.php';
}

spl_autoload_register(function ($class) {

	// load Config file
	if ($class === 'Config') {
		require __DIR__ . '/config/Config.php';
		return;
	}
	$prefix = 'PS\\';
	$base_dir = __DIR__ . '/lib/';
	$len = strlen($prefix);

	if (strncmp($prefix, $class, $len) !== 0) {
		return;
	}

	$class = str_replace('\\Source\\', '\\src\\', $class);
	$class_name = str_replace($prefix, '', $class);
	$arrPath = explode('\\', $class_name);
	$filename = $arrPath[count($arrPath) - 1];
	$class_name = implode(DIRECTORY_SEPARATOR, array_splice($arrPath, 0, -1));
	$file = $base_dir . str_replace('\\', '/', strtolower($class_name)) . DIRECTORY_SEPARATOR . $filename . '.php';
	if (file_exists($file)) {
		require $file;
	}
});
