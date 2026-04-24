<?php

namespace App\Helpers;

/**
 * Centralized query parameter parsing for API endpoints.
 * Keeps controller code DRY by extracting common filter logic.
 */
class QueryFilter
{
    protected array $params = [];

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function getAll(): array
    {
        return $this->params;
    }

    public function dateFrom(): ?string
    {
        return $this->get('date_from');
    }

    public function dateTo(): ?string
    {
        return $this->get('date_to');
    }

    public function status(): ?string
    {
        return $this->get('status');
    }

    public function sortStatus(): ?string
    {
        return $this->get('sort_status');
    }

    public function trashbagId(): ?int
    {
        return $this->get('trashbag_id') ? (int) $this->get('trashbag_id') : null;
    }

    public function wasteId(): ?int
    {
        return $this->get('waste_id') ? (int) $this->get('waste_id') : null;
    }

    public function groupBy(): string
    {
        return $this->get('group_by', 'day');
    }

    public function page(): int
    {
        return (int) $this->get('page', 1);
    }

    public function perPage(): int
    {
        return (int) $this->get('per_page', 15);
    }
}
