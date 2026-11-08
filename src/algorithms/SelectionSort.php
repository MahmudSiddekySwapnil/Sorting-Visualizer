<?php
declare(strict_types=1);

final class SelectionSort extends Tracer
{
    protected const VARS = ['n', 'i', 'j', 'min'];

    public static function meta(): array
    {
        return [
            'id' => 'selection', 'name' => 'Selection sort', 'group' => 'Simple', 'file' => 'selectionSort.php',
            'best' => 'O(n²)', 'avg' => 'O(n²)', 'worst' => 'O(n²)', 'space' => 'O(1)', 'stable' => 'No',
            'summary' => 'Scans the unsorted part for its smallest value and swaps it into the next position. At most n − 1 swaps, but always n²/2 comparisons.',
            'min' => -999, 'max' => 999, 'recursive' => false,
        ];
    }

    public static function source(): string
    {
        return <<<'PHP'
function selectionSort(array $arr): array
{
    $n = count($arr);
    for ($i = 0; $i < $n - 1; $i++) {
        $min = $i;
        for ($j = $i + 1; $j < $n; $j++) {
            if ($arr[$j] < $arr[$min]) {
                $min = $j;
            }
        }
        if ($min !== $i) {
            [$arr[$i], $arr[$min]] = [$arr[$min], $arr[$i]];
        }
    }
    return $arr;
}
PHP;
    }

    protected function run(array $arr): array
    {
        $this->rec(1, 'enter', 'Call selectionSort() with ' . count($arr) . ' elements', get_defined_vars());
        $n = count($arr);
        $this->rec(3, 'assign', "\$n = count(\$arr) → $n", get_defined_vars());

        for ($i = 0; $this->loop(4, $i < $n - 1, "\$i = $i; $i < \$n - 1 (" . ($n - 1) . ')', get_defined_vars(), ['ptrs' => ['i' => $i]]); $i++) {
            $range = [$i, $n - 1];
            $min = $i;
            $this->rec(5, 'assign', "\$min = \$i → $i", get_defined_vars(), ['range' => $range, 'ptrs' => ['i' => $i, 'min' => $min]]);

            for ($j = $i + 1; $this->loop(6, $j < $n, "\$j = $j; $j < \$n ($n)", get_defined_vars(), ['range' => $range, 'ptrs' => ['i' => $i, 'j' => $j, 'min' => $min]]); $j++) {
                $lt = $this->lt($arr[$j], $arr[$min]);
                $this->rec(7, 'compare', "\$arr[$j] = {$arr[$j]} < \$arr[$min] = {$arr[$min]} (current min) is " . self::b($lt), get_defined_vars(), ['focus' => [$j, $min], 'range' => $range, 'ptrs' => ['i' => $i, 'j' => $j, 'min' => $min]]);
                if ($lt) {
                    $min = $j;
                    $this->rec(8, 'assign', "New minimum: \$min = $j (value {$arr[$j]})", get_defined_vars(), ['focus' => [$min], 'range' => $range, 'ptrs' => ['i' => $i, 'j' => $j, 'min' => $min]]);
                }
            }

            $this->rec(11, 'check', "\$min ($min) !== \$i ($i) is " . self::b($min !== $i), get_defined_vars(), ['range' => $range, 'ptrs' => ['i' => $i, 'min' => $min]]);
            $this->markSorted($i);
            if ($min !== $i) {
                [$arr[$i], $arr[$min]] = [$arr[$min], $arr[$i]];
                $this->swaps++;
                $this->writes += 2;
                $this->rec(12, 'swap', "Swap \$arr[$i] and \$arr[$min]; index $i is final", get_defined_vars(), ['focus' => [$i, $min], 'range' => $range, 'ptrs' => ['i' => $i, 'min' => $min]]);
            }
        }

        $this->markAll($n);
        $this->rec(15, 'return', 'Return ' . self::list($arr), get_defined_vars());
        return $arr;
    }
}
