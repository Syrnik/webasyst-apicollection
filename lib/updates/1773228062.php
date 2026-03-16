<?php
/**
 * Миграция: перемещение файлов спецификаций из публичной папки в защищённую
 *
 * Перемещает все файлы из wa-data/public/apicollection/apicollection/specs/ (публичная, параметр true)
 * в wa-data/protected/apicollection/specs/ (защищённая, параметр false)
 * для повышения безопасности.
 */

$publicSpecsDir = wa()->getDataPath('apicollection/specs', true, 'apicollection');   // публичная папка
$protectedSpecsDir = wa()->getDataPath('specs', false, 'apicollection'); // защищённая папка

try {
    // Создаём защищённую папку, если её нет
    waFiles::create($protectedSpecsDir, true);

    // Если публичная папка существует, перемещаем файлы
    if (is_dir($publicSpecsDir)) {
        $files = waFiles::listdir($publicSpecsDir);

        foreach ($files as $file) {
            $publicPath = $publicSpecsDir . DIRECTORY_SEPARATOR . $file;
            $protectedPath = $protectedSpecsDir . DIRECTORY_SEPARATOR . $file;

            // Пропускаем, если файл уже существует в защищённой папке
            if (file_exists($protectedPath)) {
                continue;
            }

            // Перемещаем файл
            waFiles::move($publicPath, $protectedPath);
        }

        // Удаляем пустую публичную папку
        waFiles::delete($publicSpecsDir, true);
    }
} catch (Exception $e) {
    // Логируем ошибку, но не прерываем миграцию
    waLog::log('Migration error: ' . $e->getMessage(), 'apicollection.log');
}
