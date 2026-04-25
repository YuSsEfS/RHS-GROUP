# Optimisation du moteur de recrutement

## Architecture actuelle

Le moteur reste dans l application Laravel principale afin de ne pas casser les workflows admin existants.

Composants principaux :

- `CvExtractionService` : extraction brute PDF/DOCX/TXT
- `CvIndexingService` : indexation locale et structuration initiale du profil
- `CvIngestionService` : ingestion des CV applicatifs et imports manuels
- `RecruitmentScoringService` : scoring local, orchestration des matches et enrichissement des profils
- `AiRecruitmentAnalysisService` : facade stable pour l analyse IA
- `AiFinalCvScoringService` : appel OpenAI optionnel avec fallback local

Jobs deja en place :

- `SyncApplicationCvToBankJob`
- `ProcessManualCvUploadJob`
- `ScoreRecruitmentRequestMatchesJob`
- `AnalyzeCvMatchWithAiJob`

## Strategie queue

Objectif : eviter les longues requetes synchrones sous charge multi-utilisateurs.

Recommandations appliquees / a conserver :

- garder l indexation de CV dans des jobs dedies
- garder le matching de demandes dans un job dedie
- garder l analyse IA candidat par candidat dans un job dedie
- limiter les actions controller a la validation, creation de contexte et redirection
- preferer `afterCommit()` pour les futurs dispatchs lies a des enregistrements nouvellement crees

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

### Service CV / Indexation

Responsabilites :

- extraction texte
- normalisation CV
- hash et deduplication
- structuration locale du profil

Endpoints possibles :

- `POST /api/cv/extract`
- `POST /api/cv/index`
- `POST /api/cv/reindex`

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

## Risques a surveiller

- saturation CPU lors des batchs PDF volumineux
- ralentissements I/O sur stockage local si plusieurs imports arrivent en meme temps
- reanalyse IA couteuse si les retries ne sont pas limites
- besoins de reprise sur erreur si des jobs tombent pendant un import volumineux

## Notes de deploiement

- utiliser un vrai worker de queue en production
- separer la queue `ai` si le volume d analyses IA augmente
- surveiller le temps moyen de `ScoreRecruitmentRequestMatchesJob`
- surveiller le taux d echec d extraction PDF/DOCX
- ajouter plus tard des logs techniques ou metrics par lot d indexation
