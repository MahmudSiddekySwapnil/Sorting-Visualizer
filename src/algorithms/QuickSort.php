<?php
declare(strict_types=1);

final class QuickSort extends Tracer
{
    protected const VARS = ['lo', 'hi', 'p', 'pivot', 'i', 'j'];

    public static function meta(): array
    {
        return [
            'id' => 'quick', 'name' => 'Quick sort (Lomuto)', 'group' => 'Efficient', 'file' => 'quickSort.php',
            'best' => 'O(n log n)', 'avg' => 'O(n log n)', 'worst' => 'O(n²)', 'space' => 'O(log n)', 'stable' => 'No',
            'summary' => 'Picks the last element as pivot, moves everything ≤ pivot to its left, places the pivot in its final spot, then recurses on both sides. Already-sorted input hits the O(n²) worst case with this pivot choice.',
            'min' => -999, 'max' => 999, 'recursive' => true,
        ];
    }

    public static function source(): string
    {
        return <<<'PHP'
function quickSort(array &$arr, int $lo, int $hi): void
{
    if ($lo < $hi) {
        $p = partition($arr, $lo, $hi);
        quickSort($arr, $lo, $p - 1);
        quickSort($arr, $p + 1, $hi);
    }
}

function partition(array &$arr, int $lo, int $hi): int
{
    $pivot = $arr[$hi];
    $i = $lo - 1;
    for ($j = $lo; $j < $hi; $j++) {
        if ($arr[$j] <= $pivot) {
            $i++;
            [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
        }
    }
    [$arr[$i + 1], $arr[$hi]] = [$arr[$hi], $arr[$i + 1]];
    return $i + 1;
}

// Entry point: quickSort($arr, 0, count($arr) - 1);
PHP;
    }

    protected function run(array $arr): array
    {
        $n = count($arr);
        $this->rec(24, 'enter', 'Call quickSort($arr, 0, ' . ($n - 1) . ')', get_defined_vars());
        $this->quickSort($arr, 0, $n - 1);
        return $arr;
    }

    private function quickSort(array &$arr, int $lo, int $hi): void
    {
        $this->push("quickSort($lo, $hi)");
        $o = ['range' => $lo <= $hi ? [$lo, $hi] : null, 'ptrs' => ['lo' => $lo, 'hi' => $hi]];
        $this->rec(1, 'call', "Enter quickSort(\$arr, $lo, $hi)", get_defined_vars(), $o);
        $this->rec(3, 'check', "\$lo ($lo) < \$hi ($hi) is " . self::b($lo < $hi), get_defined_vars(), $o);

        if ($lo < $hi) {
            $this->rec(4, 'call', "Partition [$lo..$hi] around pivot \$arr[$hi] = {$arr[$hi]}", get_defined_vars(), $o);
            $p = $this->partition($arr, $lo, $hi);
            $this->markSorted($p);
            $o['ptrs']['p'] = $p;
            $this->rec(4, 'assign', "\$p = $p; pivot {$arr[$p]} is in its final position", get_defined_vars(), $o + ['focus' => []]);
            $this->rec(5, 'call', "Sort left part [$lo.." . ($p - 1) . ']', get_defined_vars(), $o);
            $this->quickSort($arr, $lo, $p - 1);
            $this->rec(6, 'call', 'Sort right part [' . ($p + 1) . "..$hi]", get_defined_vars(), $o);
            $this->quickSort($arr, $p + 1, $hi);
        } elseif ($lo === $hi) {
            $this->markSorted($lo); // a single element is in its final position
        }

        if ($this->depth() === 1) {
            $this->markAll(count($arr));
        }
        $this->rec(8, 'return', "quickSort($lo, $hi) returns", get_defined_vars(), $o);
        $this->pop();
    }

    private function partition(array &$arr, int $lo, int $hi): int
    {
        $this->push("partition($lo, $hi)");
        $range = [$lo, $hi];
        $this->rec(10, 'call', "Enter partition(\$arr, $lo, $hi)", get_defined_vars(), ['range' => $range, 'ptrs' => ['lo' => $lo, 'hi' => $hi]]);
        $pivot = $arr[$hi];
        $this->rec(12, 'assign', "\$pivot = \$arr[$hi] → $pivot", get_defined_vars(), ['range' => $range, 'focus' => [$hi], 'ptrs' => ['pivot' => $hi]]);
        $i = $lo - 1;
        $this->rec(13, 'assign', "\$i = \$lo - 1 → $i (end of the ≤ pivot zone)", get_defined_vars(), ['range' => $range, 'ptrs' => ['i' => $i, 'pivot' => $hi]]);

        for ($j = $lo; $this->loop(14, $j < $hi, "\$j = $j; $j < \$hi ($hi)", get_defined_vars(), ['range' => $range, 'ptrs' => ['i' => $i, 'j' => $j, 'pivot' => $hi]]); $j++) {
            $le = $this->le($arr[$j], $pivot);
            $this->rec(15, 'compare', "\$arr[$j] = {$arr[$j]} <= \$pivot ($pivot) is " . self::b($le), get_defined_vars(), ['range' => $range, 'focus' => [$j, $hi], 'ptrs' => ['i' => $i, 'j' => $j, 'pivot' => $hi]]);
            if ($le) {
                $i++;
                $this->rec(16, 'assign', "\$i++ → $i", get_defined_vars(), ['range' => $range, 'ptrs' => ['i' => $i, 'j' => $j, 'pivot' => $hi]]);
                [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
                $this->swaps++;
                $this->writes += 2;
                $this->rec(17, 'swap', $i === $j ? "Swap \$arr[$i] with itself (already in place)" : "Swap \$arr[$i] and \$arr[$j]", get_defined_vars(), ['range' => $range, 'focus' => [$i, $j], 'ptrs' => ['i' => $i, 'j' => $j, 'pivot' => $hi]]);
            }
        }

        [$arr[$i + 1], $arr[$hi]] = [$arr[$hi], $arr[$i + 1]];
        $this->swaps++;
        $this->writes += 2;
        $this->rec(20, 'swap', 'Place pivot: swap $arr[' . ($i + 1) . "] and \$arr[$hi]", get_defined_vars(), ['range' => $range, 'focus' => [$i + 1, $hi], 'ptrs' => ['i+1' => $i + 1, 'hi' => $hi]]);
        $this->rec(21, 'return', 'Return ' . ($i + 1), get_defined_vars(), ['range' => $range, 'ptrs' => ['p' => $i + 1]]);
        $this->pop();
        return $i + 1;
    }
}
