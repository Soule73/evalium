# MarkdownEditor Component

Un éditeur Markdown avancé basé sur EasyMDE avec de nombreuses options de personnalisation.

## Installation

```tsx
import { MarkdownEditor } from '@/Components/form/MarkdownEditor';
```

## Utilisation de base

```tsx
import { useState } from 'react';
import { MarkdownEditor } from '@/Components/form/MarkdownEditor';

function BasicExample() {
    const [content, setContent] = useState('');

    return (
        <MarkdownEditor
            value={content}
            onChange={setContent}
            placeholder="Saisissez votre texte..."
            label="Description"
        />
    );
}
```

## Props de base

| Prop | Type | Défaut | Description |
|------|------|--------|-------------|
| `value` | `string` | `''` | Valeur actuelle de l'éditeur |
| `onChange` | `(value: string) => void` | - | Callback appelé lors des changements |
| `placeholder` | `string` | `'Saisissez votre réponse ici...'` | Texte d'aide |
| `label` | `string` | - | Label du champ |
| `helpText` | `string` | - | Texte d'aide |
| `error` | `string` | - | Message d'erreur |
| `disabled` | `boolean` | `false` | Désactive l'éditeur |
| `required` | `boolean` | `false` | Champ requis |
| `className` | `string` | `''` | Classes CSS personnalisées |

## Fonctionnalités de la toolbar

### Exemple avec toolbar personnalisée

```tsx
<MarkdownEditor
    value={content}
    onChange={setContent}
    enableBold={true}
    enableItalic={true}
    enableHeading={true}
    enableStrikethrough={true}
    enableCode={true}
    enableQuote={true}
    enableUnorderedList={true}
    enableOrderedList={true}
    enableLink={true}
    enableImage={true}
    enableTable={true}
    enableHorizontalRule={true}
    enablePreview={true}
    enableSideBySide={false}
    enableFullscreen={true}
    enableGuide={true}
    enableUndo={true}
    enableRedo={true}
/>
```

### Props des fonctionnalités

| Prop | Type | Défaut | Description |
|------|------|--------|-------------|
| `enableBold` | `boolean` | `true` | Bouton gras |
| `enableItalic` | `boolean` | `true` | Bouton italique |
| `enableHeading` | `boolean` | `true` | Bouton titre |
| `enableStrikethrough` | `boolean` | `false` | Bouton barré |
| `enableCode` | `boolean` | `false` | Bouton code |
| `enableQuote` | `boolean` | `true` | Bouton citation |
| `enableUnorderedList` | `boolean` | `true` | Liste non ordonnée |
| `enableOrderedList` | `boolean` | `true` | Liste ordonnée |
| `enableLink` | `boolean` | `true` | Bouton lien |
| `enableImage` | `boolean` | `false` | Bouton image |
| `enableTable` | `boolean` | `true` | Bouton tableau |
| `enableHorizontalRule` | `boolean` | `false` | Ligne horizontale |
| `enablePreview` | `boolean` | `true` | Bouton prévisualisation |
| `enableSideBySide` | `boolean` | `false` | Mode côte à côte |
| `enableFullscreen` | `boolean` | `false` | Mode plein écran |
| `enableGuide` | `boolean` | `true` | Guide d'aide |
| `enableUndo` | `boolean` | `false` | Bouton annuler |
| `enableRedo` | `boolean` | `false` | Bouton refaire |
| `enableMathInline` | `boolean` | `false` | Formules mathématiques inline |
| `enableMathDisplay` | `boolean` | `false` | Formules mathématiques display |

## Options avancées

### Exemple complet avec toutes les options

```tsx
<MarkdownEditor
    value={content}
    onChange={setContent}
    
    // Options d'affichage
    enableSpellChecker={false}
    enableStatus={true}
    enableAutofocus={false}
    enableLineNumbers={true}
    enableLineWrapping={true}
    tabSize={2}
    minHeight="200px"
    maxHeight="500px"
    theme="dark"
    
    // Toolbar personnalisée
    customToolbar={[
        'bold', 'italic', '|',
        'heading', 'quote', '|',
        'unordered-list', 'ordered-list', '|',
        'link', 'table', '|',
        'preview', 'fullscreen'
    ]}
    
    // Upload d'images
    enableImageUpload={true}
    imageMaxSize={5242880} // 5MB
    imageUploadEndpoint="/api/upload"
    
    // Classes CSS personnalisées
    editorClassName="custom-editor-style"
/>
```

### Props des options avancées

| Prop | Type | Défaut | Description |
|------|------|--------|-------------|
| `enableSpellChecker` | `boolean` | `false` | Vérificateur orthographique |
| `enableStatus` | `boolean` | `false` | Barre de statut |
| `enableAutofocus` | `boolean` | `false` | Focus automatique |
| `enableLineNumbers` | `boolean` | `false` | Numéros de ligne |
| `enableLineWrapping` | `boolean` | `true` | Retour à la ligne |
| `tabSize` | `number` | `4` | Taille des tabulations |
| `minHeight` | `string` | - | Hauteur minimale |
| `maxHeight` | `string` | - | Hauteur maximale |
| `theme` | `string` | - | Thème CSS |
| `editorClassName` | `string` | `''` | Classes CSS de l'éditeur |

## Toolbar personnalisée

### Utilisation avec customToolbar

```tsx
<MarkdownEditor
    customToolbar={[
        'bold', 'italic', 'strikethrough',
        '|',
        'heading', 'quote',
        '|',
        'unordered-list', 'ordered-list',
        '|',
        'link', 'image', 'table',
        '|',
        'math-inline', 'math-display',
        '|',
        'preview', 'side-by-side', 'fullscreen',
        '|',
        'guide'
    ]}
/>
```

### Boutons disponibles

- **Formatage** : `bold`, `italic`, `strikethrough`, `code`, `heading`
- **Listes** : `unordered-list`, `ordered-list`, `quote`
- **Insertion** : `link`, `image`, `table`, `horizontal-rule`
- **Mathématiques** : `math-inline`, `math-display`
- **Actions** : `undo`, `redo`
- **Affichage** : `preview`, `side-by-side`, `fullscreen`
- **Aide** : `guide`
- **Séparateur** : `|`

## Upload d'images

### Configuration basique

```tsx
<MarkdownEditor
    enableImageUpload={true}
    imageMaxSize={2048000} 
    imageUploadEndpoint="/api/upload-image"
/>
```

## Formules mathématiques

Le composant `MarkdownEditor` supporte les formules mathématiques grâce à KaTeX. Deux types de formules sont disponibles :

### Formules inline

Utilisez `$...$` pour les formules dans le texte :

```tsx
<MarkdownEditor
    value={content}
    onChange={setContent}
    enableMathInline={true}
    placeholder="Exemple: La formule $E = mc^2$ d'Einstein..."
/>
```

**Exemples de formules inline :**
- `$x^2 + y^2 = z^2$` → x² + y² = z²
- `$\frac{a}{b}$` → a/b
- `$\sqrt{x}$` → √x
- `$\alpha + \beta = \gamma$` → α + β = γ

### Formules display

Utilisez `$$...$$` pour les formules centrées et sur plusieurs lignes :

```tsx
<MarkdownEditor
    value={content}
    onChange={setContent}
    enableMathDisplay={true}
    placeholder="Exemple: $$\int_{-\infty}^{\infty} e^{-x^2} dx = \sqrt{\pi}$$"
/>
```

**Exemples de formules display :**
```
$$\sum_{i=1}^{n} i = \frac{n(n+1)}{2}$$

$$\int_{a}^{b} f(x) dx = F(b) - F(a)$$

$$\lim_{x \to \infty} \frac{1}{x} = 0$$
```

### Boutons de la toolbar

Activez les boutons pour insérer facilement des formules :

```tsx
<MarkdownEditor
    enableMathInline={true}
    enableMathDisplay={true}
    // Autres props...
/>
```

- **Bouton ∑** : Insère `$formule$` pour les formules inline
- **Bouton ∫** : Insère `$$\nformule\n$$` pour les formules display

### Exemple complet avec mathématiques

```tsx
<MarkdownEditor
    value={mathContent}
    onChange={setMathContent}
    label="Contenu mathématique"
    placeholder="Saisissez vos formules..."
    
    // Fonctionnalités mathématiques
    enableMathInline={true}
    enableMathDisplay={true}
    
    // Autres fonctionnalités utiles
    enableBold={true}
    enableItalic={true}
    enablePreview={true}
    enableSideBySide={true}
    
    minHeight="300px"
/>
```

### Syntaxe KaTeX supportée

Le composant utilise KaTeX pour le rendu des formules. Voici quelques exemples de syntaxe :

| Syntaxe | Résultat | Description |
|---------|----------|-------------|
| `x^2` | x² | Exposant |
| `x_1` | x₁ | Indice |
| `\frac{a}{b}` | a/b | Fraction |
| `\sqrt{x}` | √x | Racine carrée |
| `\sqrt[n]{x}` | ⁿ√x | Racine nième |
| `\sum_{i=1}^{n}` | ∑ᵢ₌₁ⁿ | Somme |
| `\int_{a}^{b}` | ∫ₐᵇ | Intégrale |
| `\lim_{x \to \infty}` | lim(x→∞) | Limite |
| `\alpha, \beta, \gamma` | α, β, γ | Lettres grecques |
| `\sin, \cos, \tan` | sin, cos, tan | Fonctions |
| `\leq, \geq, \neq` | ≤, ≥, ≠ | Comparaisons |

### Gestion des erreurs

Si une formule contient une erreur de syntaxe, elle sera affichée en rouge avec le message d'erreur :

```
Erreur: \invalid_syntax
```

### Styles personnalisés

Les formules mathématiques peuvent être stylisées via CSS :

```css
.EasyMDEContainer .editor-preview .katex {
    font-size: 1.2em;
    color: #2563eb;
}

.EasyMDEContainer .editor-preview .katex-display .katex {
    background: #f0f9ff;
    border: 2px solid #3b82f6;
}
```

### Fonction d'upload personnalisée

```tsx
const handleImageUpload = (
    file: File,
    onSuccess: (url: string) => void,
    onError: (error: string) => void
) => {
    const formData = new FormData();
    formData.append('image', file);
    
    fetch('/api/upload', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            onSuccess(data.url);
        } else {
            onError(data.message);
        }
    })
    .catch(error => {
        onError('Erreur lors de l\'upload');
    });
};

<MarkdownEditor
    enableImageUpload={true}
    imageUploadFunction={handleImageUpload}
    imageMaxSize={5242880} // 5MB
/>
```

## Exemples d'usage

### Éditeur minimal pour commentaires

```tsx
<MarkdownEditor
    value={comment}
    onChange={setComment}
    placeholder="Écrivez votre commentaire..."
    enableBold={true}
    enableItalic={true}
    enableLink={true}
    enableQuote={true}
    enablePreview={true}
    enableGuide={false}
    minHeight="100px"
    maxHeight="300px"
/>
```

### Éditeur complet pour articles

```tsx
<MarkdownEditor
    value={article}
    onChange={setArticle}
    label="Contenu de l'article"
    placeholder="Rédigez votre article..."
    
    // Toutes les fonctionnalités activées
    enableBold={true}
    enableItalic={true}
    enableHeading={true}
    enableStrikethrough={true}
    enableCode={true}
    enableQuote={true}
    enableUnorderedList={true}
    enableOrderedList={true}
    enableLink={true}
    enableImage={true}
    enableTable={true}
    enableHorizontalRule={true}
    enablePreview={true}
    enableSideBySide={true}
    enableFullscreen={true}
    enableGuide={true}
    enableUndo={true}
    enableRedo={true}
    
    // Formules mathématiques
    enableMathInline={true}
    enableMathDisplay={true}
    
    // Options avancées
    enableStatus={true}
    enableLineNumbers={false}
    minHeight="400px"
    
    // Upload d'images
    enableImageUpload={true}
    imageUploadEndpoint="/api/articles/upload-image"
    imageMaxSize={10485760} // 10MB
/>
```

### Éditeur pour formulaires d'examen

```tsx
<MarkdownEditor
    value={response}
    onChange={setResponse}
    placeholder="Saisissez votre réponse..."
    
    // Fonctionnalités limitées pour éviter la triche
    enableBold={true}
    enableItalic={true}
    enableUnorderedList={true}
    enableOrderedList={true}
    enablePreview={true}
    enableGuide={false}
    
    // Pas d'upload d'images
    enableImage={false}
    enableImageUpload={false}
    
    // Pas de plein écran
    enableFullscreen={false}
    enableSideBySide={false}
    
    minHeight="200px"
    maxHeight="400px"
/>
```

### Éditeur pour cours de mathématiques

```tsx
<MarkdownEditor
    value={mathLesson}
    onChange={setMathLesson}
    label="Contenu du cours"
    placeholder="Rédigez votre cours avec des formules mathématiques..."
    
    // Fonctionnalités essentielles
    enableBold={true}
    enableItalic={true}
    enableHeading={true}
    enableUnorderedList={true}
    enableOrderedList={true}
    enableTable={true}
    
    // Formules mathématiques activées
    enableMathInline={true}
    enableMathDisplay={true}
    
    // Prévisualisation nécessaire pour voir les formules
    enablePreview={true}
    enableSideBySide={true}
    
    // Options avancées
    minHeight="500px"
    enableLineNumbers={true}
    
    // Guide pour la syntaxe KaTeX
    enableGuide={true}
/>
```

## Gestion de l'état avec useRef

```tsx
import { useRef } from 'react';
import { MarkdownEditor, MarkdownEditorRef } from '@/Components/form/MarkdownEditor';

function ExampleWithRef() {
    const editorRef = useRef<MarkdownEditorRef>(null);
    
    const handleFocus = () => {
        editorRef.current?.focus();
    };
    
    const handleGetValue = () => {
        const value = editorRef.current?.getValue();
        console.log('Current value:', value);
    };
    
    const handleSetValue = () => {
        editorRef.current?.setValue('# Nouveau contenu');
    };
    
    return (
        <div>
            <MarkdownEditor
                ref={editorRef}
                placeholder="Contenu..."
            />
            
            <div className="mt-4 space-x-2">
                <button onClick={handleFocus}>Focus</button>
                <button onClick={handleGetValue}>Get Value</button>
                <button onClick={handleSetValue}>Set Value</button>
            </div>
        </div>
    );
}
```

## Personnalisation CSS

### Classes CSS disponibles

- `.markdown-editor-field` : Container principal
- `.markdown-editor-container` : Container de l'éditeur
- `.EasyMDEContainer` : Container EasyMDE
- `.editor-toolbar` : Barre d'outils
- `.editor-preview` : Zone de prévisualisation

### Exemple de styles personnalisés

```css
.custom-editor-style .EasyMDEContainer {
    border: 2px solid #3b82f6;
    border-radius: 12px;
}

.custom-editor-style .editor-toolbar {
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.custom-editor-style .CodeMirror {
    font-family: 'JetBrains Mono', monospace;
    font-size: 14px;
    line-height: 1.6;
}

.custom-editor-style .editor-preview {
    background-color: #ffffff;
    padding: 20px;
}
```

## Raccourcis clavier

| Raccourci | Action |
|-----------|--------|
| `Ctrl+B` | Gras (si activé) |
| `Ctrl+I` | Italique (si activé) |
| `Ctrl+P` | Prévisualisation (si activé) |
| `F9` | Mode côte à côte (si activé) |
| `F11` | Plein écran (si activé) |

## Bonnes pratiques

1. **Performance** : Utilisez `enableAutofocus={false}` par défaut pour éviter les problèmes de focus
2. **Sécurité** : Désactivez l'upload d'images pour les formulaires sensibles
3. **UX** : Limitez les fonctionnalités selon le contexte d'usage
4. **Accessibilité** : Toujours fournir un `label` descriptif
5. **Validation** : Gérez les erreurs avec la prop `error`