<?php
declare(strict_types=1);

final class BubbleSort extends Tracer
{
    protected const VARS = ['n', 'i', 'j', 'temp', 'swapped'];

    public static function meta(): array
    {
        return [
            'id' => 'bubble', 'name' => 'Bubble sort', 'group' => 'Simple', 'file' => 'bubbleSort.php',
            'best' => 'O(n)', 'avg' => 'O(n²)', 'worst' => 'O(n²)', 'space' => 'O(1)', 'stable' => 'Yes',
            'summary' => 'Swaps adjacent out-of-order pairs. Each pass bubbles the largest remaining value to the end; stops early if a pass makes no swaps.',
            'min' => -999, 'max' => 999, 'recursive' => false,
        ];
    }

    public static function source(): string
    {
        return <<<'PHP'
function bubbleSort(array $arr): array
{
    $n = count($arr);
    for ($i = 0; $i < $n - 1; $i++) {
        $swapped = false;
        for ($j = 0; $j < $n - $i - 1; $j++) {
            if ($arr[$j] > $arr[$j + 1]) {
                $temp = $arr[$j];
                $arr[$j] = $arr[$j + 1];
                $arr[$j + 1] = $temp;
                $swapped = true;
            }
        }
        if (!$swapped) {
            break;
        }
    }
    return $arr;
}
PHP;
    }

    protected function run(array $arr): array
    {
        $this->rec(1, 'enter', 'Call bubbleSort() with ' . count($arr) . ' elements', get_defined_vars());
        $n = count($arr);
        $this->rec(3, 'assign', "\$n = count(\$arr) → $n", get_defined_vars());

        for ($i = 0; $this->loop(4, $i < $n - 1, "\$i = $i; $i < \$n - 1 (" . ($n - 1) . ')', get_defined_vars()); $i++) {
            $swapped = false;
            $this->rec(5, 'assign', '$swapped = false', get_defined_vars(), ['range' => [0, $n - $i - 1]]);

            for ($j = 0; $this->loop(6, $j < $n - $i - 1, "\$j = $j; $j < \$n - \$i - 1 (" . ($n - $i - 1) . ')', get_defined_vars(), ['range' => [0, $n - $i - 1], 'ptrs' => ['j' => $j]]); $j++) {
                $o = ['focus' => [$j, $j + 1], 'ptrs' => ['j' => $j, 'j+1' => $j + 1], 'range' => [0, $n - $i - 1]];
                $gt = $this->gt($arr[$j], $arr[$j + 1]);
                $this->rec(7, 'compare', "\$arr[$j] = {$arr[$j]} > \$arr[" . ($j + 1) . "] = {$arr[$j + 1]} is " . self::b($gt), get_defined_vars(), $o);
                if ($gt) {
                    $temp = $arr[$j];
                    $this->rec(8, 'swap', "\$temp = \$arr[$j] → $temp", get_defined_vars(), $o);
                    $arr[$j] = $arr[$j + 1];
                    $this->writes++;
                    $this->rec(9, 'swap', "\$arr[$j] = \$arr[" . ($j + 1) . "] → {$arr[$j]}", get_defined_vars(), $o);
                    $arr[$j + 1] = $temp;
                    $this->writes++;
                    $this->swaps++;
                    $this->rec(10, 'swap', "\$arr[" . ($j + 1) . "] = \$temp → $temp (swap complete)", get_defined_vars(), $o);
                    $swapped = true;
                    $this->rec(11, 'assign', '$swapped = true', get_defined_vars(), $o);
                }
            }

            $this->markSorted($n - $i - 1);
            $this->rec(14, 'check', 'Pass ' . ($i + 1) . ' done; index ' . ($n - $i - 1) . ' is final. !$swapped is ' . self::b(!$swapped), get_defined_vars());
            if (!$swapped) {
                $this->markAll($n);
                $this->rec(15, 'break', 'No swaps in this pass, so the array is sorted. Exit early.', get_defined_vars());
                break;
            }
        }

        $this->markAll($n);
        $this->rec(18, 'return', 'Return ' . self::list($arr), get_defined_vars());
        return $arr;
    }
}
