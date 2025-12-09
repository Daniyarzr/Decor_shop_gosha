<?php
/**
 * Система кэширования для сайта
 */

class Cache {
    private static $cacheDir = __DIR__ . '/../cache/';
    private static $defaultTTL = 3600; // 1 час по умолчанию
    
    /**
     * Инициализация директории кэша
     */
    public static function init() {
        if (!file_exists(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
        if (!file_exists(self::$cacheDir . 'data/')) {
            mkdir(self::$cacheDir . 'data/', 0755, true);
        }
    }
    
    /**
     * Получить данные из кэша
     */
    public static function get($key) {
        self::init();
        $file = self::$cacheDir . 'data/' . md5($key) . '.cache';
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($file));
        
        // Проверяем срок действия
        if (time() > $data['expires']) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Сохранить данные в кэш
     */
    public static function set($key, $value, $ttl = null) {
        self::init();
        $ttl = $ttl ?? self::$defaultTTL;
        $file = self::$cacheDir . 'data/' . md5($key) . '.cache';
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        file_put_contents($file, serialize($data));
    }
    
    /**
     * Удалить данные из кэша
     */
    public static function delete($key) {
        self::init();
        $file = self::$cacheDir . 'data/' . md5($key) . '.cache';
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    /**
     * Очистить весь кэш
     */
    public static function clear() {
        self::init();
        $files = glob(self::$cacheDir . 'data/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    /**
     * Очистить устаревшие записи
     */
    public static function clean() {
        self::init();
        $files = glob(self::$cacheDir . 'data/*.cache');
        foreach ($files as $file) {
            $data = unserialize(file_get_contents($file));
            if (time() > $data['expires']) {
                unlink($file);
            }
        }
    }
}

