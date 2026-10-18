<?php
namespace app\common\service\nnzs;

use think\Db;

/**
 * 破坏性操作前的数据备份（JSON 行快照），存放于 application/data/backup/nnzs/。
 * 只做"留底"，不改变原操作的行为和返回值；备份失败只记日志、不阻断。
 */
class Backup
{
    public static function dir()
    {
        return APP_PATH . 'data/backup/nnzs/';
    }

    /**
     * @param string $table 不含前缀的表名
     * @param array|string $where 查询条件（同 Db::where）
     * @param array|string $fields 字段
     * @return string|false 备份文件路径
     */
    public static function table($table, $where = [], $fields = '*', $tag = '')
    {
        try {
            $dir = self::dir();
            if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
                Log::error('备份目录不可写: ' . $dir);
                return false;
            }
            $q = Db::name($table)->field($fields);
            if (!empty($where)) {
                $q->where($where);
            }
            $rows = $q->select();
            $rows = is_object($rows) ? $rows->toArray() : (array)$rows;
            $file = $dir . date('Ymd-His') . '-' . $table . ($tag !== '' ? '-' . preg_replace('/[^a-z0-9_]/i', '', $tag) : '')
                . '-' . substr(md5(uniqid('', true)), 0, 6) . '.json';
            $json = json_encode(['table' => $table, 'time' => date('c'), 'count' => count($rows), 'rows' => $rows],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false || @file_put_contents($file, $json, LOCK_EX) === false) {
                Log::error('写备份失败: ' . $file);
                return false;
            }
            Log::info('已备份 ' . $table . ' ' . count($rows) . ' 行 -> ' . basename($file));
            return $file;
        } catch (\Throwable $e) {
            Log::error('备份 ' . $table . ' 失败: ' . $e->getMessage());
            return false;
        }
    }

    /** 最近的备份文件列表 */
    public static function lists($limit = 50)
    {
        $files = glob(self::dir() . '*.json') ?: [];
        rsort($files);
        $out = [];
        foreach (array_slice($files, 0, $limit) as $f) {
            $out[] = ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)];
        }
        return $out;
    }
}
