<?php
declare(strict_types=1);

final class MergeSort extends Tracer
{
    protected const VARS = ['lo', 'hi', 'mid', 'i', 'j', 'k'];

    public static function meta(): array
    {
        return [
            'id' => 'merge', 'name' => 'Merge sort', 'group' => 'Efficient', 'file' => 'mergeSort.php',
            'best' => 'O(n log n)', 'avg' => 'O(n log n)', 'worst' => 'O(n log n)', 'space' => 'O(n)', 'stable' => 'Yes',
            'summary' => 'Divide and conquer: split the range in half, sort each half recursively, then merge the two sorted halves using temporary $left and $right copies.',
            'min' => -999, 'max' => 999, 'recursive' => true,
        ];
    }

    public static function source(): string
    {
        return <<<'PHP'
function mergeSort(array &$arr, int $lo, int $hi): void
{
    if ($lo >= $hi) {
        return;
    }
    $mid = intdiv($lo + $hi, 2);
    mergeSort($arr, $lo, $mid);
    mergeSort($arr, $mid + 1, $hi);
    merge($arr, $lo, $mid, $hi);
}

function merge(array &$arr, int $lo, int $mid, int $hi): void
{
    $left = array_slice($arr, $lo, $mid - $lo + 1);
    $right = array_slice($arr, $mid + 1, $hi - $mid);
    $i = $j = 0;
    $k = $lo;
    while ($i < count($left) && $j < count($right)) {
        if ($left[$i] <= $right[$j]) {
            $arr[$k++] = $left[$i++];
        } else {
            $arr[$k++] = $right[$j++];
        }
    }
    while ($i < count($left)) {
        $arr[$k++] = $left[$i++];
    }
    while ($j < count($right)) {
        $arr[$k++] = $right[$j++];
    }
}

// Entry point: mergeSort($arr, 0, count($arr) - 1);
PHP;
    }

    protected function run(array $arr): array
    {
        $n = count($arr);
        $this->rec(33, 'enter', 'Call mergeSort($arr, 0, ' . ($n - 1) . ')', get_defined_vars());
        $this->mergeSort($arr, 0, $n - 1);
        return $arr;
    }

    private function mergeSort(array &$arr, int $lo, int $hi): void
    {
        $this->push("mergeSort($lo, $hi)");
        $o = ['range' => [$lo, $hi], 'ptrs' => ['lo' => $lo, 'hi' => $hi]];
        $this->rec(1, 'call', "Enter mergeSort(\$arr, $lo, $hi)", get_defined_vars(), $o);
        $this->rec(3, 'check', "\$lo ($lo) >= \$hi ($hi) is " . self::b($lo >= $hi), get_defined_vars(), $o);
        if ($lo >= $hi) {
            $this->rec(4, 'return', 'One element: already sorted. Return.', get_defined_vars(), $o);
            $this->pop();
            return;
        }
        $mid = intdiv($lo + $hi, 2);
        $o['ptrs']['mid'] = $mid;
        $this->rec(6, 'assign', "\$mid = intdiv($lo + $hi, 2) → $mid", get_defined_vars(), $o);
        $this->rec(7, 'call', "Sort left half [$lo..$mid]", get_defined_vars(), ['range' => [$lo, $mid]] + $o);
        $this->mergeSort($arr, $lo, $mid);
        $this->rec(8, 'call', 'Sort right half [' . ($mid + 1) . "..$hi]", get_defined_vars(), ['range' => [$mid + 1, $hi]] + $o);
        $this->mergeSort($arr, $mid + 1, $hi);
        $this->rec(9, 'call', "Merge [$lo..$mid] with [" . ($mid + 1) . "..$hi]", get_defined_vars(), $o);
        $this->merge($arr, $lo, $mid, $hi);

        if ($this->depth() === 1) {
            $this->markAll(count($arr));
        }
        $this->rec(10, 'return', "mergeSort($lo, $hi) done: [$lo..$hi] is sorted", get_defined_vars(), $o);
        $this->pop();
    }

    private function merge(array &$arr, int $lo, int $mid, int $hi): void
    {
        $this->push("merge($lo, $mid, $hi)");
        $base = ['range' => [$lo, $hi]];
        $this->rec(12, 'call', "Enter merge(\$arr, $lo, $mid, $hi)", get_defined_vars(), $base + ['ptrs' => ['lo' => $lo, 'mid' => $mid, 'hi' => $hi]]);

        $left = array_slice($arr, $lo, $mid - $lo + 1);
        $this->rec(14, 'assign', '$left = ' . self::list($left), get_defined_vars(), $base + ['aux' => $this->lr($left, [], null, null)]);
        $right = array_slice($arr, $mid + 1, $hi - $mid);
        $this->rec(15, 'assign', '$right = ' . self::list($right), get_defined_vars(), $base + ['aux' => $this->lr($left, $right, null, null)]);
        $i = $j = 0;
        $this->rec(16, 'assign', '$i = $j = 0', get_defined_vars(), $base + ['aux' => $this->lr($left, $right, $i, $j)]);
        $k = $lo;
        $this->rec(17, 'assign', "\$k = \$lo → $k", get_defined_vars(), $base + ['aux' => $this->lr($left, $right, $i, $j), 'ptrs' => ['k' => $k]]);

        $nl = count($left);
        $nr = count($right);
        while ($this->loop(18, $i < $nl && $j < $nr, "\$i ($i) < $nl && \$j ($j) < $nr", get_defined_vars(), $base + ['aux' => $this->lr($left, $right, $i, $j), 'ptrs' => ['k' => $k]])) {
            $le = $this->le($left[$i], $right[$j]);
            $this->rec(19, 'compare', "\$left[$i] = {$left[$i]} <= \$right[$j] = {$right[$j]} is " . self::b($le), get_defined_vars(), $base + ['aux' => $this->lr($left, $right, $i, $j, [$i], [$j]), 'ptrs' => ['k' => $k]]);
            if ($le) {
                $arr[$k++] = $left[$i++];
                $this->writes++;
                $this->rec(20, 'write', '$arr[' . ($k - 1) . '] = $left[' . ($i - 1) . '] → ' . $left[$i - 1] . '; $k++, $i++', get_defined_vars(), $base + ['focus' => [$k - 1], 'aux' => $this->lr($left, $right, $i, $j, [$i - 1], []), 'ptrs' => ['k' => $k]]);
            } else {
                $arr[$k++] = $right[$j++];
                $this->writes++;
                $this->rec(22, 'write', '$arr[' . ($k - 1) . '] = $right[' . ($j - 1) . '] → ' . $right[$j - 1] . '; $k++, $j++', get_defined_vars(), $base + ['focus' => [$k - 1], 'aux' => $this->lr($left, $right, $i, $j, [], [$j - 1]), 'ptrs' => ['k' => $k]]);
            }
        }
        while ($this->loop(25, $i < $nl, "\$i ($i) < count(\$left) ($nl)", get_defined_vars(), $base + ['aux' => $this->lr($left, $right, $i, $j), 'ptrs' => ['k' => $k]])) {
            $arr[$k++] = $left[$i++];
            $this->writes++;
            $this->rec(26, 'write', 'Copy leftover $left[' . ($i - 1) . '] → $arr[' . ($k - 1) . ']', get_defined_vars(), $base + ['focus' => [$k - 1], 'aux' => $this->lr($left, $right, $i, $j, [$i - 1], []), 'ptrs' => ['k' => $k]]);
        }
        while ($this->loop(28, $j < $nr, "\$j ($j) < count(\$right) ($nr)", get_defined_vars(), $base + ['aux' => $this->lr($left, $right, $i, $j), 'ptrs' => ['k' => $k]])) {
            $arr[$k++] = $right[$j++];
            $this->writes++;
            $this->rec(29, 'write', 'Copy leftover $right[' . ($j - 1) . '] → $arr[' . ($k - 1) . ']', get_defined_vars(), $base + ['focus' => [$k - 1], 'aux' => $this->lr($left, $right, $i, $j, [], [$j - 1]), 'ptrs' => ['k' => $k]]);
        }

        $this->rec(31, 'return', "Merged [$lo..$hi] → " . self::list(array_slice($arr, $lo, $hi - $lo + 1)), get_defined_vars(), $base);
        $this->pop();
    }

    /** Auxiliary rows for $left / $right with their pointers. */
    private function lr(array $left, array $right, ?int $i, ?int $j, array $fl = [], array $fr = []): array
    {
        return [
            ['label' => '$left',  'kind' => 'cells', 'values' => $left,  'focus' => $fl, 'ptrs' => (object) ($i === null ? [] : ['i' => $i])],
            ['label' => '$right', 'kind' => 'cells', 'values' => $right, 'focus' => $fr, 'ptrs' => (object) ($j === null ? [] : ['j' => $j])],
        ];
    }
}
