<?php
declare(strict_types=1);

final class HeapSort extends Tracer
{
    protected const VARS = ['n', 'i', 'end', 'size', 'root', 'largest', 'left', 'right'];

    public static function meta(): array
    {
        return [
            'id' => 'heap', 'name' => 'Heap sort', 'group' => 'Efficient', 'file' => 'heapSort.php',
            'best' => 'O(n log n)', 'avg' => 'O(n log n)', 'worst' => 'O(n log n)', 'space' => 'O(1)', 'stable' => 'No',
            'summary' => 'Builds a max-heap in the array (children of i live at 2i+1 and 2i+2), then repeatedly swaps the root (largest) to the end and sifts the new root down.',
            'min' => -999, 'max' => 999, 'recursive' => true,
        ];
    }

    public static function source(): string
    {
        return <<<'PHP'
function heapSort(array $arr): array
{
    $n = count($arr);
    for ($i = intdiv($n, 2) - 1; $i >= 0; $i--) {
        heapify($arr, $n, $i);
    }
    for ($end = $n - 1; $end > 0; $end--) {
        [$arr[0], $arr[$end]] = [$arr[$end], $arr[0]];
        heapify($arr, $end, 0);
    }
    return $arr;
}

function heapify(array &$arr, int $size, int $root): void
{
    $largest = $root;
    $left = 2 * $root + 1;
    $right = 2 * $root + 2;
    if ($left < $size && $arr[$left] > $arr[$largest]) {
        $largest = $left;
    }
    if ($right < $size && $arr[$right] > $arr[$largest]) {
        $largest = $right;
    }
    if ($largest !== $root) {
        [$arr[$root], $arr[$largest]] = [$arr[$largest], $arr[$root]];
        heapify($arr, $size, $largest);
    }
}
PHP;
    }

    protected function run(array $arr): array
    {
        $this->push('heapSort()');
        $this->rec(1, 'enter', 'Call heapSort() with ' . count($arr) . ' elements', get_defined_vars());
        $n = count($arr);
        $this->rec(3, 'assign', "\$n = count(\$arr) → $n", get_defined_vars());

        for ($i = intdiv($n, 2) - 1; $this->loop(4, $i >= 0, "Build heap: \$i = $i; $i >= 0", get_defined_vars(), ['ptrs' => ['i' => $i]]); $i--) {
            $this->rec(5, 'call', "Heapify subtree rooted at index $i", get_defined_vars(), ['ptrs' => ['i' => $i]]);
            $this->heapify($arr, $n, $i);
        }

        for ($end = $n - 1; $this->loop(7, $end > 0, "\$end = $end; $end > 0", get_defined_vars(), ['range' => [0, $end], 'ptrs' => ['end' => $end]]); $end--) {
            [$arr[0], $arr[$end]] = [$arr[$end], $arr[0]];
            $this->swaps++;
            $this->writes += 2;
            $this->markSorted($end);
            $this->rec(8, 'swap', "Move max {$arr[$end]} from root to index $end (final)", get_defined_vars(), ['range' => [0, $end], 'focus' => [0, $end], 'ptrs' => ['root' => 0, 'end' => $end]]);
            $this->rec(9, 'call', "Restore heap of size $end from the root", get_defined_vars(), ['range' => [0, $end - 1], 'ptrs' => ['root' => 0]]);
            $this->heapify($arr, $end, 0);
        }

        $this->markAll($n);
        $this->rec(11, 'return', 'Return ' . self::list($arr), get_defined_vars());
        $this->pop();
        return $arr;
    }

    private function heapify(array &$arr, int $size, int $root): void
    {
        $this->push("heapify(size $size, root $root)");
        $range = [0, $size - 1];
        $this->rec(14, 'call', "Enter heapify(\$arr, $size, $root)", get_defined_vars(), ['range' => $range, 'ptrs' => ['root' => $root]]);
        $largest = $root;
        $this->rec(16, 'assign', "\$largest = \$root → $root", get_defined_vars(), ['range' => $range, 'ptrs' => ['root' => $root]]);
        $left = 2 * $root + 1;
        $this->rec(17, 'assign', "\$left = 2 × $root + 1 → $left", get_defined_vars(), ['range' => $range, 'ptrs' => ['root' => $root, 'left' => $left]]);
        $right = 2 * $root + 2;
        $ptrs = ['root' => $root, 'left' => $left, 'right' => $right];
        $this->rec(18, 'assign', "\$right = 2 × $root + 2 → $right", get_defined_vars(), ['range' => $range, 'ptrs' => $ptrs]);

        if ($left < $size) {
            $gt = $this->gt($arr[$left], $arr[$largest]);
            $this->rec(19, 'compare', "\$left ($left) < \$size ($size) && \$arr[$left] = {$arr[$left]} > \$arr[$largest] = {$arr[$largest]} is " . self::b($gt), get_defined_vars(), ['range' => $range, 'focus' => [$left, $largest], 'ptrs' => $ptrs]);
        } else {
            $gt = false;
            $this->rec(19, 'check', "\$left ($left) < \$size ($size) is false: no left child", get_defined_vars(), ['range' => $range, 'ptrs' => $ptrs]);
        }
        if ($gt) {
            $largest = $left;
            $this->rec(20, 'assign', "\$largest = \$left → $left", get_defined_vars(), ['range' => $range, 'ptrs' => $ptrs]);
        }

        if ($right < $size) {
            $gt = $this->gt($arr[$right], $arr[$largest]);
            $this->rec(22, 'compare', "\$right ($right) < \$size ($size) && \$arr[$right] = {$arr[$right]} > \$arr[$largest] = {$arr[$largest]} is " . self::b($gt), get_defined_vars(), ['range' => $range, 'focus' => [$right, $largest], 'ptrs' => $ptrs]);
        } else {
            $gt = false;
            $this->rec(22, 'check', "\$right ($right) < \$size ($size) is false: no right child", get_defined_vars(), ['range' => $range, 'ptrs' => $ptrs]);
        }
        if ($gt) {
            $largest = $right;
            $this->rec(23, 'assign', "\$largest = \$right → $right", get_defined_vars(), ['range' => $range, 'ptrs' => $ptrs]);
        }

        $this->rec(25, 'check', "\$largest ($largest) !== \$root ($root) is " . self::b($largest !== $root), get_defined_vars(), ['range' => $range, 'ptrs' => $ptrs]);
        if ($largest !== $root) {
            [$arr[$root], $arr[$largest]] = [$arr[$largest], $arr[$root]];
            $this->swaps++;
            $this->writes += 2;
            $this->rec(26, 'swap', "Swap \$arr[$root] and \$arr[$largest]", get_defined_vars(), ['range' => $range, 'focus' => [$root, $largest], 'ptrs' => $ptrs]);
            $this->rec(27, 'call', "Sift down: heapify at index $largest", get_defined_vars(), ['range' => $range, 'ptrs' => ['root' => $largest]]);
            $this->heapify($arr, $size, $largest);
        }

        $this->rec(29, 'return', "heapify(size $size, root $root) returns", get_defined_vars(), ['range' => $range]);
        $this->pop();
    }
}
