<?php
declare(strict_types=1);

final class CocktailSort extends Tracer
{
    protected const VARS = ['start', 'end', 'i', 'swapped'];

    public static function meta(): array
    {
        return [
            'id' => 'cocktail', 'name' => 'Cocktail shaker sort', 'group' => 'Simple', 'file' => 'cocktailSort.php',
            'best' => 'O(n)', 'avg' => 'O(n²)', 'worst' => 'O(n²)', 'space' => 'O(1)', 'stable' => 'Yes',
            'summary' => 'Bubble sort in both directions: a forward pass moves the largest value right, a backward pass moves the smallest left. Small values near the end travel faster.',
            'min' => -999, 'max' => 999, 'recursive' => false,
        ];
    }

    public static function source(): string
    {
        return <<<'PHP'
function cocktailSort(array $arr): array
{
    $start = 0;
    $end = count($arr) - 1;
    $swapped = true;
    while ($swapped) {
        $swapped = false;
        for ($i = $start; $i < $end; $i++) {
            if ($arr[$i] > $arr[$i + 1]) {
                [$arr[$i], $arr[$i + 1]] = [$arr[$i + 1], $arr[$i]];
                $swapped = true;
            }
        }
        $end--;
        if (!$swapped) {
            break;
        }
        $swapped = false;
        for ($i = $end; $i > $start; $i--) {
            if ($arr[$i - 1] > $arr[$i]) {
                [$arr[$i - 1], $arr[$i]] = [$arr[$i], $arr[$i - 1]];
                $swapped = true;
            }
        }
        $start++;
    }
    return $arr;
}
PHP;
    }

    protected function run(array $arr): array
    {
        $n = count($arr);
        $this->rec(1, 'enter', "Call cocktailSort() with $n elements", get_defined_vars());
        $start = 0;
        $this->rec(3, 'assign', '$start = 0', get_defined_vars());
        $end = count($arr) - 1;
        $this->rec(4, 'assign', "\$end = count(\$arr) - 1 → $end", get_defined_vars());
        $swapped = true;
        $this->rec(5, 'assign', '$swapped = true', get_defined_vars());

        while ($this->loop(6, $swapped, '$swapped', get_defined_vars(), ['range' => [$start, $end]])) {
            $swapped = false;
            $this->rec(7, 'assign', '$swapped = false', get_defined_vars(), ['range' => [$start, $end]]);

            for ($i = $start; $this->loop(8, $i < $end, "\$i = $i; $i < \$end ($end)", get_defined_vars(), ['range' => [$start, $end], 'ptrs' => ['i' => $i]]); $i++) {
                $o = ['focus' => [$i, $i + 1], 'ptrs' => ['i' => $i, 'i+1' => $i + 1], 'range' => [$start, $end]];
                $gt = $this->gt($arr[$i], $arr[$i + 1]);
                $this->rec(9, 'compare', "\$arr[$i] = {$arr[$i]} > \$arr[" . ($i + 1) . "] = {$arr[$i + 1]} is " . self::b($gt), get_defined_vars(), $o);
                if ($gt) {
                    [$arr[$i], $arr[$i + 1]] = [$arr[$i + 1], $arr[$i]];
                    $this->swaps++;
                    $this->writes += 2;
                    $this->rec(10, 'swap', "Swap \$arr[$i] and \$arr[" . ($i + 1) . ']', get_defined_vars(), $o);
                    $swapped = true;
                    $this->rec(11, 'assign', '$swapped = true', get_defined_vars(), $o);
                }
            }

            $this->markSorted($end);
            $end--;
            $this->rec(14, 'assign', 'Forward pass done; index ' . ($end + 1) . " is final. \$end-- → $end", get_defined_vars());
            $this->rec(15, 'check', '!$swapped is ' . self::b(!$swapped), get_defined_vars());
            if (!$swapped) {
                $this->markAll($n);
                $this->rec(16, 'break', 'No swaps in this pass, so the array is sorted. Exit early.', get_defined_vars());
                break;
            }
            $swapped = false;
            $this->rec(18, 'assign', '$swapped = false', get_defined_vars(), ['range' => [$start, $end]]);

            for ($i = $end; $this->loop(19, $i > $start, "\$i = $i; $i > \$start ($start)", get_defined_vars(), ['range' => [$start, $end], 'ptrs' => ['i' => $i]]); $i--) {
                $o = ['focus' => [$i - 1, $i], 'ptrs' => ['i-1' => $i - 1, 'i' => $i], 'range' => [$start, $end]];
                $gt = $this->gt($arr[$i - 1], $arr[$i]);
                $this->rec(20, 'compare', "\$arr[" . ($i - 1) . "] = {$arr[$i - 1]} > \$arr[$i] = {$arr[$i]} is " . self::b($gt), get_defined_vars(), $o);
                if ($gt) {
                    [$arr[$i - 1], $arr[$i]] = [$arr[$i], $arr[$i - 1]];
                    $this->swaps++;
                    $this->writes += 2;
                    $this->rec(21, 'swap', "Swap \$arr[" . ($i - 1) . "] and \$arr[$i]", get_defined_vars(), $o);
                    $swapped = true;
                    $this->rec(22, 'assign', '$swapped = true', get_defined_vars(), $o);
                }
            }

            $this->markSorted($start);
            $start++;
            $this->rec(25, 'assign', 'Backward pass done; index ' . ($start - 1) . " is final. \$start++ → $start", get_defined_vars());
        }

        $this->markAll($n);
        $this->rec(27, 'return', 'Return ' . self::list($arr), get_defined_vars());
        return $arr;
    }
}
