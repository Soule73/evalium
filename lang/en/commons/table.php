<?php

declare(strict_types=1);

/**
 * DataTable component shared translations.
 * Frontend key: t('commons/table.no_results'), t('commons/table.items_selected'), etc.
 */
return [
  // Search & filters
  'search_placeholder' => 'Search...',
  'clear_search' => 'Clear search',
  'reset_filters' => 'Reset filters',

  // Empty states
  'no_results' => 'No results found.',
  'no_results_subtitle' => 'Try adjusting your search criteria.',
  'no_data' => 'No data available.',
  'no_data_subtitle' => 'No items to display.',
  'empty_search_title' => 'No results for this search',

  // Column headers (generic)
  'actions' => 'Actions',
  'status' => 'Status',
  'name' => 'Name',
  'email' => 'Email',
  'created_at' => 'Created',
  'updated_at' => 'Updated',
  'date' => 'Date',

  // Selection (pipe for pluralization via translateKey)
  'items_selected' => '1 item selected|:count items selected',
  'select_all' => 'Select All',
  'deselect_all' => 'Deselect All',

  // Pagination
  'page_of' => 'Page :current of :total',
  'previous' => 'Previous',
  'next' => 'Next',
  'per_page' => 'Per page',

  // Loading
  'loading' => 'Loading...',
];
