<?php
declare(strict_types=1);

final class RadixSort extends Tracer
{
    protected const VARS = ['max', 'exp', 'value', 'digit'];

    public static function meta(): array
    {
        return [
            'id' => 'radix', 'name' => 'Radix sort (LSD)', 'group' => 'Non-comparison', 'file' => 'radixSort.php',
            'best' => 'O(d·(n + b))', 'avg' => 'O(d·(n + b))', 'worst' => 'O(d·(n + b))', 'space' => 'O(n + b)', 'stable' => 'Yes',
            'summary' => 'Sorts by one digit at a time, least significant first, dropping values into 10 buckets and collecting them in order. Bucket order is preserved, so each pass keeps the previous one’s work. d = digits, b = 10.',
            'min' => 0, 'max' => 999, 'recursive' => false,
        ];
    }

    public static function source(): string
    {
        return <<<'PHP'
function radixSort(array $arr): array
{
    $max = max($arr);
    for ($exp = 1; intdiv($max, $exp) > 0; $exp *= 10) {
        $buckets = array_fill(0, 10, []);
        foreach ($arr as $value) {
            $digit = intdiv($value, $exp) % 10;
            $buckets[$digit][] = $value;
        }
        $arr = array_merge(...$buckets);
    }
    return $arr;
}
PHP;
    }

    protected function run(array $arr): array
    {
        $this->rec(1, 'enter', 'Call radixSort() with ' . count($arr) . ' elements', get_defined_vars());
        $max = max($arr);
        $this->rec(3, 'assign', "\$max = max(\$arr) → $max", get_defined_vars());

        for ($exp = 1; $this->loop(4, intdiv($max, $exp) > 0, "\$exp = $exp; intdiv($max, $exp) = " . intdiv($max, $exp) . ' > 0', get_defined_vars()); $exp *= 10) {
            $place = [1 => 'ones', 10 => 'tens', 100 => 'hundreds'][$exp] ?? "×$exp";
            $buckets = array_fill(0, 10, []);
            $this->rec(5, 'assign', "Empty 10 buckets for the $place digit", get_defined_vars(), ['aux' => $this->bk($buckets)]);
            foreach ($arr as $idx => $value) {
                $this->rec(6, 'loop', "Next element: \$value = $value (index $idx)", get_defined_vars(), ['focus' => [$idx], 'aux' => $this->bk($buckets)]);
                $digit = intdiv($value, $exp) % 10;
                $this->rec(7, 'assign', "\$digit = intdiv($value, $exp) % 10 → $digit ($place digit)", get_defined_vars(), ['focus' => [$idx], 'aux' => $this->bk($buckets, $digit)]);
                $buckets[$digit][] = $value;
                $this->rec(8, 'write', "Append $value to bucket $digit", get_defined_vars(), ['focus' => [$idx], 'aux' => $this->bk($buckets, $digit)]);
            }
            $this->rec(6, 'loop', 'No more elements, exit foreach', get_defined_vars(), ['aux' => $this->bk($buckets)]);
            $arr = array_merge(...$buckets);
            $this->writes += count($arr);
            $this->rec(10, 'write', 'Collect buckets 0–9 in order → ' . self::list($arr), get_defined_vars(), ['focus' => array_keys($arr), 'aux' => $this->bk($buckets)]);
        }

        $this->markAll(count($arr));
        $this->rec(12, 'return', 'Return ' . self::list($arr), get_defined_vars());
        return $arr;
    }

    private function bk(array $buckets, ?int $focus = null): array
    {
        return [['label' => '$buckets', 'kind' => 'buckets', 'values' => $buckets, 'focus' => $focus === null ? [] : [$focus], 'ptrs' => (object) []]];
    }
}
