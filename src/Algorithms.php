<?php
declare(strict_types=1);

require_once __DIR__ . '/Tracer.php';
foreach (glob(__DIR__ . '/algorithms/*.php') as $file) {
    require_once $file;
}

/** Registry of available algorithms, in dropdown order. */
final class Algorithms
{
    /** @var list<class-string<Tracer>> */
    private const CLASSES = [
        BubbleSort::class,
        CocktailSort::class,
        SelectionSort::class,
        InsertionSort::class,
        ShellSort::class,
        MergeSort::class,
        QuickSort::class,
        HeapSort::class,
        CountingSort::class,
        RadixSort::class,
    ];

    /** @return array<string, class-string<Tracer>> id => class */
    public static function map(): array
    {
        $out = [];
        foreach (self::CLASSES as $class) {
            $out[$class::meta()['id']] = $class;
        }
        return $out;
    }

    /** @return class-string<Tracer>|null */
    public static function get(string $id): ?string
    {
        return self::map()[$id] ?? null;
    }

    /** @return array<string, array<string,mixed>> id => meta */
    public static function metas(): array
    {
        return array_map(fn ($c) => $c::meta(), self::map());
    }
}
