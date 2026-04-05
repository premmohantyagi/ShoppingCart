<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    public function scopeFilter(Builder $query, Request $request): Builder
    {
        foreach ($this->getFilterable() as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('search') && method_exists($this, 'getSearchable')) {
            $search = $request->input('search');
            $searchable = $this->getSearchable();

            $query->where(function (Builder $q) use ($search, $searchable) {
                foreach ($searchable as $column) {
                    $q->orWhere($column, 'LIKE', "%{$search}%");
                }
            });
        }

        return $query;
    }

    protected function getFilterable(): array
    {
        return $this->filterable ?? [];
    }
}
