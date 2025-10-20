# DataTable - Sélection en masse (Bulk Selection)

## Vue d'ensemble

Le composant `DataTable` inclut désormais des fonctionnalités de sélection en masse permettant de :
- Sélectionner/désélectionner des éléments individuels
- Sélectionner/désélectionner tous les éléments de la page actuelle
- Afficher des actions en masse pour les éléments sélectionnés
- Réinitialiser la sélection lors du changement de page

## Configuration

### 1. Activer la sélection

Pour activer la sélection en masse, ajoutez `enableSelection: true` dans votre configuration :

```tsx
const dataTableConfig: DataTableConfig<Group> = {
    enableSelection: true,
    columns: [
        // ... vos colonnes
    ],
    // ...
};
```

### 2. Ajouter des actions en masse

Définissez les actions à afficher lorsque des éléments sont sélectionnés :

```tsx
import { TrashIcon, ArchiveBoxIcon } from '@heroicons/react/24/outline';

const dataTableConfig: DataTableConfig<Group> = {
    enableSelection: true,
    selectionActions: (selectedIds) => (
        <>
            <Button
                onClick={() => handleBulkDelete(selectedIds)}
                color="danger"
                variant="outline"
                size="sm"
            >
                <TrashIcon className="w-4 h-4 mr-2" />
                Supprimer ({selectedIds.length})
            </Button>
            <Button
                onClick={() => handleBulkArchive(selectedIds)}
                color="secondary"
                variant="outline"
                size="sm"
            >
                <ArchiveBoxIcon className="w-4 h-4 mr-2" />
                Archiver
            </Button>
        </>
    ),
    columns: [
        // ... vos colonnes
    ],
};
```

### 3. Gérer les changements de sélection

Utilisez le callback `onSelectionChange` pour réagir aux changements :

```tsx
const [selectedItems, setSelectedItems] = useState<(number | string)[]>([]);

<DataTable
    data={groups}
    config={dataTableConfig}
    onSelectionChange={(ids) => {
        setSelectedItems(ids);
        console.log('Éléments sélectionnés:', ids);
    }}
/>
```

## Exemple complet

```tsx
import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { DataTable } from '@/Components/DataTable';
import { Button } from '@/Components/Button';
import { TrashIcon, ArchiveBoxIcon } from '@heroicons/react/24/outline';
import { Group } from '@/types';
import { DataTableConfig, PaginationType } from '@/types/datatable';

interface Props {
    groups: PaginationType<Group>;
}

export default function GroupIndex({ groups }: Props) {
    const [selectedGroups, setSelectedGroups] = useState<(number | string)[]>([]);

    const handleBulkDelete = (ids: (number | string)[]) => {
        if (confirm(`Voulez-vous vraiment supprimer ${ids.length} groupe(s) ?`)) {
            router.post(route('admin.groups.bulk-delete'), {
                ids: ids
            });
        }
    };

    const handleBulkArchive = (ids: (number | string)[]) => {
        router.post(route('admin.groups.bulk-archive'), {
            ids: ids
        });
    };

    const dataTableConfig: DataTableConfig<Group> = {
        enableSelection: true,
        selectionActions: (selectedIds) => (
            <>
                <Button
                    onClick={() => handleBulkDelete(selectedIds)}
                    color="danger"
                    variant="outline"
                    size="sm"
                >
                    <TrashIcon className="w-4 h-4 mr-2" />
                    Supprimer ({selectedIds.length})
                </Button>
                <Button
                    onClick={() => handleBulkArchive(selectedIds)}
                    color="secondary"
                    variant="outline"
                    size="sm"
                >
                    <ArchiveBoxIcon className="w-4 h-4 mr-2" />
                    Archiver ({selectedIds.length})
                </Button>
            </>
        ),
        columns: [
            {
                key: 'name',
                label: 'Groupe',
                render: (group) => (
                    <div>
                        <div className="text-sm font-medium text-gray-900">
                            {group.display_name}
                        </div>
                    </div>
                )
            },
            // ... autres colonnes
        ],
        searchPlaceholder: 'Rechercher un groupe...',
    };

    return (
        <AuthenticatedLayout title="Groupes">
            <Section
                title="Gestion des groupes"
                subtitle="Gérez les groupes d'étudiants"
            >
                <DataTable
                    data={groups}
                    config={dataTableConfig}
                    onSelectionChange={setSelectedGroups}
                />
            </Section>
        </AuthenticatedLayout>
    );
}
```

## API du hook useDataTable

### Actions de sélection

Le hook `useDataTable` expose les actions suivantes pour la sélection :

```typescript
interface SelectionActions {
    // Basculer la sélection d'un élément
    toggleItem: (id: number | string) => void;
    
    // Basculer la sélection de tous les éléments de la page
    toggleAllOnPage: () => void;
    
    // Sélectionner tous les éléments de la page
    selectAll: () => void;
    
    // Désélectionner tous les éléments
    deselectAll: () => void;
    
    // Vérifier si un élément est sélectionné
    isItemSelected: (id: number | string) => boolean;
    
    // Obtenir la liste des IDs sélectionnés
    getSelectedItems: () => (number | string)[];
    
    // Obtenir le nombre d'éléments sélectionnés
    getSelectedCount: () => number;
}
```

### État de sélection

```typescript
interface SelectionState {
    selectedItems: Set<number | string>;
    allItemsOnPageSelected: boolean;
    someItemsOnPageSelected: boolean;
    selectedCount: number;
}
```

## Notes importantes

1. **Type des données** : Vos données doivent avoir un champ `id` (number ou string)
2. **Réinitialisation** : La sélection est automatiquement réinitialisée lors du changement de page
3. **Performance** : Les sélections sont stockées en mémoire et ne persistent pas entre les rechargements de page
4. **Accessibilité** : Les checkboxes incluent des labels ARIA appropriés

## Comportements

### Checkbox d'en-tête
- **Non coché** : Aucun élément de la page n'est sélectionné
- **Coché** : Tous les éléments de la page sont sélectionnés
- **Indéterminé** : Certains éléments de la page sont sélectionnés

### Actions en masse
- Apparaissent uniquement si au moins un élément est sélectionné
- Affichent le nombre d'éléments sélectionnés
- Incluent un bouton pour désélectionner tout

## Backend (Laravel)

Exemple de route pour gérer les actions en masse :

```php
// routes/web.php
Route::post('/admin/groups/bulk-delete', [GroupController::class, 'bulkDelete'])
    ->name('admin.groups.bulk-delete');

// app/Http/Controllers/Admin/GroupController.php
public function bulkDelete(Request $request)
{
    $request->validate([
        'ids' => 'required|array',
        'ids.*' => 'exists:groups,id'
    ]);

    Group::whereIn('id', $request->ids)->delete();

    return redirect()
        ->route('admin.groups.index')
        ->with('success', count($request->ids) . ' groupe(s) supprimé(s)');
}
```

## Personnalisation CSS

Les checkboxes utilisent les classes Tailwind par défaut. Vous pouvez les personnaliser en modifiant :
- `BulkActions.tsx` pour la barre d'actions
- Les classes dans `DataTable.tsx` pour les checkboxes

## Exemple avec confirmation modale

```tsx
import { useState } from 'react';
import ConfirmationModal from '@/Components/ConfirmationModal';

const [showDeleteModal, setShowDeleteModal] = useState(false);
const [itemsToDelete, setItemsToDelete] = useState<(number | string)[]>([]);

const handleBulkDeleteClick = (ids: (number | string)[]) => {
    setItemsToDelete(ids);
    setShowDeleteModal(true);
};

const confirmBulkDelete = () => {
    router.post(route('admin.groups.bulk-delete'), {
        ids: itemsToDelete
    }, {
        onSuccess: () => setShowDeleteModal(false)
    });
};

// Dans le JSX
<ConfirmationModal
    isOpen={showDeleteModal}
    onClose={() => setShowDeleteModal(false)}
    onConfirm={confirmBulkDelete}
    title="Supprimer les groupes"
    message={`Voulez-vous vraiment supprimer ${itemsToDelete.length} groupe(s) ?`}
    confirmText="Supprimer"
    type="danger"
/>
```
