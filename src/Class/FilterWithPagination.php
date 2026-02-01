<?php

namespace App\Class;

use Doctrine\ORM\QueryBuilder;
use DateTime;

class FilterWithPagination
{
    const OPERATORS = [
        'EQUAL' => 'equal',
        'NOT_EQUAL' => 'not_equal',
        'CONTAIN' => 'contain',
        'LESS_THAN' => 'less_than',
        'LESS_THAN_OR_EQUAL_TO' => 'less_than_or_equal_to',
        'GREATER_THAN' => 'greater_than',
        'GREATER_THAN_OR_EQUAL_TO' => 'greater_than_or_equal_to',
        'EQUAL_TO' => 'equal_to',
        'BETWEEN' => 'between',
        'INCLUDES' => 'includes',
        'NOT_INCLUDES' => 'not_includes',
    ];

    public static function findWithPagination(
        QueryBuilder $qb,
        array $filters,
        string $alias = 't'
    ): array {

        self::processFilters($qb, $filters, $alias);
        self::processSorting($qb, $filters, $alias);

        return self::processPagination($qb, $filters);
    }


    private static function processFilters(QueryBuilder $qb, array $filters, string $alias): void
    {
        if (!isset($filters['f']) || !is_array($filters['f'])) return;

        foreach ($filters['f'] as $index => $filter) {
            if (!self::isValidFilter($filter)) continue;

            $field = "{$alias}.{$filter['field']}";
            $operator = $filter['operator'];
            $value = $filter['value'];

            self::applyFilter($qb, $field, $operator, $value, $index);
        }
    }


    private static function applyFilter(QueryBuilder $qb, string $field, string $op, $value, int $index): void
    {
        $param = "param_$index";

        switch ($op) {
            case self::OPERATORS['EQUAL']:
                $qb->andWhere("$field = :$param")->setParameter($param, $value);
                break;

            case self::OPERATORS['NOT_EQUAL']:
                $qb->andWhere("$field != :$param")->setParameter($param, $value);
                break;

            case self::OPERATORS['CONTAIN']:
                $qb->andWhere("LOWER($field) LIKE LOWER(:$param)")->setParameter($param, "%$value%");
                break;

            case self::OPERATORS['LESS_THAN']:
                $qb->andWhere("$field < :$param")->setParameter($param, $value);
                break;

            case self::OPERATORS['LESS_THAN_OR_EQUAL_TO']:
                $qb->andWhere("$field <= :$param")->setParameter($param, $value);
                break;

            case self::OPERATORS['GREATER_THAN']:
                $qb->andWhere("$field > :$param")->setParameter($param, $value);
                break;

            case self::OPERATORS['GREATER_THAN_OR_EQUAL_TO']:
                $qb->andWhere("$field >= :$param")->setParameter($param, $value);
                break;

            case self::OPERATORS['EQUAL_TO']:
                self::applyEqualToDate($qb, $field, $value);
                break;

            case self::OPERATORS['BETWEEN']:
                self::applyBetween($qb, $field, $value, $index);
                break;

            case self::OPERATORS['INCLUDES']:
                $qb->andWhere("$field IN (:$param)")
                    ->setParameter($param, $value);
                break;

            case self::OPERATORS['NOT_INCLUDES']:
                $qb->andWhere("$field NOT IN (:$param)")
                    ->setParameter($param, $value);
                break;
        }
    }


    private static function applyEqualToDate(QueryBuilder $qb, string $field, $value): void
    {
        $start = new DateTime($value . " 00:00:00");
        $end   = new DateTime($value . " 23:59:59");

        $qb->andWhere("$field BETWEEN :startDate AND :endDate")
            ->setParameter('startDate', $start)
            ->setParameter('endDate', $end);
    }


    private static function applyBetween(QueryBuilder $qb, string $field, array $value, int $index): void
    {
        $start = $value[0] ?? null;
        $end   = $value[1] ?? null;

        // Nếu cả hai đều null thì không áp dụng filter
        if (!$start && !$end) {
            return;
        }

        // Nếu chỉ có end date (lấy tất cả từ quá khứ đến end date)
        if (!$start && $end) {
            $endDateTime = new DateTime($end . " 23:59:59");
            $qb->andWhere("$field <= :bEnd$index")
                ->setParameter("bEnd$index", $endDateTime);
            return;
        }

        // Nếu chỉ có start date (lấy từ start date đến hiện tại)
        if ($start && !$end) {
            $startDateTime = new DateTime($start . " 00:00:00");
            $qb->andWhere("$field >= :bStart$index")
                ->setParameter("bStart$index", $startDateTime);
            return;
        }

        // Nếu có cả start và end date
        $startDateTime = new DateTime($start . " 00:00:00");
        $endDateTime   = new DateTime($end . " 23:59:59");

        $qb->andWhere("$field BETWEEN :bStart$index AND :bEnd$index")
            ->setParameter("bStart$index", $startDateTime)
            ->setParameter("bEnd$index", $endDateTime);
    }


    private static function processSorting(QueryBuilder $qb, array $filters, string $alias): void
    {
        if (!isset($filters['sort_column'])) return;

        $column = "{$alias}.{$filters['sort_column']}";
        $direction = strtolower($filters['sort_direction'] ?? 'asc');

        $qb->orderBy($column, $direction);
    }


    private static function processPagination(QueryBuilder $qb, array $filters): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = (int)($filters['limit'] ?? 10);

        $totalQb = clone $qb;
        $totalQb->resetDQLPart('orderBy');
        $total = (int) $totalQb->select("COUNT(1)")->getQuery()->getSingleScalarResult();

        if ($limit > 0) {
            $qb->setMaxResults($limit);
            $qb->setFirstResult(($page - 1) * $limit);
        }

        $data = $qb->getQuery()->getResult();
        $countCurrent = count($data);

        return [
            'collection' => $data,
            'total' => $total,
            'total_current' => $countCurrent,
            'current_page' => $page,
            'last_page' => $limit > 0 ? ceil($total / $limit) : 1,
            'from' => ($page - 1) * $limit + 1,
            'to' => ($page - 1) * $limit + $countCurrent,
        ];
    }


    private static function isValidFilter(array $filter): bool
    {
        return isset($filter['field'], $filter['operator'], $filter['value']);
    }
}
