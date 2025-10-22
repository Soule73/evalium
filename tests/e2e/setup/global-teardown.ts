import { FullConfig } from '@playwright/test';
import fs from 'fs';
import path from 'path';

async function globalTeardown(config: FullConfig) {
    console.log('Nettoyage après les tests...');

    try {
        // Nettoyer les fichiers d'authentification
        const authFile = 'playwright/.auth/user.json';
        if (fs.existsSync(authFile)) {
            fs.unlinkSync(authFile);
            console.log('Fichier d\'authentification supprimé');
        }

        // Nettoyer les fichiers temporaires de test
        const tempDir = 'test-results';
        if (fs.existsSync(tempDir)) {
            // Ne supprimer que les fichiers anciens pour préserver les derniers résultats
            const files = fs.readdirSync(tempDir);
            const oldFiles = files.filter(file => {
                const filePath = path.join(tempDir, file);
                const stats = fs.statSync(filePath);
                const dayInMs = 24 * 60 * 60 * 1000;
                return Date.now() - stats.mtime.getTime() > dayInMs;
            });

            oldFiles.forEach(file => {
                const filePath = path.join(tempDir, file);
                fs.unlinkSync(filePath);
            });

            if (oldFiles.length > 0) {
                console.log(`${oldFiles.length} fichiers temporaires anciens supprimés`);
            }
        }

        // Afficher un résumé des résultats si disponible
        const resultsFile = 'playwright-report/results.json';
        if (fs.existsSync(resultsFile)) {
            try {
                const results = JSON.parse(fs.readFileSync(resultsFile, 'utf8'));
                const { stats } = results;

                console.log('Résumé des tests:');
                console.log(`   Réussis: ${stats.expected || 0}`);
                console.log(`   Échoués: ${stats.unexpected || 0}`);
                console.log(`   Ignorés: ${stats.skipped || 0}`);
                console.log(`   Durée totale: ${results.durationMs}ms`);

                if (stats.unexpected > 0) {
                    console.log('Certains tests ont échoué. Consultez le rapport HTML pour plus de détails.');
                }
            } catch (error) {
                console.log('Rapport de résultats non disponible');
            }
        }

    } catch (error) {
        console.error('Erreur lors du nettoyage:', error);
    }

    console.log('Nettoyage terminé');
}

export default globalTeardown;