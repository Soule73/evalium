<?php

declare(strict_types=1);

/**
 * DataTable component shared translations.
 * Frontend key: t('commons/table.no_results'), t('commons/table.items_selected'), etc.
 */
return [
    // Search & filters
    'search_placeholder' => 'Rechercher...',
    'clear_search' => 'Effacer la recherche',
    'reset_filters' => 'Réinitialiser les filtres',

    // Empty states
    'no_results' => 'Aucun résultat trouvé.',
    'no_results_subtitle' => 'Essayez de modifier vos critères de recherche.',
    'no_data' => 'Aucune donnée disponible.',
    'no_data_subtitle' => 'Aucun élément à afficher.',
    'empty_search_title' => 'Aucun résultat pour cette recherche',
    'try_different_search' => 'Essayez un autre terme de recherche',

    // Column headers (generic)
    'actions' => 'Actions',
    'status' => 'Statut',
    'name' => 'Nom',
    'email' => 'Email',
    'created_at' => 'Créé le',
    'updated_at' => 'Mis à jour le',
    'date' => 'Date',

    // Selection (pipe for pluralization via translateKey)
    'items_selected' => '1 élément sélectionné|:count éléments sélectionnés',
    'select_all' => 'Tout sélectionner',
    'deselect_all' => 'Tout désélectionner',

    // Pagination
    'page_of' => 'Page :current sur :total',
    'previous' => 'Précédent',
    'next' => 'Suivant',
    'per_page' => 'Par page',

    // Loading
    'loading' => 'Chargement...',
];
