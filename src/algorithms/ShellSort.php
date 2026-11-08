<?php
declare(strict_types=1);

final class ShellSort extends Tracer
{
    protected const VARS = ['n', 'gap', 'i', 'temp', 'j'];

    public static function meta(): array
    {
        return [
            'id' => 'shell', 'name' => 'Shell sort', 'group' => 'Efficient', 'file' => 'shellSort.php',
            'best' => 'O(n log n)', 'avg' => '≈ O(n^1.5)', 'worst' => 'O(n²)', 'space' => 'O(1)', 'stable' => 'No',
            'summary' => 'Insertion sort on elements $gap apart, with the gap halved each round. Far-apart moves early mean little shifting is left when $gap reaches 1. Complexity depends on the gap sequence; halving is the simplest.',
            'min' => -999, 'max' => 999, 'recursive' => false,
        ];
    }

    public static function source(): string
    {
        return <<<'PHP'
function shellSort(array $arr): array
{
    $n = count($arr);
    for ($gap = intdiv($n, 2); $gap > 0; $gap = intdiv($gap, 2)) {
        for ($i = $gap; $i < $n; $i++) {
            $temp = $arr[$i];
            $j = $i;
            while ($j >= $gap && $arr[$j - $gap] > $temp) {
                $arr[$j] = $arr[$j - $gap];
                $j -= $gap;
            }
            $arr[$j] = $temp;
        }
    }
    return $arr;
}
PHP;
    }

    protected function run(array $arr): array
    {
        $this->rec(1, 'enter', 'Call shellSort() with ' . count($arr) . ' elements', get_defined_vars());
        $n = count($arr);
        $this->rec(3, 'assign', "\$n = count(\$arr) → $n", get_defined_vars());

        for ($gap = intdiv($n, 2); $this->loop(4, $gap > 0, "\$gap = $gap; $gap > 0", get_defined_vars()); $gap = intdiv($gap, 2)) {
            for ($i = $gap; $this->loop(5, $i < $n, "\$i = $i; $i < \$n ($n)", get_defined_vars(), ['ptrs' => ['i' => $i]]); $i++) {
                $temp = $arr[$i];
                $this->rec(6, 'assign', "\$temp = \$arr[$i] → $temp", get_defined_vars(), ['focus' => [$i], 'ptrs' => ['i' => $i]]);
                $j = $i;
                $this->rec(7, 'assign', "\$j = \$i → $j", get_defined_vars(), ['ptrs' => ['i' => $i, 'j' => $j]]);

                while (true) {
                    if ($j < $gap) {
                        $this->rec(8, 'loop', "\$j ($j) >= \$gap ($gap) is false, exit loop", get_defined_vars(), ['ptrs' => ['j' => $j]]);
                        break;
                    }
                    $gt = $this->gt($arr[$j - $gap], $temp);
                    $this->rec(8, 'compare', "\$arr[" . ($j - $gap) . "] = {$arr[$j - $gap]} > \$temp ($temp) is " . self::b($gt) . ($gt ? '' : ', exit loop'), get_defined_vars(), ['focus' => [$j - $gap, $j], 'ptrs' => ['j-gap' => $j - $gap, 'j' => $j]]);
                    if (!$gt) {
                        break;
                    }
                    $arr[$j] = $arr[$j - $gap];
                    $this->writes++;
                    $this->rec(9, 'write', "\$arr[$j] = \$arr[" . ($j - $gap) . "] → {$arr[$j]} (shift $gap right)", get_defined_vars(), ['focus' => [$j - $gap, $j], 'ptrs' => ['j-gap' => $j - $gap, 'j' => $j]]);
                    $j -= $gap;
                    $this->rec(10, 'assign', "\$j -= \$gap → $j", get_defined_vars(), ['ptrs' => ['j' => $j]]);
                }

                $arr[$j] = $temp;
                $this->writes++;
                $this->rec(12, 'write', "\$arr[$j] = \$temp → $temp", get_defined_vars(), ['focus' => [$j], 'ptrs' => ['j' => $j]]);
            }
        }

        $this->markAll($n);
        $this->rec(15, 'return', 'Return ' . self::list($arr), get_defined_vars());
        return $arr;
    }
}
