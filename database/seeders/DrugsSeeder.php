<?php

namespace Database\Seeders;

use App\Models\Drug;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DrugsSeeder extends Seeder
{
    public function run(): void
    {
        $sqlFile = database_path('data/drugs.sql');

        if (!file_exists($sqlFile)) {
            $this->command->error('❌ ملف drugs.sql مش موجود');
            return;
        }

        $this->command->info('⏳ بيتم تحميل بيانات الأدوية...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Drug::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $sql = file_get_contents($sqlFile);
        $sql = $this->translateColumns($sql);

        // استخرج كل جملة INSERT
        preg_match_all(
            "/INSERT\s+INTO\s+`?drugs`?\s*\(([^)]+)\)\s*VALUES\s*\((.+?)\)\s*;/is",
            $sql,
            $matches,
            PREG_SET_ORDER
        );

        if (empty($matches)) {
            $this->command->error('❌ مفيش بيانات اتلقت في الملف');
            return;
        }

        $count = 0;
        $errors = 0;
        $batchSize = 100;
        $batch = [];

        foreach ($matches as $match) {
            $columns = $this->parseColumns($match[1]);
            $values  = $this->parseValues($match[2]);

            if (count($columns) !== count($values)) {
                $errors++;
                continue;
            }

            $row = array_combine($columns, $values);

            // تحويل timestamp
            if (!empty($row['price_updated_at']) && is_numeric($row['price_updated_at'])) {
                $ts = (int) $row['price_updated_at'];
                if ($ts > 9999999999) $ts = intdiv($ts, 1000);
                $row['price_updated_at'] = Carbon::createFromTimestamp($ts)->format('Y-m-d H:i:s');
            }

            // تحويل NULL
            foreach ($row as $k => $v) {
                if (strtoupper((string)$v) === 'NULL' || $v === '') {
                    $row[$k] = null;
                }
            }

            $batch[] = $row;

            if (count($batch) >= $batchSize) {
                DB::table('drugs')->insert($batch);
                $count += count($batch);
                $batch = [];
            }
        }

        // ادخل الباقي
        if (!empty($batch)) {
            DB::table('drugs')->insert($batch);
            $count += count($batch);
        }

        $this->command->info("✅ تم إضافة {$count} دواء بنجاح!");

        if ($errors > 0) {
            $this->command->warn("⚠️ {$errors} صف اتجاهل بسبب خطأ في البيانات");
        }
    }

    /**
     * استخرج أسماء الأعمدة من جزء الـ INSERT
     */
    private function parseColumns(string $columnsPart): array
    {
        $columnsPart = trim($columnsPart);
        $cols = explode(',', $columnsPart);

        return array_map(function ($col) {
            return trim(trim($col), '`\'" ');
        }, $cols);
    }

    /**
     * استخرج القيم بشكل آمن — يتعامل مع الـ quotes الجوا النص
     */
    private function parseValues(string $valuesPart): array
    {
        $values = [];
        $current = '';
        $inQuote = false;
        $quoteChar = '';
        $i = 0;
        $len = strlen($valuesPart);

        while ($i < $len) {
            $char = $valuesPart[$i];

            if (!$inQuote && ($char === "'" || $char === '"')) {
                $inQuote = true;
                $quoteChar = $char;
                $i++;
                continue;
            }

            if ($inQuote && $char === '\\' && $i + 1 < $len) {
                // escaped character — خد الحرف اللي بعده زي ما هو
                $next = $valuesPart[$i + 1];
                if ($next === "'")  { $current .= "'";  $i += 2; continue; }
                if ($next === '"')  { $current .= '"';  $i += 2; continue; }
                if ($next === '\\') { $current .= '\\'; $i += 2; continue; }
                if ($next === 'n')  { $current .= "\n"; $i += 2; continue; }
                if ($next === 'r')  { $current .= "\r"; $i += 2; continue; }
                if ($next === 't')  { $current .= "\t"; $i += 2; continue; }
                $current .= $next;
                $i += 2;
                continue;
            }

            if ($inQuote && $char === $quoteChar) {
                // تحقق لو اقتباس مضاعف زي ''
                if ($i + 1 < $len && $valuesPart[$i + 1] === $quoteChar) {
                    $current .= $char;
                    $i += 2;
                    continue;
                }
                $inQuote = false;
                $quoteChar = '';
                $i++;
                continue;
            }

            if (!$inQuote && $char === ',') {
                $values[] = $current === '' ? null : $current;
                $current = '';
                $i++;
                continue;
            }

            if (!$inQuote && strtoupper(substr($valuesPart, $i, 4)) === 'NULL') {
                $values[] = null;
                $current = '';
                $i += 4;
                if ($i < $len && $valuesPart[$i] === ',') $i++;
                continue;
            }

            $current .= $char;
            $i++;
        }

        // آخر قيمة
        $values[] = $current === '' ? null : $current;

        return $values;
    }

    private function translateColumns(string $sql): string
    {
        $map = [
            '"id"'                    => '`id`',
            '"اسم انجليزي"'           => '`name_en`',
            '"اسم عربي"'              => '`name_ar`',
            '"سعر قديم"'              => '`old_price`',
            '"سعر جديد"'              => '`new_price`',
            '"مادة فعالة"'            => '`active_ingredient`',
            '"الشركة"'                => '`company`',
            '"الفئة"'                 => '`category`',
            '"وحدات كبرى"'            => '`major_units`',
            '"وحدات صغرى"'            => '`minor_units`',
            '"حجم/وزن"'               => '`size_weight`',
            '"تركيز"'                 => '`concentration`',
            '"شكل صيدلاني"'           => '`dosage_form`',
            '"باركود دولي"'           => '`barcode`',
            '"محلي/مستورد"'           => '`origin`',
            '"تاريخ تحديث السعر"'     => '`price_updated_at`',
            '"شهرة الصنف"'            => '`popularity`',
        ];

        return str_replace(array_keys($map), array_values($map), $sql);
    }
}