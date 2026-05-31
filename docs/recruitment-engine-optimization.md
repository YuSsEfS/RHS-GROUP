# Optimisation du moteur de recrutement

## Architecture actuelle

Le moteur reste dans l application Laravel principale afin de ne pas casser les workflows admin existants.

Composants principaux :

- `CvExtractionService` : extraction brute PDF/DOCX/TXT
- `CvIndexingService` : indexation locale et structuration initiale du profil
- `CvIngestionService` : ingestion des CV applicatifs et imports manuels
- `CvDuplicateDetectionService` : detection multi-signaux des doublons avant creation
- `CvStorageOptimizationService` : compression/verifications de stockage sans bloquer l UI
- `RecruitmentScoringService` : scoring local, orchestration des matches et enrichissement des profils
- `AiRecruitmentAnalysisService` : facade stable pour l analyse IA
- `AiFinalCvScoringService` : appel OpenAI optionnel avec fallback local

Jobs deja en place :

- `SyncApplicationCvToBankJob`
- `ProcessManualCvUploadJob`
- `ScoreRecruitmentRequestMatchesJob`
- `AnalyzeCvMatchWithAiJob`
- `IndexExternalCvBatchJob`
- `CompressCvFileJob`

Ecrans et endpoints de suivi ajoutes :

- `GET /admin/matching-history`
- `GET /admin/cvs/import-status/{cvImportBatch}`
- `GET /admin/external-cvs/{externalCvBatch}/status`
- `POST /admin/cvs/{cv}/optimize-storage`
- `POST /admin/cvs/bulk-optimize-storage`

## Strategie queue

Objectif : eviter les longues requetes synchrones sous charge multi-utilisateurs.

Recommandations appliquees / a conserver :

- garder l indexation de CV dans des jobs dedies
- garder la compression de stockage CV dans des jobs dedies
- garder le matching de demandes dans un job dedie
- garder l analyse IA candidat par candidat dans un job dedie
- limiter les actions controller a la validation, creation de contexte et redirection
- preferer `afterCommit()` pour les futurs dispatchs lies a des enregistrements nouvellement crees
- utiliser la connexion de queue `database` pour les traitements lourds meme si l environnement local est encore en `sync`
- ne jamais redeclarer les proprietes `Queueable` comme `$connection` ou `$queue`
- utiliser `onConnection('database')` et `onQueue('indexing'|'compression'|'recruitment'|'ai')` dans les constructeurs de jobs
- separer les queues lourdes pour permettre l indexation, la compression et le matching en parallele avec plusieurs workers

### Commandes a prevoir en local / production

Si la queue n est pas encore configuree :

1. definir `QUEUE_CONNECTION=database`
2. executer `php artisan migrate`
3. redemarrer les workers apres une mise a jour de code :

```bash
php artisan queue:restart
```

4. lancer des workers separes. Un worker ne traite qu un job a la fois; plusieurs terminaux donnent une vraie execution parallele :

```bash
php artisan queue:work database --queue=indexing --tries=5 --timeout=1200 --memory=1024 --sleep=1
php artisan queue:work database --queue=compression --tries=5 --timeout=1200 --memory=1024 --sleep=1
php artisan queue:work database --queue=recruitment --tries=5 --timeout=1200 --memory=1024 --sleep=1
php artisan queue:work database --queue=ai --tries=3 --timeout=1200 --memory=512 --sleep=1
```

Queues utilisees :

- `indexing` : lots de la base externe, imports manuels CV et synchronisation des candidatures vers la CV Bank
- `compression` : optimisation stockage CV
- `recruitment` : scoring/matching local
- `ai` : analyse IA candidat par candidat

### Comment garder l application fluide

- laisser le worker tourner dans un terminal dedie
- utiliser plusieurs workers dedies si plusieurs traitements lourds doivent avancer en meme temps
- laisser les traitements tourner meme si l utilisateur change de page, recharge ou ferme l onglet
- utiliser les pages de statut pour suivre les lots sans recharger manuellement
- ouvrir l historique matching pour retrouver les resultats termines et non lus
- laisser les formulaires d import envoyer rapidement les fichiers, puis deleguer l indexation au worker
- ne jamais charger tous les resultats de matching en une seule page; les resultats sont pagines pour proteger le navigateur et MySQL

### Effet attendu

- l indexation externe n immobilise plus la navigation admin
- l import manuel CV n immobilise plus la page d upload
- la compression des CV continue sans garder l utilisateur sur la page CV Bank
- le lancement de matching renvoie immediatement la page de resultats
- l analyse IA ne doit jamais bloquer le navigateur
- les imports manuels de CV peuvent etre planifies sans attendre leur fin en requete HTTP

## Frontieres de services preparees

Ces services peuvent sortir plus tard dans un microservice/API sans refaire la logique metier :

- `CvIndexingService`
- `CvIngestionService`
- `RecruitmentScoringService`
- `AiRecruitmentAnalysisService`

Contrats futurs conseilles :

- `CvIndexerInterface`
- `RecruitmentAnalyzerInterface`
- `CandidateScorerInterface`

## Split microservice recommande plus tard

La separation actuelle est deja de type "API interne asynchrone" : les controllers lancent un job et les pages interrogent l etat stocke en base. Le vrai split dans une autre application/API doit se faire comme une etape de deploiement separee, pas en deplacant brutalement le code, afin de ne pas casser l admin ni les donnees CV.

### Service CV / Indexation

Responsabilites :

- extraction texte
- normalisation CV
- hash et deduplication
- compression de stockage et verification post-compression
- structuration locale du profil

Endpoints possibles :

- `POST /api/cv/extract`
- `POST /api/cv/index`
- `POST /api/cv/reindex`
- `POST /api/cv/optimize-storage`
- `GET /api/cv/import-status/{id}`
- `GET /api/cv/external-batches/{id}/status`

### Service Matching

Responsabilites :

- scoring local deterministe
- orchestration des demandes de matching
- analyses IA asynchrones

Endpoints possibles :

- `POST /api/matching/requests`
- `POST /api/matching/requests/{id}/score`
- `POST /api/matching/matches/{id}/analyze-ai`
- `GET /api/matching/requests/{id}/results`
- `GET /api/matching/history`

## Risques a surveiller

- saturation CPU lors des batchs PDF volumineux
- ralentissements I/O sur stockage local si plusieurs imports arrivent en meme temps
- consommation disque si les originaux compresses verifies sont conserves trop longtemps
- reanalyse IA couteuse si les retries ne sont pas limites
- besoins de reprise sur erreur si des jobs tombent pendant un import volumineux

## Notes de deploiement

- utiliser un vrai worker de queue en production
- lancer toutes les files lourdes en local :
  `php artisan queue:work database --queue=indexing,compression,recruitment,ai --tries=1 --timeout=1200`
- pour du vrai parallele, lancer plusieurs workers dans plusieurs terminaux avec des queues separees, par exemple un worker `indexing`, un worker `compression`, et un worker `recruitment,ai`
- si des anciens jobs restent bloques sur l ancienne file `external-indexing`, lancer une fois `php artisan queue:work database --queue=external-indexing,indexing --stop-when-empty --tries=1 --timeout=1200`, puis relancer les workers standards ci-dessus
- separer la queue `ai` si le volume d analyses IA augmente
- surveiller le temps moyen de `ScoreRecruitmentRequestMatchesJob`
- surveiller le temps moyen de `CompressCvFileJob`
- surveiller le taux d echec d extraction PDF/DOCX
- ajouter plus tard des logs techniques ou metrics par lot d indexation
- pour une future extraction API, sortir d abord `CvIndexingService`, puis `RecruitmentScoringService`, et enfin l analyse IA
- a court terme, conserver l historique matching et les endpoints de statut dans Laravel pour eviter de perdre les URLs de resultats

## API mobile

Les routes `routes/api.php` sont preparees avec Sanctum : login, logout, utilisateur courant, notifications, reunions, messages internes, ressources RH et demandes de recrutement autorisees. Les tokens mobiles utilisent `Laravel\Sanctum\HasApiTokens` sur le modele `User`; lancer les migrations Sanctum avant de tester l authentification API.
