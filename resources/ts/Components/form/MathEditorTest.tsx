import React, { useState } from 'react';
import { MarkdownEditor } from './MarkdownEditor';
import MarkdownRenderer from './MarkdownRenderer';

/**
 * Composant de test pour vérifier le support des formules mathématiques
 * dans MarkdownEditor et MarkdownRenderer
 */
const MathEditorTest: React.FC = () => {
    const [content, setContent] = useState(`# Test des formules mathématiques

## Formules inline

Voici quelques exemples de formules inline :
- L'équation d'Einstein : $E = mc^2$
- Le théorème de Pythagore : $a^2 + b^2 = c^2$
- Une fraction : $\\frac{a}{b} = \\frac{c}{d}$
- Une racine carrée : $\\sqrt{x^2 + y^2}$

## Formules display

Voici des formules centrées :

$$\\sum_{i=1}^{n} i = \\frac{n(n+1)}{2}$$

$$\\int_{-\\infty}^{\\infty} e^{-x^2} dx = \\sqrt{\\pi}$$

$$\\lim_{x \\to \\infty} \\frac{1}{x} = 0$$

## Test d'erreur

Formule avec erreur : $\\invalid_syntax$

## Texte normal

Ceci est du texte normal sans formules mathématiques.
`);

    return (
        <div className="p-6 max-w-6xl mx-auto">
            <h1 className="text-2xl font-bold mb-6">Test Editor + Renderer Mathématiques</h1>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Éditeur */}
                <div>
                    <h2 className="text-xl font-semibold mb-4">Éditeur</h2>
                    <MarkdownEditor
                        value={content}
                        onChange={setContent}
                        label="Contenu avec formules mathématiques"
                        placeholder="Saisissez votre contenu avec des formules..."

                        // Fonctionnalités mathématiques activées
                        enableMathInline={true}
                        enableMathDisplay={true}

                        // Autres fonctionnalités utiles
                        enableBold={true}
                        enableItalic={true}
                        enableHeading={true}
                        enableUnorderedList={true}
                        enableOrderedList={true}
                        enablePreview={true}
                        enableSideBySide={false}
                        enableGuide={true}

                        minHeight="400px"
                    />
                </div>

                {/* Aperçu avec MarkdownRenderer */}
                <div>
                    <h2 className="text-xl font-semibold mb-4">Aperçu (MarkdownRenderer)</h2>
                    <div className="border border-gray-300 rounded-lg p-4 bg-white min-h-[400px]">
                        <MarkdownRenderer>
                            {content}
                        </MarkdownRenderer>
                    </div>
                </div>
            </div>

            {/* Instructions */}
            <div className="mt-8 p-4 bg-blue-50 rounded-lg">
                <h3 className="text-lg font-semibold mb-2">Instructions de test :</h3>
                <ul className="list-disc list-inside space-y-1 text-sm">
                    <li>Utilisez le bouton <strong>∑</strong> pour insérer des formules inline ($...$ )</li>
                    <li>Utilisez le bouton <strong>∫</strong> pour insérer des formules display ($$...$$)</li>
                    <li>Testez la prévisualisation pour voir le rendu des formules</li>
                    <li>Vérifiez que les formules s'affichent correctement dans les deux colonnes</li>
                    <li>Testez des formules complexes avec fractions, intégrales, sommes, etc.</li>
                </ul>
            </div>
        </div>
    );
};

export default MathEditorTest;