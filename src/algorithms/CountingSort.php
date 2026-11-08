<?php
declare(strict_types=1);

final class CountingSort extends Tracer
{
    protected const VARS = ['max', 'value', 'k', 'v'];

    public static function meta(): array
    {
        return [
            'id' => 'counting', 'name' => 'Counting sort', 'group' => 'Non-comparison', 'file' => 'countingSort.php',
            'best' => 'O(n + k)', 'avg' => 'O(n + k)', 'worst' => 'O(n + k)', 'space' => 'O(k)', 'stable' => 'n/a',
            'summary' => 'No comparisons: counts how often each value occurs, then rewrites the array in value order. k is the largest value, so it suits small integer ranges. This simple form rebuilds values; the prefix-sum variant is stable.',
            'min' => 0, 'max' => 99, 'recursive' => false,
        ];
    }

    public static function source(): string
    {
        return <<<'PHP'
function countingSort(array $arr): array
{
    $max = max($arr);
    $count = array_fill(0, $max + 1, 0);
    foreach ($arr as $value) {
        $count[$value]++;
    }
    $k = 0;
    for ($v = 0; $v <= $max; $v++) {
        while ($count[$v] > 0) {
            $arr[$k++] = $v;
            $count[$v]--;
        }
    }
    return $arr;
}
PHP;
    }

    protected function run(array $arr): array
    {
        $this->rec(1, 'enter', 'Call countingSort() with ' . count($arr) . ' elements', get_defined_vars());
        $max = max($arr);
        $this->rec(3, 'assign', "\$max = max(\$arr) → $max", get_defined_vars());
        $count = array_fill(0, $max + 1, 0);
        $this->rec(4, 'assign', '$count = array_fill(0, ' . ($max + 1) . ', 0)', get_defined_vars(), ['aux' => $this->c($count)]);

        foreach ($arr as $idx => $value) {
            $this->rec(5, 'loop', "Next element: \$value = $value (index $idx)", get_defined_vars(), ['focus' => [$idx], 'aux' => $this->c($count, [$value])]);
            $count[$value]++;
            $this->rec(6, 'write', "\$count[$value]++ → {$count[$value]}", get_defined_vars(), ['focus' => [$idx], 'aux' => $this->c($count, [$value])]);
        }
        $this->rec(5, 'loop', 'No more elements, exit foreach', get_defined_vars(), ['aux' => $this->c($count)]);

        $k = 0;
        $this->rec(8, 'assign', '$k = 0', get_defined_vars(), ['aux' => $this->c($count), 'ptrs' => ['k' => $k]]);
        for ($v = 0; $this->loop(9, $v <= $max, "\$v = $v; $v <= \$max ($max)", get_defined_vars(), ['aux' => $this->c($count, [], $v), 'ptrs' => ['k' => $k]]); $v++) {
            while ($this->loop(10, $count[$v] > 0, "\$count[$v] ({$count[$v]}) > 0", get_defined_vars(), ['aux' => $this->c($count, [$v], $v), 'ptrs' => ['k' => $k]])) {
                $arr[$k++] = $v;
                $this->writes++;
                $this->markSorted($k - 1);
                $this->rec(11, 'write', '$arr[' . ($k - 1) . "] = $v; \$k++ → $k", get_defined_vars(), ['focus' => [$k - 1], 'aux' => $this->c($count, [$v], $v), 'ptrs' => ['k' => $k]]);
                $count[$v]--;
                $this->rec(12, 'assign', "\$count[$v]-- → {$count[$v]}", get_defined_vars(), ['aux' => $this->c($count, [$v], $v), 'ptrs' => ['k' => $k]]);
            }
        }

        $this->markAll(count($arr));
        $this->rec(15, 'return', 'Return ' . self::list($arr), get_defined_vars(), ['aux' => $this->c($count)]);
        return $arr;
    }

    private function c(array $count, array $focus = [], ?int $v = null): array
    {
        return [['label' => '$count', 'kind' => 'cells', 'values' => $count, 'focus' => $focus, 'ptrs' => (object) ($v === null ? [] : ['v' => $v])]];
    }
}
