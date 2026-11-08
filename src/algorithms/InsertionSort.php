<?php
declare(strict_types=1);

final class InsertionSort extends Tracer
{
    protected const VARS = ['n', 'i', 'key', 'j'];

    public static function meta(): array
    {
        return [
            'id' => 'insertion', 'name' => 'Insertion sort', 'group' => 'Simple', 'file' => 'insertionSort.php',
            'best' => 'O(n)', 'avg' => 'O(n²)', 'worst' => 'O(n²)', 'space' => 'O(1)', 'stable' => 'Yes',
            'summary' => 'Grows a sorted prefix one element at a time: takes the next value as $key, shifts larger values right, and drops $key into the gap. Fast on nearly-sorted data.',
            'min' => -999, 'max' => 999, 'recursive' => false,
        ];
    }

    public static function source(): string
    {
        return <<<'PHP'
function insertionSort(array $arr): array
{
    $n = count($arr);
    for ($i = 1; $i < $n; $i++) {
        $key = $arr[$i];
        $j = $i - 1;
        while ($j >= 0 && $arr[$j] > $key) {
            $arr[$j + 1] = $arr[$j];
            $j--;
        }
        $arr[$j + 1] = $key;
    }
    return $arr;
}
PHP;
    }

    protected function run(array $arr): array
    {
        $this->rec(1, 'enter', 'Call insertionSort() with ' . count($arr) . ' elements', get_defined_vars());
        $n = count($arr);
        $this->rec(3, 'assign', "\$n = count(\$arr) → $n", get_defined_vars());

        for ($i = 1; $this->loop(4, $i < $n, "\$i = $i; $i < \$n ($n)", get_defined_vars(), ['ptrs' => ['i' => $i]]); $i++) {
            $range = [0, $i];
            $key = $arr[$i];
            $this->rec(5, 'assign', "\$key = \$arr[$i] → $key", get_defined_vars(), ['focus' => [$i], 'range' => $range, 'ptrs' => ['i' => $i]]);
            $j = $i - 1;
            $this->rec(6, 'assign', "\$j = \$i - 1 → $j", get_defined_vars(), ['range' => $range, 'ptrs' => ['i' => $i, 'j' => $j]]);

            while (true) {
                if ($j < 0) {
                    $this->rec(7, 'loop', "\$j = $j; \$j >= 0 is false, exit loop", get_defined_vars(), ['range' => $range, 'ptrs' => ['i' => $i]]);
                    break;
                }
                $gt = $this->gt($arr[$j], $key);
                $this->rec(7, 'compare', "\$arr[$j] = {$arr[$j]} > \$key ($key) is " . self::b($gt) . ($gt ? '' : ', exit loop'), get_defined_vars(), ['focus' => [$j], 'range' => $range, 'ptrs' => ['i' => $i, 'j' => $j]]);
                if (!$gt) {
                    break;
                }
                $arr[$j + 1] = $arr[$j];
                $this->writes++;
                $this->rec(8, 'write', "Shift {$arr[$j]} right: \$arr[" . ($j + 1) . "] = \$arr[$j]", get_defined_vars(), ['focus' => [$j, $j + 1], 'range' => $range, 'ptrs' => ['j' => $j, 'j+1' => $j + 1]]);
                $j--;
                $this->rec(9, 'assign', "\$j-- → $j", get_defined_vars(), ['range' => $range, 'ptrs' => ['i' => $i, 'j' => $j]]);
            }

            $arr[$j + 1] = $key;
            $this->writes++;
            $this->rec(11, 'write', "Insert \$key ($key) at index " . ($j + 1) . "; prefix [0..$i] is sorted", get_defined_vars(), ['focus' => [$j + 1], 'range' => $range, 'ptrs' => ['j+1' => $j + 1]]);
        }

        $this->markAll($n);
        $this->rec(13, 'return', 'Return ' . self::list($arr), get_defined_vars());
        return $arr;
    }
}
