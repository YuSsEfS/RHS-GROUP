<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Keyword synonyms
    |--------------------------------------------------------------------------
    |
    | Keys and values should stay human-readable. The scoring service will
    | normalize accents/case itself.
    |
    */

    'synonyms' => [

        // =====================================================================
        // LANGUAGES
        // =====================================================================
        'français' => ['francais', 'french', 'fr', 'langue française', 'langue francaise'],
        'francais' => ['français', 'french', 'fr'],
        'anglais' => ['english', 'en', 'ang', 'business english', 'courant en anglais'],
        'english' => ['anglais', 'en'],
        'arabe' => ['arabic', 'ar', 'darija', 'classical arabic'],
        'arabic' => ['arabe', 'ar'],
        'espagnol' => ['spanish', 'es', 'castillan'],
        'spanish' => ['espagnol', 'es'],
        'allemand' => ['german', 'de'],
        'german' => ['allemand', 'de'],
        'italien' => ['italian', 'it'],
        'italian' => ['italien', 'it'],
        'portugais' => ['portuguese', 'pt'],
        'portuguese' => ['portugais', 'pt'],
        'néerlandais' => ['neerlandais', 'dutch', 'nl'],
        'neerlandais' => ['néerlandais', 'dutch', 'nl'],
        'dutch' => ['néerlandais', 'neerlandais', 'nl'],
        'turc' => ['turkish', 'tr'],
        'turkish' => ['turc', 'tr'],
        'russe' => ['russian', 'ru'],
        'russian' => ['russe', 'ru'],
        'chinois' => ['mandarin', 'chinese', 'zh'],
        'chinese' => ['chinois', 'mandarin', 'zh'],

        // =====================================================================
        // OFFICE / DIGITAL / TOOLS
        // =====================================================================
        'excel' => ['microsoft excel', 'xls', 'xlsx', 'tableur', 'spreadsheet', 'tableaux croisés dynamiques', 'tableaux croises dynamiques', 'tcd', 'vlookup', 'xlookup'],
        'microsoft excel' => ['excel', 'xls', 'xlsx'],
        'word' => ['microsoft word', 'word processing', 'traitement de texte'],
        'microsoft word' => ['word'],
        'powerpoint' => ['ppt', 'presentation', 'microsoft powerpoint', 'présentation', 'slides'],
        'microsoft powerpoint' => ['powerpoint', 'ppt'],
        'outlook' => ['messagerie', 'email', 'courriel', 'microsoft outlook'],
        'pack office' => ['microsoft office', 'office', 'bureautique', 'office 365', 'microsoft 365'],
        'office 365' => ['microsoft 365', 'pack office', 'office'],
        'microsoft 365' => ['office 365', 'pack office'],
        'google sheets' => ['tableur', 'spreadsheet', 'google spreadsheet'],
        'google docs' => ['documents google', 'docs'],
        'google drive' => ['drive', 'partage de fichiers', 'stockage cloud'],
        'power bi' => ['powerbi', 'business intelligence', 'dashboarding', 'reporting bi'],
        'powerbi' => ['power bi', 'business intelligence'],
        'tableau' => ['tableau software', 'bi', 'dashboarding'],
        'sap' => ['erp sap', 'sap mm', 'sap fi', 'sap sd', 'sap hr'],
        'erp' => ['sap', 'odoo', 'sage', 'oracle erp', 'dynamics', 'navision'],
        'crm' => ['salesforce', 'hubspot', 'relation client', 'gestion relation client'],
        'salesforce' => ['crm'],
        'hubspot' => ['crm'],
        'odoo' => ['erp'],
        'sage' => ['erp', 'sage x3', 'sage 100'],
        'sage x3' => ['sage', 'erp'],
        'sage 100' => ['sage', 'erp'],
        'oracle erp' => ['erp'],
        'dynamics' => ['erp', 'microsoft dynamics'],
        'navision' => ['erp', 'dynamics'],
        'trello' => ['kanban', 'gestion de tâches', 'gestion de taches'],
        'jira' => ['agile', 'scrum', 'gestion de tickets'],
        'notion' => ['documentation', 'wiki'],
        'slack' => ['messagerie d équipe', 'messagerie d\'équipe', 'communication interne'],
        'teams' => ['microsoft teams', 'visioconférence', 'visioconference'],
        'zoom' => ['visioconférence', 'visioconference', 'meeting online'],
        'canva' => ['design simple', 'création visuelle', 'creation visuelle'],
        'photoshop' => ['adobe photoshop', 'retouche photo'],
        'illustrator' => ['adobe illustrator', 'vectoriel'],
        'figma' => ['ui design', 'ux design', 'maquette'],
        'autocad' => ['cao', 'dessin technique'],
        'solidworks' => ['cao', 'conception mécanique', 'conception mecanique'],

        // =====================================================================
        // DEV / SOFTWARE / DATA / CLOUD
        // =====================================================================
        'js' => ['javascript'],
        'javascript' => ['js', 'ecmascript'],
        'ts' => ['typescript'],
        'typescript' => ['ts'],
        'node' => ['nodejs', 'node.js'],
        'nodejs' => ['node', 'node.js'],
        'php' => ['laravel', 'symfony', 'php natif'],
        'laravel' => ['php', 'framework laravel'],
        'symfony' => ['php', 'framework symfony'],
        'react' => ['reactjs', 'react.js'],
        'reactjs' => ['react', 'react.js'],
        'vue' => ['vuejs', 'vue.js'],
        'vuejs' => ['vue', 'vue.js'],
        'angular' => ['angularjs'],
        'sql' => ['mysql', 'postgresql', 'postgres', 'database', 'base de données', 'base de donnees', 'sql server', 'oracle sql'],
        'mysql' => ['sql'],
        'postgresql' => ['sql', 'postgres'],
        'postgres' => ['postgresql', 'sql'],
        'sql server' => ['sql', 'mssql'],
        'mssql' => ['sql server', 'sql'],
        'mongodb' => ['nosql', 'mongo'],
        'nosql' => ['mongodb', 'mongo'],
        'api' => ['rest', 'rest api', 'web services', 'api rest'],
        'rest' => ['api', 'rest api'],
        'git' => ['github', 'gitlab', 'version control'],
        'github' => ['git'],
        'gitlab' => ['git'],
        'docker' => ['container', 'containers'],
        'kubernetes' => ['k8s', 'orchestration'],
        'linux' => ['ubuntu', 'debian', 'unix', 'shell'],
        'python' => ['pandas', 'numpy', 'python3'],
        'java' => ['spring', 'spring boot'],
        'spring boot' => ['spring', 'java'],
        'c#' => ['dotnet', '.net'],
        '.net' => ['dotnet', 'c#'],
        'dotnet' => ['.net', 'c#'],
        'html' => ['html5'],
        'css' => ['css3'],
        'tailwind' => ['tailwindcss'],
        'bootstrap' => ['bootstrap 5', 'bootstrap4'],
        'aws' => ['amazon web services', 'cloud'],
        'azure' => ['microsoft azure', 'cloud'],
        'gcp' => ['google cloud', 'cloud'],
        'cloud' => ['aws', 'azure', 'gcp'],
        'devops' => ['ci/cd', 'integration continue', 'déploiement continu', 'deploiement continu'],
        'ci/cd' => ['devops', 'pipeline'],
        'data analysis' => ['analyse de données', 'analyse de donnees', 'data analytics'],
        'analyse de données' => ['data analysis', 'analyse de donnees'],
        'analyse de donnees' => ['data analysis', 'analyse de données'],
        'machine learning' => ['ml', 'apprentissage automatique'],
        'ml' => ['machine learning'],
        'cybersécurité' => ['cybersecurite', 'cybersecurity', 'sécurité informatique', 'securite informatique'],
        'cybersecurite' => ['cybersécurité', 'cybersecurity'],
        'cybersecurity' => ['cybersécurité', 'cybersecurite'],

        // =====================================================================
        // HR / ADMIN / FINANCE
        // =====================================================================
        'rh' => ['ressources humaines', 'human resources'],
        'ressources humaines' => ['rh', 'human resources'],
        'human resources' => ['rh', 'ressources humaines'],
        'recrutement' => ['recruitment', 'talent acquisition', 'sourcing', 'staffing', 'headhunting'],
        'recruitment' => ['recrutement', 'talent acquisition'],
        'talent acquisition' => ['recrutement', 'recruitment'],
        'sourcing' => ['recrutement'],
        'paie' => ['payroll', 'gestion de la paie'],
        'payroll' => ['paie'],
        'administration' => ['administratif', 'back office', 'gestion administrative'],
        'administratif' => ['administration', 'back office'],
        'assistant administratif' => ['administration', 'administratif', 'secrétaire', 'secretaire', 'assistant admin', 'back office', 'agent administratif'],
        'agent administratif' => ['assistant administratif', 'administratif'],
        'assistante administrative' => ['assistant administratif', 'administratif'],
        'secrétaire' => ['assistant administratif', 'administratif', 'secretariat'],
        'secretaire' => ['assistant administratif', 'administratif', 'secretariat'],
        'secretariat' => ['secrétaire', 'secretaire'],
        'réceptionniste' => ['receptionniste', 'front desk', 'accueil'],
        'receptionniste' => ['réceptionniste', 'front desk', 'accueil'],
        'front desk' => ['réceptionniste', 'receptionniste'],
        'accueil' => ['réceptionniste', 'receptionniste'],
        'comptabilité' => ['comptabilite', 'accounting'],
        'comptabilite' => ['comptabilité', 'accounting'],
        'accounting' => ['comptabilité', 'comptabilite'],
        'comptable' => ['accountant', 'comptabilité', 'comptabilite'],
        'accountant' => ['comptable', 'accounting'],
        'aide comptable' => ['assistant comptable', 'comptabilité', 'comptabilite'],
        'assistant comptable' => ['aide comptable', 'comptabilité', 'comptabilite'],
        'finance' => ['financial', 'contrôle de gestion', 'controle de gestion', 'gestion financière', 'gestion financiere'],
        'contrôle de gestion' => ['financial control', 'finance', 'controle de gestion', 'controleur de gestion', 'contrôleur de gestion'],
        'controle de gestion' => ['financial control', 'finance', 'contrôle de gestion', 'controleur de gestion', 'contrôleur de gestion'],
        'contrôleur de gestion' => ['controleur de gestion', 'contrôle de gestion', 'controle de gestion'],
        'controleur de gestion' => ['contrôleur de gestion', 'contrôle de gestion', 'controle de gestion'],
        'facturation' => ['billing', 'invoice', 'invoicing'],
        'billing' => ['facturation'],
        'invoice' => ['facturation'],
        'recouvrement' => ['collections', 'credit collection'],
        'audit' => ['auditing', 'contrôle interne', 'controle interne'],
        'auditing' => ['audit'],
        'trésorerie' => ['tresorerie', 'treasury'],
        'tresorerie' => ['trésorerie', 'treasury'],
        'treasury' => ['trésorerie', 'tresorerie'],
        'banque' => ['banking'],
        'banking' => ['banque'],
        'contrôle interne' => ['controle interne', 'internal control'],
        'controle interne' => ['contrôle interne', 'internal control'],
        'internal control' => ['contrôle interne', 'controle interne'],
        'juridique' => ['legal', 'affaires juridiques'],
        'legal' => ['juridique'],
        'achats indirects' => ['purchasing indirect', 'indirect procurement'],
        'achats directs' => ['direct procurement', 'purchasing direct'],

        // =====================================================================
        // SALES / CUSTOMER / MARKETING
        // =====================================================================
        'commercial' => ['sales', 'business development', 'vente', 'technico-commercial'],
        'sales' => ['commercial', 'vente'],
        'business development' => ['commercial', 'sales', 'bizdev'],
        'bizdev' => ['business development'],
        'vente' => ['commercial', 'sales'],
        'service client' => ['customer service', 'relation client', 'support client', 'customer support'],
        'customer service' => ['service client', 'relation client'],
        'relation client' => ['service client', 'customer service', 'customer relation'],
        'support client' => ['service client', 'customer support'],
        'customer support' => ['support client', 'service client'],
        'télévente' => ['televente', 'inside sales'],
        'televente' => ['télévente', 'inside sales'],
        'inside sales' => ['televente', 'télévente'],
        'prospection' => ['lead generation', 'prospecting'],
        'négociation' => ['negociation', 'negotiation'],
        'negociation' => ['négociation', 'negotiation'],
        'negotiation' => ['négociation', 'negociation'],
        'merchandising' => ['trade marketing'],
        'trade marketing' => ['merchandising'],
        'marketing digital' => ['digital marketing', 'webmarketing'],
        'digital marketing' => ['marketing digital', 'webmarketing'],
        'webmarketing' => ['marketing digital', 'digital marketing'],
        'community manager' => ['social media manager', 'gestion réseaux sociaux', 'gestion reseaux sociaux'],
        'social media manager' => ['community manager'],
        'service après-vente' => ['sav', 'service apres vente', 'after sales service'],
        'service apres vente' => ['sav', 'service après-vente', 'after sales service'],
        'sav' => ['service après-vente', 'service apres vente'],
        'after sales service' => ['sav', 'service après-vente'],
        'chargé de clientèle' => ['charge de clientele', 'account manager', 'customer advisor'],
        'charge de clientele' => ['chargé de clientèle', 'account manager'],
        'customer advisor' => ['chargé de clientèle', 'charge de clientele'],
        'account manager' => ['chargé de clientèle', 'charge de clientele'],
        'téléconseiller' => ['teleconseiller', 'call center', 'conseiller client'],
        'teleconseiller' => ['téléconseiller', 'call center'],
        'call center' => ['téléconseiller', 'teleconseiller'],
        'conseiller client' => ['téléconseiller', 'teleconseiller'],

        // =====================================================================
        // PROCUREMENT / LOGISTICS / SUPPLY / CUSTOMS / TRANSIT
        // =====================================================================
        'achats' => ['procurement', 'purchasing', 'buyer', 'approvisionnements'],
        'procurement' => ['achats', 'purchasing'],
        'purchasing' => ['achats', 'procurement'],
        'buyer' => ['acheteur', 'achats'],
        'acheteur' => ['buyer', 'achats'],
        'approvisionnement' => ['supply', 'supply chain', 'stock', 'approvisionnements'],
        'approvisionnements' => ['approvisionnement', 'supply chain'],
        'logistique' => ['supply chain', 'transport', 'warehouse', 'stock', 'logistics'],
        'logistics' => ['logistique', 'supply chain'],
        'supply chain' => ['logistique', 'approvisionnement'],
        'stock' => ['inventory', 'magasin', 'gestion de stock'],
        'inventory' => ['stock'],
        'gestion de stock' => ['stock', 'inventory'],
        'magasin' => ['stock', 'warehouse', 'magasinier'],
        'warehouse' => ['magasin', 'stock'],
        'magasinier' => ['storekeeper', 'magasin'],
        'storekeeper' => ['magasinier'],
        'transport' => ['shipping', 'fleet', 'livraison'],
        'shipping' => ['transport'],
        'livraison' => ['delivery', 'transport'],
        'delivery' => ['livraison'],
        'planification' => ['planning', 'ordonnancement', 'scheduling'],
        'ordonnancement' => ['planification', 'planning'],
        'planning' => ['planification', 'ordonnancement'],
        'import' => ['importation'],
        'export' => ['exportation'],
        'importation' => ['import'],
        'exportation' => ['export'],
        'import export' => ['import-export', 'import/export', 'commerce international'],
        'import-export' => ['import export', 'import/export'],
        'import/export' => ['import export', 'import-export'],
        'commerce international' => ['import export', 'transit international'],
        'transit' => ['freight forwarding', 'transport international'],
        'freight forwarding' => ['transit'],
        'douane' => ['customs', 'custom clearance', 'clearance'],
        'customs' => ['douane'],
        'custom clearance' => ['douane', 'customs', 'clearance'],
        'clearance' => ['custom clearance', 'douane'],

        // very specific customs / transit roles
        'déclarant en douane' => ['declarant en douane', 'déclaration en douane', 'declaration en douane', 'customs declarant'],
        'declarant en douane' => ['déclarant en douane', 'déclaration en douane', 'declaration en douane', 'customs declarant'],
        'customs declarant' => ['déclarant en douane', 'declarant en douane'],
        'agent de transit' => ['transitaire', 'freight agent', 'agent transit'],
        'agent transit' => ['agent de transit', 'transitaire'],
        'transitaire' => ['agent de transit', 'agent transit', 'freight forwarder'],
        'freight agent' => ['agent de transit', 'transitaire'],
        'freight forwarder' => ['transitaire', 'agent de transit'],
        'responsable douane' => ['chef douane', 'customs manager', 'responsable dédouanement', 'responsable dedouanement'],
        'chef douane' => ['responsable douane', 'customs manager'],
        'customs manager' => ['responsable douane', 'chef douane'],
        'responsable dédouanement' => ['responsable dedouanement', 'responsable douane'],
        'responsable dedouanement' => ['responsable dédouanement', 'responsable douane'],
        'agent de dédouanement' => ['agent de dedouanement', 'clearance agent'],
        'agent de dedouanement' => ['agent de dédouanement', 'clearance agent'],
        'clearance agent' => ['agent de dédouanement', 'agent de dedouanement'],
        'assistant transit' => ['assistant import export', 'assistant logistique internationale'],
        'assistant import export' => ['assistant transit'],
        'coordinateur logistique' => ['coordonnateur logistique', 'logistics coordinator'],
        'coordonnateur logistique' => ['coordinateur logistique', 'logistics coordinator'],
        'logistics coordinator' => ['coordinateur logistique', 'coordonnateur logistique'],
        'supply planner' => ['planificateur supply', 'supply planning'],
        'planificateur supply' => ['supply planner'],
        'gestionnaire de stock' => ['inventory controller', 'stock controller'],
        'inventory controller' => ['gestionnaire de stock', 'stock controller'],
        'stock controller' => ['gestionnaire de stock', 'inventory controller'],

        // =====================================================================
        // PRODUCTION / INDUSTRY / MAINTENANCE / TECHNICAL
        // =====================================================================
        'production' => ['fabrication', 'industrie', 'manufacturing', 'process'],
        'fabrication' => ['production', 'manufacturing'],
        'industrie' => ['production', 'manufacturing', 'industriel'],
        'manufacturing' => ['production', 'fabrication'],
        'maintenance' => ['entretien', 'technicien maintenance', 'industrial maintenance', 'preventive maintenance', 'corrective maintenance'],
        'entretien' => ['maintenance'],
        'technicien maintenance' => ['maintenance', 'maintenance technician'],
        'maintenance technician' => ['technicien maintenance', 'maintenance'],
        'préventive' => ['preventive maintenance', 'maintenance préventive', 'maintenance preventive'],
        'corrective' => ['corrective maintenance', 'maintenance corrective'],
        'électromécanique' => ['electromecanique', 'electromechanical'],
        'electromecanique' => ['électromécanique'],
        'automatisme' => ['automation', 'plc', 'industrial automation'],
        'automation' => ['automatisme', 'plc'],
        'plc' => ['automatisme', 'automate', 'siemens s7', 'schneider plc'],
        'automate' => ['plc', 'automatisme'],
        'électricité industrielle' => ['electricite industrielle', 'industrial electricity'],
        'electricite industrielle' => ['électricité industrielle', 'industrial electricity'],
        'industrial electricity' => ['électricité industrielle', 'electricite industrielle'],
        'mécanique' => ['mecanique', 'mechanical'],
        'mecanique' => ['mécanique', 'mechanical'],
        'mechanical' => ['mécanique', 'mecanique'],
        'lean manufacturing' => ['lean', 'amélioration continue', 'amelioration continue'],
        'amélioration continue' => ['continuous improvement', 'lean', 'amelioration continue', 'kaizen'],
        'amelioration continue' => ['continuous improvement', 'lean', 'amélioration continue', 'kaizen'],
        'continuous improvement' => ['amélioration continue', 'amelioration continue', 'lean'],
        'kaizen' => ['amélioration continue', 'amelioration continue'],
        '5s' => ['lean', 'kaizen'],
        'smed' => ['lean'],
        'tpm' => ['total productive maintenance'],
        'total productive maintenance' => ['tpm'],
        'méthodes' => ['methodes', 'industrial methods'],
        'methodes' => ['méthodes', 'industrial methods'],
        'industrial methods' => ['méthodes', 'methodes'],
        'process' => ['procédé', 'procede'],
        'procédé' => ['process', 'procede'],
        'procede' => ['process', 'procédé'],
        'câblage' => ['cablage', 'wiring'],
        'cablage' => ['câblage', 'wiring'],
        'wiring' => ['câblage', 'cablage'],

        // =====================================================================
        // QUALITY / HSE / LAB / COMPLIANCE
        // =====================================================================
        'qualité' => ['qualite', 'quality', 'smq', 'quality assurance'],
        'qualite' => ['qualité', 'quality', 'smq'],
        'quality' => ['qualité', 'qualite'],
        'quality assurance' => ['assurance qualité', 'assurance qualite', 'qualité'],
        'assurance qualité' => ['assurance qualite', 'quality assurance'],
        'assurance qualite' => ['assurance qualité', 'quality assurance'],
        'contrôle qualité' => ['controle qualite', 'quality control', 'quality controller', 'quality inspector', 'contrôleur qualité', 'controleur qualite'],
        'controle qualite' => ['contrôle qualité', 'quality control', 'quality controller', 'quality inspector', 'contrôleur qualité', 'controleur qualite'],
        'contrôleur qualité' => ['controleur qualite', 'contrôle qualité', 'quality controller', 'quality inspector'],
        'controleur qualite' => ['contrôleur qualité', 'controle qualite', 'quality controller', 'quality inspector'],
        'quality controller' => ['quality control', 'quality inspector', 'contrôle qualité', 'controle qualite', 'contrôleur qualité'],
        'quality inspector' => ['quality control', 'quality controller', 'contrôle qualité', 'controle qualite'],
        'quality control' => ['quality controller', 'quality inspector', 'contrôle qualité', 'controle qualite'],
        'haccp' => ['iso 22000', 'sécurité alimentaire', 'securite alimentaire', 'food safety'],
        'iso 9001' => ['smq', 'qualité', 'qualite', 'quality management', 'qms'],
        'iso 22000' => ['haccp', 'food safety'],
        'iso 14001' => ['environnement', 'environmental management'],
        'iso 45001' => ['santé sécurité', 'sante securite', 'occupational safety'],
        'bpf' => ['bonnes pratiques de fabrication', 'gmp'],
        'gmp' => ['bpf', 'bonnes pratiques de fabrication'],
        'bpf' => ['bonnes pratiques pharmaceutiques'],
        'smq' => ['système de management de la qualité', 'systeme de management de la qualite', 'iso 9001', 'qms'],
        'qms' => ['smq', 'iso 9001'],
        'hse' => ['santé sécurité environnement', 'sante securite environnement', 'health safety environment', 'qhse', 'ehs'],
        'qhse' => ['hse'],
        'ehs' => ['hse'],
        'laboratoire' => ['lab', 'analyse', 'analyses', 'laboratory'],
        'laboratory' => ['laboratoire', 'lab'],
        'lab' => ['laboratoire', 'laboratory'],
        'analyse' => ['analyses', 'laboratoire', 'analysis'],
        'analyses' => ['analyse', 'laboratoire'],
        'analysis' => ['analyse', 'analyses'],
        'métrologie' => ['metrologie', 'metrology'],
        'metrologie' => ['métrologie', 'metrology'],
        'metrology' => ['métrologie', 'metrologie'],
        'traçabilité' => ['tracabilite', 'traceability'],
        'tracabilite' => ['traçabilité', 'traceability'],
        'traceability' => ['tracabilite', 'traçabilité'],
        'non conformité' => ['non conformite', 'non-conformite', 'nc', 'écart qualité', 'ecart qualite'],
        'non conformite' => ['non conformité', 'non-conformite', 'nc'],
        'quality audit' => ['audit qualité', 'audit qualite'],
        'audit qualité' => ['audit qualite', 'quality audit'],
        'audit qualite' => ['audit qualité', 'quality audit'],
        'capa' => ['corrective action', 'preventive action'],
        'corrective action' => ['capa'],
        'preventive action' => ['capa'],
        'validation' => ['qualification', 'validation process'],
        'qualification' => ['validation'],
        'conformité réglementaire' => ['conformite reglementaire', 'regulatory compliance'],
        'conformite reglementaire' => ['conformité réglementaire', 'regulatory compliance'],
        'regulatory compliance' => ['conformité réglementaire', 'conformite reglementaire'],
        'sécurité alimentaire' => ['securite alimentaire', 'food safety', 'haccp'],
        'securite alimentaire' => ['sécurité alimentaire', 'food safety', 'haccp'],
        'food safety' => ['sécurité alimentaire', 'securite alimentaire', 'haccp'],

        // =====================================================================
        // FOOD / AGRO / CHEM / PHARMA
        // =====================================================================
        'agroalimentaire' => ['food industry', 'industrie alimentaire', 'food processing'],
        'food industry' => ['agroalimentaire'],
        'food processing' => ['agroalimentaire'],
        'industrie alimentaire' => ['agroalimentaire'],
        'chimie' => ['chemical', 'chemistry'],
        'chemical' => ['chimie'],
        'chemistry' => ['chimie'],
        'pharmaceutique' => ['pharma', 'pharmaceutical'],
        'pharma' => ['pharmaceutique', 'pharmaceutical'],
        'pharmaceutical' => ['pharmaceutique', 'pharma'],
        'cosmétique' => ['cosmetique', 'cosmetics'],
        'cosmetique' => ['cosmétique', 'cosmetics'],
        'cosmetics' => ['cosmétique', 'cosmetique'],
        'microbiologie' => ['microbiology'],
        'microbiology' => ['microbiologie'],
        'physicochimie' => ['physico-chimie', 'physicochemical'],
        'physico-chimie' => ['physicochimie', 'physicochemical'],
        'physicochemical' => ['physicochimie', 'physico-chimie'],

        // =====================================================================
        // MANAGEMENT / PROJECT / OPERATIONS
        // =====================================================================
        'gestion de projet' => ['project management', 'pilotage de projet'],
        'project management' => ['gestion de projet'],
        'pilotage de projet' => ['gestion de projet'],
        'chef de projet' => ['project manager'],
        'project manager' => ['chef de projet'],
        'gestionnaire de produit' => ['product manager', 'chef de produit'],
        'product manager' => ['gestionnaire de produit', 'chef de produit'],
        'chef de produit' => ['gestionnaire de produit', 'product manager'],
        'team lead' => ['superviseur', 'responsable equipe', 'responsable d equipe', 'lead technique'],
        'superviseur' => ['team lead', 'responsable equipe'],
        'responsable equipe' => ['team lead', 'superviseur'],
        'responsable d equipe' => ['team lead', 'superviseur'],
        'operations' => ['opérations', 'ops', 'exploitation'],
        'opérations' => ['operations', 'ops', 'exploitation'],
        'ops' => ['operations', 'opérations'],
        'exploitation' => ['operations', 'opérations'],

        // =====================================================================
        // HEALTHCARE / SERVICE / FIELD
        // =====================================================================
        'infirmier' => ['nurse', 'infirmière', 'infirmiere'],
        'infirmière' => ['infirmier', 'nurse'],
        'infirmiere' => ['infirmier', 'nurse'],
        'nurse' => ['infirmier', 'infirmière', 'infirmiere'],
        'aide soignant' => ['care assistant', 'assistant de soins'],
        'care assistant' => ['aide soignant'],
        'technicien laboratoire' => ['lab technician', 'technicien de laboratoire'],
        'technicien de laboratoire' => ['technicien laboratoire', 'lab technician'],
        'lab technician' => ['technicien laboratoire', 'technicien de laboratoire'],
        'délégué médical' => ['delegue medical', 'medical representative'],
        'delegue medical' => ['délégué médical', 'medical representative'],
        'medical representative' => ['délégué médical', 'delegue medical'],

        // =====================================================================
        // SOFT SKILLS
        // =====================================================================
        'rigueur' => ['rigoureux', 'rigoureuse', 'sérieux', 'serieux', 'précision', 'precision'],
        'autonomie' => ['autonome'],
        'organisation' => ['organise', 'organisé', 'organisee', 'organisée'],
        'communication' => ['bon relationnel', 'aisance relationnelle', 'communication skills'],
        'travail en équipe' => ['travail en equipe', 'esprit d équipe', 'esprit d equipe', 'teamwork', 'collaboration'],
        'travail en equipe' => ['travail en équipe', 'esprit d équipe', 'esprit d equipe', 'teamwork', 'collaboration'],
        'esprit d équipe' => ['travail en équipe', 'travail en equipe', 'teamwork'],
        'esprit d equipe' => ['travail en équipe', 'travail en equipe', 'teamwork'],
        'teamwork' => ['travail en équipe', 'travail en equipe', 'esprit d équipe', 'esprit d equipe'],
        'leadership' => ['management', 'capacité à encadrer', 'capacite a encadrer'],
        'adaptabilité' => ['adaptabilite', 'flexibilité', 'flexibilite'],
        'adaptabilite' => ['adaptabilité', 'flexibilité', 'flexibilite'],
        'flexibilité' => ['adaptabilité', 'adaptabilite'],
        'flexibilite' => ['adaptabilité', 'adaptabilite'],
        'esprit analytique' => ['analyse', 'analytical mind'],
        'sens du détail' => ['sens du detail', 'attention au détail', 'attention au detail'],
        'sens du detail' => ['sens du détail', 'attention au détail', 'attention au detail'],
        'attention au détail' => ['sens du détail', 'sens du detail'],
        'attention au detail' => ['sens du détail', 'sens du detail'],
        'problem solving' => ['résolution de problèmes', 'resolution de problemes'],
        'résolution de problèmes' => ['problem solving', 'resolution de problemes'],
        'resolution de problemes' => ['problem solving', 'résolution de problèmes'],
        'ponctualité' => ['ponctualite'],
        'ponctualite' => ['ponctualité'],
        'disponibilité' => ['disponibilite'],
        'disponibilite' => ['disponibilité'],
        'polyvalence' => ['polyvalent', 'polyvalente', 'versatility'],
        'initiative' => ['proactivité', 'proactivite'],
        'proactivité' => ['initiative', 'proactivite'],
        'proactivite' => ['initiative', 'proactivité'],
        'gestion du stress' => ['resistance au stress', 'stress management'],
        'resistance au stress' => ['gestion du stress'],
        'stress management' => ['gestion du stress'],

        // =====================================================================
        // MAROC / RHS / VOCABULAIRE RH CIBLE
        // =====================================================================
        'attestation de salaire' => ['salary certificate', 'attestation salariale'],
        'attestation de travail' => ['work certificate', 'employment certificate'],
        'charge import export' => ['import export officer', 'agent import export'],
        'assistant transit et douane' => ['assistant transit', 'assistant douane'],
        'gestionnaire paie' => ['payroll officer', 'gestionnaire de paie'],
        'charge administration du personnel' => ['hr administration officer', 'administration du personnel'],
        'responsable exploitation' => ['operations manager', 'manager exploitation'],
        'technicien methodes' => ['method technician', 'industrial methods technician'],
        'agent sav' => ['service apres vente', 'after sales agent'],
        'charge recouvrement' => ['collections officer', 'credit controller'],
        'assistante de direction' => ['executive assistant', 'office manager assistant'],
        'gestionnaire parc auto' => ['fleet coordinator', 'fleet manager'],
        'agent back office' => ['back office officer', 'gestion administrative'],
        'responsable magasin' => ['warehouse supervisor', 'store manager'],
        'technicien laboratoire' => ['lab technician', 'technicien de laboratoire'],
        'commercial terrain' => ['field sales', 'outside sales'],
        'coordinateur rh' => ['hr coordinator', 'coordonnateur rh'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Job title families
    |--------------------------------------------------------------------------
    |
    | Used for broad family alignment only.
    |
    */

    'title_families' => [
        'qualite' => [
            'qualite', 'quality', 'quality assurance', 'assurance qualité', 'assurance qualite',
            'controle qualite', 'contrôle qualité', 'quality control', 'quality controller',
            'quality inspector', 'controleur qualite', 'contrôleur qualité',
            'haccp', 'iso 9001', 'iso 22000', 'smq', 'qms', 'laboratoire', 'lab',
            'hse', 'qhse', 'metrologie', 'métrologie', 'audit qualite', 'audit qualité'
        ],
        'production' => [
            'production', 'fabrication', 'manufacturing', 'industrie',
            'industriel', 'usine', 'atelier', 'process', 'procédé', 'procede'
        ],
        'maintenance' => [
            'maintenance', 'technicien maintenance', 'maintenance technician',
            'electromecanique', 'électromécanique', 'automatisme', 'automation',
            'plc', 'electricite industrielle', 'électricité industrielle', 'mecanique', 'mécanique'
        ],
        'logistique' => [
            'logistique', 'logistics', 'supply chain', 'transport', 'stock',
            'magasin', 'warehouse', 'approvisionnement', 'approvisionnements',
            'planification', 'ordonnancement', 'magasinier', 'gestionnaire de stock'
        ],
        'douane' => [
            'douane', 'customs', 'custom clearance', 'clearance',
            'declarant en douane', 'déclarant en douane', 'customs declarant',
            'agent de dédouanement', 'agent de dedouanement',
            'responsable douane', 'chef douane', 'customs manager'
        ],
        'transit' => [
            'transit', 'agent de transit', 'agent transit', 'transitaire',
            'freight forwarder', 'freight agent', 'import export',
            'import-export', 'import/export', 'commerce international'
        ],
        'commercial' => [
            'commercial', 'sales', 'business development', 'vente',
            'prospection', 'service client', 'customer service', 'account manager',
            'charge de clientele', 'chargé de clientèle', 'teleconseiller', 'téléconseiller'
        ],
        'finance' => [
            'finance', 'comptabilite', 'comptabilité', 'accounting',
            'comptable', 'accountant',
            'controle de gestion', 'contrôle de gestion',
            'controleur de gestion', 'contrôleur de gestion',
            'tresorerie', 'trésorerie', 'audit', 'facturation', 'billing', 'recouvrement'
        ],
        'rh' => [
            'rh', 'ressources humaines', 'human resources',
            'recrutement', 'recruitment', 'talent acquisition',
            'payroll', 'paie', 'administration du personnel'
        ],
        'it' => [
            'developer', 'developpeur', 'développeur', 'full stack', 'frontend',
            'backend', 'php', 'laravel', 'react', 'javascript',
            'typescript', 'node', 'sql', 'api', 'devops', 'data analysis',
            'analyse de données', 'analyse de donnees'
        ],
        'administratif' => [
            'assistant administratif', 'administration', 'administratif',
            'secretaire', 'secrétaire', 'back office', 'agent administratif',
            'réceptionniste', 'receptionniste', 'accueil'
        ],
        'achats' => [
            'achats', 'procurement', 'purchasing', 'buyer', 'acheteur'
        ],
        'marketing' => [
            'marketing', 'digital marketing', 'marketing digital',
            'community manager', 'social media manager',
            'communication', 'brand', 'webmarketing'
        ],
        'sante' => [
            'infirmier', 'infirmière', 'infirmiere', 'nurse',
            'aide soignant', 'care assistant', 'délégué médical', 'delegue medical'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Specific title tokens
    |--------------------------------------------------------------------------
    |
    | These are decisive discriminators.
    | If the requested title has one of these tokens and the candidate title
    | lacks it, we apply a penalty even if the family is similar.
    |
    */

    'specific_title_tokens' => [
        // hierarchy / seniority
        'assistant',
        'junior',
        'senior',
        'manager',
        'responsable',
        'chef',
        'directeur',
        'superviseur',
        'coordinateur',
        'coordinatrice',
        'lead',
        'team lead',

        // operational specificity
        'agent',
        'declarant',
        'déclarant',
        'transitaire',
        'inspecteur',
        'controleur',
        'contrôleur',
        'technicien',
        'ingenieur',
        'ingénieur',
        'analyste',
        'consultant',
        'auditeur',
        'comptable',
        'receptionniste',
        'réceptionniste',
        'secretaire',
        'secrétaire',

        // functional specificity
        'administratif',
        'commercial',
        'qualite',
        'qualité',
        'logistique',
        'douane',
        'transit',
        'import',
        'export',
        'paie',
        'recrutement',
        'sourcing',
        'comptabilite',
        'comptabilité',
        'maintenance',
        'production',
        'laboratoire',
        'finance',
        'facturation',
        'recouvrement',
        'hse',
        'qhse',
        'supply',
        'stock',
        'achat',
        'achats',
        'service client',
        'support',
        'planning',
        'ordonnancement',
        'controle',
        'contrôle',
        'qualite',
        'quality',
    ],

    /*
    |--------------------------------------------------------------------------
    | Title conflict groups
    |--------------------------------------------------------------------------
    |
    | If a required title token belongs to one group and candidate title belongs
    | to another conflicting group inside the same family, apply a stronger penalty.
    |
    */

    'title_conflicts' => [
        'responsibility_level' => [
            'junior' => ['assistant', 'junior', 'stagiaire', 'trainee'],
            'specialist' => ['declarant', 'déclarant', 'controleur', 'contrôleur', 'technicien', 'inspecteur', 'analyste', 'agent', 'transitaire', 'comptable'],
            'leadership' => ['responsable', 'manager', 'chef', 'superviseur', 'coordinateur', 'coordinatrice', 'directeur', 'head'],
        ],

        'customs_transit_roles' => [
            'customs_declaration' => ['declarant', 'déclarant', 'dédouanement', 'dedouanement', 'customs declarant', 'clearance'],
            'transit_forwarding' => ['transit', 'transitaire', 'freight', 'forwarder', 'import', 'export'],
            'management_customs' => ['responsable douane', 'chef douane', 'customs manager', 'responsable', 'manager', 'chef'],
        ],

        'quality_roles' => [
            'inspection_control' => ['controleur', 'contrôleur', 'inspector', 'inspecteur', 'quality control'],
            'assurance_system' => ['assurance qualite', 'assurance qualité', 'quality assurance', 'smq', 'qms'],
            'hse_compliance' => ['hse', 'qhse', 'sécurité', 'securite', 'compliance'],
            'lab_analysis' => ['laboratoire', 'lab', 'analyse', 'analyses', 'microbiologie', 'physicochimie'],
        ],

        'finance_roles' => [
            'accounting' => ['comptable', 'accountant', 'comptabilite', 'comptabilité'],
            'controlling' => ['controle de gestion', 'contrôle de gestion', 'controleur de gestion', 'contrôleur de gestion'],
            'treasury' => ['tresorerie', 'trésorerie', 'treasury'],
            'billing_collection' => ['facturation', 'billing', 'recouvrement', 'collections'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Education ranks
    |--------------------------------------------------------------------------
    */

    'education_ranks' => [
        1 => ['niveau bac', 'bac'],
        2 => ['bac+2', 'bac 2', 'dut', 'bts', 'deust', 'ts', 'technicien specialise', 'technicien spécialisé'],
        3 => ['bac+3', 'bac 3', 'licence', 'bachelor'],
        4 => ['bac+5', 'bac 5', 'master', 'ingenieur', 'ingénieur', 'cycle ingenieur', 'cycle ingénieur'],
        5 => ['doctorat', 'phd'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Location aliases
    |--------------------------------------------------------------------------
    */

    'location_aliases' => [
        'casablanca' => ['casa', 'ain sebaa', 'ain sebaa', 'sidi maarouf', 'bouskoura', 'hay hassani', 'bernoussi', 'nouaceur'],
        'rabat' => ['sale', 'salé', 'temara', 'témara', 'rabat-sale', 'rabat salé', 'skhirat'],
        'tanger' => ['tanger-med', 'tanger med'],
        'mohammedia' => [],
        'fes' => ['fès'],
        'marrakech' => ['marrakesh'],
        'agadir' => [],
        'kenitra' => ['kénitra'],
        'el jadida' => ['jadida'],
        'meknes' => ['meknès'],
    ],
];
